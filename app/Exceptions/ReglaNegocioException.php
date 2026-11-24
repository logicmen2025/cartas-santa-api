<?php

namespace App\Exceptions;

use Carbon\CarbonInterface;

/** 422: los datos están bien formados, pero violan una regla del negocio. */
class ReglaNegocioException extends ApiException
{
    /** Regla 1: no se aceptan cartas después del 24 de diciembre. */
    public static function fueraDePlazo(CarbonInterface $fecha): self
    {
        return new self(422, 'FUERA_DE_PLAZO', 'No se aceptan cartas creadas después del 24 de diciembre.', [
            ['fechaRecepcion' => $fecha->toIso8601String(), 'fechaLimite' => $fecha->copy()->setDate($fecha->year, 12, 24)->endOfDay()->toIso8601String()],
        ]);
    }
}
