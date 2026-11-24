<?php

namespace App\Exceptions;

/** 403: el token es válido, pero el rol no alcanza. */
class AccesoDenegadoException extends ApiException
{
    /** @param list<string> $rolesPermitidos */
    public static function rolInsuficiente(string $rolActual, array $rolesPermitidos): self
    {
        return new self(403, 'ROL_INSUFICIENTE', sprintf(
            'Tu rol (%s) no tiene permiso para esta operación. Se requiere: %s.',
            $rolActual,
            implode(', ', $rolesPermitidos),
        ), [
            ['rolActual' => $rolActual, 'rolesPermitidos' => $rolesPermitidos],
        ]);
    }
}
