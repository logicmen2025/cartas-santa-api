<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Error "esperado" de la API. Lleva todo lo que necesita el manejador global
 * para armar la respuesta con el formato único:
 * { codigo, mensaje, detalles, timestamp, ruta }.
 */
class ApiException extends RuntimeException
{
    /**
     * @param  list<array<string, mixed>>  $detalles
     * @param  array<string, string>  $cabeceras
     */
    public function __construct(
        public readonly int $estadoHttp,
        public readonly string $codigo,
        string $mensaje,
        public readonly array $detalles = [],
        public readonly array $cabeceras = [],
        ?Throwable $anterior = null,
    ) {
        parent::__construct($mensaje, 0, $anterior);
    }
}
