<?php

namespace App\Exceptions;

use App\Models\Carta;

/** 503: el servicio SOAP externo falló (Fault, timeout o respuesta inválida). */
class ServicioExternoNoDisponibleException extends ApiException
{
    public static function listaBuenosTraviesos(Carta $carta, ListaNoDisponibleException $causa): self
    {
        return new self(
            503,
            'SERVICIO_EXTERNO_NO_DISPONIBLE',
            "La Lista de Buenos y Traviesos no está disponible. La carta {$carta->id} se mantiene en "
                ."{$carta->estado->value} con comportamiento PENDIENTE_VERIFICACION; intenta aprobarla más tarde.",
            [[
                'servicio' => 'ListaBuenosTraviesosService',
                'motivo' => $causa->motivo,
                'descripcion' => $causa->getMessage(),
                'cartaId' => $carta->id,
                'estadoCarta' => $carta->estado->value,
                'comportamiento' => $carta->comportamiento?->value,
            ]],
            ['Retry-After' => '60'],
            $causa,
        );
    }
}
