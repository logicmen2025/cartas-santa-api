<?php

namespace App\Http\Middleware;

use App\Exceptions\SolicitudInvalidaException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si el cliente dice que manda JSON pero el cuerpo está roto, respondemos 400
 * de inmediato (Laravel por defecto lo ignoraría y diría "campo requerido").
 */
class ValidarCuerpoJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $contenido = $request->getContent();

        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'], true)
            && trim($contenido) !== ''
            && str_contains(strtolower((string) $request->header('Content-Type')), 'json')) {
            $decodificado = json_decode($contenido, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw SolicitudInvalidaException::jsonMalformado(json_last_error_msg());
            }

            if (! is_array($decodificado)) {
                throw SolicitudInvalidaException::cuerpoNoEsObjeto();
            }
        }

        return $next($request);
    }
}
