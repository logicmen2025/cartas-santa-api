<?php

namespace App\Services;

use App\Contracts\CartaRepositoryInterface;
use App\Contracts\ListaBuenosTraviesosInterface;
use App\Enums\Comportamiento;
use App\Enums\EstadoCarta;
use App\Exceptions\ConflictoEstadoException;
use App\Exceptions\ListaNoDisponibleException;
use App\Exceptions\RecursoNoEncontradoException;
use App\Exceptions\ReglaNegocioException;
use App\Exceptions\ServicioExternoNoDisponibleException;
use App\Models\Carta;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Capa de servicios: aquí viven TODAS las reglas de negocio de las cartas.
 * Los controladores solo reciben la petición, llaman a este servicio y
 * devuelven la respuesta.
 *
 *   Regla 1: no se aceptan cartas después del 24 de diciembre ....... 422
 *   Regla 2: una carta nueva siempre nace en RECIBIDA
 *   Regla 3: solo se permiten las transiciones del mapa ............. 409
 *   Regla 4: para APROBAR se consulta la Lista (SOAP);
 *            si es TRAVIESO la carta pasa a RECHAZADA
 *   Regla 5: una carta ENTREGADA no se modifica ni se elimina ......... 409
 *   Fallo del SOAP (Fault o > 3 s): la carta queda RECIBIDA con
 *            comportamiento PENDIENTE_VERIFICACION ...................... 503
 */
class CartaService
{
    private const MES_LIMITE = 12;

    private const DIA_LIMITE = 24;

    /** Regla 3: mapa de transiciones válidas (estado actual => destinos posibles). */
    private const TRANSICIONES = [
        'RECIBIDA' => [EstadoCarta::APROBADA, EstadoCarta::RECHAZADA],
        'APROBADA' => [EstadoCarta::EN_PRODUCCION],
        'EN_PRODUCCION' => [EstadoCarta::ENTREGADA],
        'ENTREGADA' => [],
        'RECHAZADA' => [],
    ];

    public function __construct(
        private readonly CartaRepositoryInterface $cartas,
        private readonly ListaBuenosTraviesosInterface $lista,
    ) {}

    /** @return Collection<int, Carta> */
    public function listar(?EstadoCarta $estado = null, ?string $ciudad = null): Collection
    {
        return $this->cartas->listar($estado, $ciudad);
    }

    public function obtener(int $id): Carta
    {
        return $this->cartas->buscarPorId($id) ?? throw RecursoNoEncontradoException::carta($id);
    }

    /** @param array{remitente: string, edad: int, ciudad: string, regalos: list<string>} $datos */
    public function registrar(array $datos): Carta
    {
        $ahora = now();

        // Regla 1
        if ($this->estaFueraDePlazo($ahora)) {
            throw ReglaNegocioException::fueraDePlazo($ahora);
        }

        // Regla 2: estado, comportamiento y fecha los pone el sistema, no el cliente
        return $this->cartas->crear([
            'remitente' => $datos['remitente'],
            'edad' => (int) $datos['edad'],
            'ciudad' => $datos['ciudad'],
            'regalos' => array_values($datos['regalos']),
            'estado' => EstadoCarta::RECIBIDA,
            'comportamiento' => null,
            'fecha_recepcion' => $ahora,
        ]);
    }

    /** PUT: solo en RECIBIDA. */
    public function actualizar(int $id, array $datos): Carta
    {
        $carta = $this->obtener($id);

        // Regla 5
        if ($carta->estado === EstadoCarta::ENTREGADA) {
            throw ConflictoEstadoException::cartaEntregada('modificar');
        }

        if ($carta->estado !== EstadoCarta::RECIBIDA) {
            throw ConflictoEstadoException::cartaNoEditable($carta->estado);
        }

        return $this->cartas->actualizar($carta, [
            'remitente' => $datos['remitente'],
            'edad' => (int) $datos['edad'],
            'ciudad' => $datos['ciudad'],
            'regalos' => array_values($datos['regalos']),
        ]);
    }

    public function eliminar(int $id): void
    {
        $carta = $this->obtener($id);

        // Regla 5
        if ($carta->estado === EstadoCarta::ENTREGADA) {
            throw ConflictoEstadoException::cartaEntregada('eliminar');
        }

        $this->cartas->eliminar($carta);
    }

    /** PATCH /estado: aplica las reglas 3 y 4. */
    public function cambiarEstado(int $id, EstadoCarta $nuevoEstado): ResultadoCambioEstado
    {
        $carta = $this->obtener($id);
        $estadoActual = $carta->estado;

        // Regla 3
        $permitidas = self::TRANSICIONES[$estadoActual->value];
        if (! in_array($nuevoEstado, $permitidas, true)) {
            throw ConflictoEstadoException::transicionInvalida($estadoActual, $nuevoEstado, $permitidas);
        }

        // Regla 4
        if ($nuevoEstado === EstadoCarta::APROBADA) {
            return $this->aprobarConsultandoLaLista($carta);
        }

        $carta = $this->cartas->actualizar($carta, ['estado' => $nuevoEstado]);

        return new ResultadoCambioEstado($carta, "La carta pasó de {$estadoActual->value} a {$nuevoEstado->value}.");
    }

    private function aprobarConsultandoLaLista(Carta $carta): ResultadoCambioEstado
    {
        try {
            $comportamiento = $this->lista->consultarComportamiento($carta->remitente, $carta->ciudad);
        } catch (ListaNoDisponibleException $e) {
            // Escenario A: la carta NO cambia de estado (sigue RECIBIDA), se marca
            // PENDIENTE_VERIFICACION y la API responde 503 con el formato estándar.
            $carta = $this->cartas->actualizar($carta, ['comportamiento' => Comportamiento::PENDIENTE_VERIFICACION]);

            Log::warning('ListaBuenosTraviesosService no disponible; carta marcada PENDIENTE_VERIFICACION', [
                'carta_id' => $carta->id,
                'motivo' => $e->motivo,
                'detalle' => $e->getMessage(),
            ]);

            throw ServicioExternoNoDisponibleException::listaBuenosTraviesos($carta, $e);
        }

        if ($comportamiento === Comportamiento::TRAVIESO) {
            $carta = $this->cartas->actualizar($carta, [
                'estado' => EstadoCarta::RECHAZADA,
                'comportamiento' => Comportamiento::TRAVIESO,
            ]);

            return new ResultadoCambioEstado(
                $carta,
                'La Lista de Buenos y Traviesos dice TRAVIESO: la carta pasó a RECHAZADA en lugar de APROBADA.',
            );
        }

        $carta = $this->cartas->actualizar($carta, [
            'estado' => EstadoCarta::APROBADA,
            'comportamiento' => Comportamiento::BUENO,
        ]);

        return new ResultadoCambioEstado($carta, 'La Lista de Buenos y Traviesos dice BUENO: la carta pasó a APROBADA.');
    }

    private function estaFueraDePlazo(CarbonInterface $fecha): bool
    {
        return $fecha->month === self::MES_LIMITE && $fecha->day > self::DIA_LIMITE;
    }
}
