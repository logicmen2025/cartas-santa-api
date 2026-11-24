<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Http\Requests\CambiarEstadoRequest;
use App\Http\Requests\GuardarCartaRequest;
use App\Http\Requests\ListarCartasRequest;
use App\Http\Resources\CartaPublicaResource;
use App\Http\Resources\CartaResource;
use App\Services\CartaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Controlador delgado: recibe la petición ya validada (FormRequest), llama
 * al servicio y arma la respuesta HTTP. No contiene reglas de negocio.
 */
class CartaController extends Controller
{
    public function __construct(private readonly CartaService $cartas) {}

    /** GET /api/cartas?estado=&ciudad= (público) */
    public function index(ListarCartasRequest $request): JsonResponse
    {
        $cartas = $this->cartas->listar($request->estado(), $request->ciudad());

        $coleccion = $this->veDatosCompletos($request)
            ? CartaResource::collection($cartas)
            : CartaPublicaResource::collection($cartas);

        return $coleccion->additional(['meta' => ['total' => $cartas->count()]])->response();
    }

    /** POST /api/cartas (público) */
    public function store(GuardarCartaRequest $request): JsonResponse
    {
        $carta = $this->cartas->registrar($request->validated());

        return CartaResource::make($carta)
            ->response()
            ->setStatusCode(201)
            ->header('Location', url("/api/cartas/{$carta->id}"));
    }

    /** GET /api/cartas/{id} (público) */
    public function show(Request $request, int $id): JsonResponse
    {
        $carta = $this->cartas->obtener($id);

        $recurso = $this->veDatosCompletos($request) ? CartaResource::make($carta) : CartaPublicaResource::make($carta);

        return $recurso->response();
    }

    /** PUT /api/cartas/{id} (público, solo si la carta está RECIBIDA) */
    public function update(GuardarCartaRequest $request, int $id): JsonResponse
    {
        $carta = $this->cartas->actualizar($id, $request->validated());

        return CartaResource::make($carta)->response();
    }

    /** DELETE /api/cartas/{id} (rol ELFO_JEFE) */
    public function destroy(int $id): Response
    {
        $this->cartas->eliminar($id);

        return response()->noContent();
    }

    /** PATCH /api/cartas/{id}/estado (rol ELFO_JEFE) */
    public function cambiarEstado(CambiarEstadoRequest $request, int $id): JsonResponse
    {
        $resultado = $this->cartas->cambiarEstado($id, $request->nuevoEstado());

        return CartaResource::make($resultado->carta)
            ->additional(['meta' => ['mensaje' => $resultado->mensaje]])
            ->response();
    }

    /** Solo el ELFO_JEFE autenticado ve nombre completo y edad de los menores. */
    private function veDatosCompletos(Request $request): bool
    {
        return ($request->attributes->get('usuario')['rol'] ?? null) === Rol::ELFO_JEFE->value;
    }
}
