<?php

namespace App\Exceptions;

/** 401: falta el token o no es válido. */
class NoAutenticadoException extends ApiException
{
    private static function crear(string $codigo, string $mensaje): self
    {
        return new self(401, $codigo, $mensaje, [], ['WWW-Authenticate' => 'Bearer']);
    }

    public static function tokenRequerido(): self
    {
        return self::crear('TOKEN_REQUERIDO', 'Este endpoint requiere un token. Envíalo en el header Authorization: Bearer <token>.');
    }

    public static function tokenInvalido(): self
    {
        return self::crear('TOKEN_INVALIDO', 'El token enviado no es válido.');
    }

    public static function tokenExpirado(): self
    {
        return self::crear('TOKEN_EXPIRADO', 'El token expiró. Inicia sesión de nuevo en /api/auth/login.');
    }

    public static function credencialesInvalidas(): self
    {
        return self::crear('CREDENCIALES_INVALIDAS', 'Correo o contraseña incorrectos.');
    }
}
