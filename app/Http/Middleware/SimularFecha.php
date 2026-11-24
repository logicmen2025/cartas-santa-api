<?php

namespace App\Http\Middleware;

use App\Exceptions\SolicitudInvalidaException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Solo para desarrollo y demostración.
 *
 * Con PERMITIR_FECHA_SIMULADA=true (y APP_ENV distinto de production), el
 * header "X-Fecha-Simulada: 2026-12-25" hace que la API trabaje con esa fecha
 * durante esa petición. Sirve para evidenciar la regla del 24 de diciembre
 * (422) sin tocar el reloj del computador. En producción se ignora.
 */
class SimularFecha
{
    public const CABECERA = 'X-Fecha-Simulada';

    public function handle(Request $request, Closure $next): Response
    {
        $valor = $request->header(self::CABECERA);

        if ($valor === null || ! config('polonorte.permitir_fecha_simulada') || app()->isProduction()) {
            return $next($request);
        }

        try {
            $fecha = Carbon::parse($valor, config('app.timezone'));
        } catch (Throwable) {
            throw SolicitudInvalidaException::fechaSimuladaInvalida((string) $valor);
        }

        Carbon::setTestNow($fecha);

        try {
            $respuesta = $next($request);
            $respuesta->headers->set('X-Fecha-Simulada-Aplicada', $fecha->toIso8601String());

            return $respuesta;
        } finally {
            Carbon::setTestNow();
        }
    }
}
