<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Services\Auth\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Para endpoints públicos: si llega un token válido, deja los datos del
 * usuario en la petición; si no llega (o no sirve), sigue como anónimo.
 * Se usa para decidir si se muestran los datos completos del menor.
 */
class IdentificarUsuarioOpcional
{
    public function __construct(private readonly JwtService $jwt) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = JwtService::tokenDeLaPeticion($request);

        if ($token !== null) {
            try {
                $request->attributes->set('usuario', $this->jwt->validar($token));
            } catch (ApiException) {
                // endpoint público: un token malo no bloquea, solo no da privilegios
            }
        }

        return $next($request);
    }
}
