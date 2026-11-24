<?php

namespace App\Contracts;

use App\Enums\EstadoCarta;
use App\Models\Carta;
use Illuminate\Support\Collection;

/**
 * Capa de datos de las cartas. El servicio solo conoce esta interfaz;
 * la implementación concreta (Eloquent) se enlaza en AppServiceProvider.
 */
interface CartaRepositoryInterface
{
    /** @return Collection<int, Carta> */
    public function listar(?EstadoCarta $estado = null, ?string $ciudad = null): Collection;

    public function buscarPorId(int $id): ?Carta;

    /** @param array<string, mixed> $datos */
    public function crear(array $datos): Carta;

    /** @param array<string, mixed> $datos */
    public function actualizar(Carta $carta, array $datos): Carta;

    public function eliminar(Carta $carta): void;
}
