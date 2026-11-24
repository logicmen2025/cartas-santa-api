<?php

namespace App\Services;

use App\Models\Carta;

/** Lo que devuelve CartaService::cambiarEstado: la carta y una explicación. */
final class ResultadoCambioEstado
{
    public function __construct(
        public readonly Carta $carta,
        public readonly string $mensaje,
    ) {}
}
