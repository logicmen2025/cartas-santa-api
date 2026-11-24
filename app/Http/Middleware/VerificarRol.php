<?php

namespace App\Http\Middleware;

use App\Exceptions\AccesoDenegadoException;
use App\Exceptions\NoAutenticadoException;
use App\Services\Auth\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de seguridad para rutas con rol.
 *
 * Uso en rutas: ->middleware('rol:ELFO_JEFE')
 *   - sin token ................... 401 TOKEN_REQUERIDO
 *   - token inválido o expirado ... 401 TOKEN_INVALIDO / TOKEN_EXPIRADO
 *   - token válido, otro rol ...... 403 ROL_INSUFICIENTE
 */
class VerificarRol
{
    public function __construct(private readonly JwtService $jwt) {}

    public function handle(Request $request, Closure $next, string ...$rolesPermitidos): Response
    {
        $token = JwtService::tokenDeLaPeticion($request);
        if ($token === null) {
            throw NoAutenticadoException::tokenRequerido();
        }

        $claims = $this->jwt->validar($token);

        if (! in_array($claims['rol'], $rolesPermitidos, true)) {
            throw AccesoDenegadoException::rolInsuficiente($claims['rol'], $rolesPermitidos);
        }

        $request->attributes->set('usuario', $claims);

        return $next($request);
    }
}
