<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Toda la API habla JSON aunque el cliente no envíe "Accept: application/json".
 * Además deja las tildes y la ñ legibles en la respuesta.
 */
class ForzarRespuestaJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        $respuesta = $next($request);

        if ($respuesta instanceof JsonResponse) {
            $respuesta->setEncodingOptions($respuesta->getEncodingOptions() | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $respuesta;
    }
}
