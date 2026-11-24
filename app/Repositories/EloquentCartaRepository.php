<?php

namespace App\Repositories;

use App\Contracts\CartaRepositoryInterface;
use App\Enums\EstadoCarta;
use App\Models\Carta;
use Illuminate\Support\Collection;

class EloquentCartaRepository implements CartaRepositoryInterface
{
    public function listar(?EstadoCarta $estado = null, ?string $ciudad = null): Collection
    {
        return Carta::query()
            ->when($estado, fn ($q) => $q->where('estado', $estado->value))
            // Filtro por ciudad sin distinguir mayúsculas: "quito" encuentra "Quito"
            ->when($ciudad, fn ($q) => $q->whereRaw('LOWER(ciudad) = ?', [mb_strtolower($ciudad)]))
            ->orderBy('id')
            ->get();
    }

    public function buscarPorId(int $id): ?Carta
    {
        return Carta::query()->find($id);
    }

    public function crear(array $datos): Carta
    {
        return Carta::query()->create($datos);
    }

    public function actualizar(Carta $carta, array $datos): Carta
    {
        $carta->fill($datos)->save();

        return $carta->refresh();
    }

    public function eliminar(Carta $carta): void
    {
        $carta->delete();
    }
}
