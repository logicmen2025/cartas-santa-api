<?php

namespace App\Exceptions;

/** 500: falta configuración obligatoria (por ejemplo JWT_SECRET). */
class ConfiguracionInvalidaException extends ApiException
{
    public function __construct(string $mensaje)
    {
        parent::__construct(500, 'CONFIGURACION_INVALIDA', $mensaje);
    }
}
