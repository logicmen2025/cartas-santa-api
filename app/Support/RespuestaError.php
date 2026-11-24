<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Único lugar donde se arma el JSON de error. Así todas las respuestas de
 * error tienen exactamente la misma forma.
 */
final class RespuestaError
{
    /**
     * @param  list<array<string, mixed>>  $detalles
     * @param  array<string, string>  $cabeceras
     */
    public static function crear(
        int $estadoHttp,
        string $codigo,
        string $mensaje,
        array $detalles,
        Request $request,
        array $cabeceras = [],
    ): JsonResponse {
        return new JsonResponse([
            'codigo' => $codigo,
            'mensaje' => $mensaje,
            'detalles' => array_values($detalles),
            'timestamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'ruta' => '/'.ltrim($request->path(), '/'),
        ], $estadoHttp, $cabeceras, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
