<?php

namespace App\Exceptions;

/** 400: la petición no tiene un formato que se pueda procesar. */
class SolicitudInvalidaException extends ApiException
{
    public static function jsonMalformado(string $motivo): self
    {
        return new self(400, 'JSON_MALFORMADO', 'El cuerpo de la petición no es un JSON válido.', [
            ['motivo' => $motivo],
        ]);
    }

    public static function cuerpoNoEsObjeto(): self
    {
        return new self(400, 'JSON_MALFORMADO', 'El cuerpo de la petición debe ser un objeto JSON.');
    }

    public static function fechaSimuladaInvalida(string $valor): self
    {
        return new self(400, 'FECHA_SIMULADA_INVALIDA', 'El header X-Fecha-Simulada no tiene una fecha válida (usa por ejemplo 2026-12-25 o 2026-12-25T10:00:00-05:00).', [
            ['valorRecibido' => $valor],
        ]);
    }
}
