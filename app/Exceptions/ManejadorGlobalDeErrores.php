<?php

namespace App\Exceptions;

use App\Support\RespuestaError;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Manejador global de errores de la API.
 *
 * Se registra en bootstrap/app.php. Cualquier excepción que salga de una ruta
 * /api/* termina aquí y se convierte en el formato único de error. Gracias a
 * esto los controladores y servicios solo lanzan excepciones; nunca arman
 * respuestas de error a mano, y nunca se escapa un HTML de error ni un 500
 * por culpa del servicio SOAP.
 */
final class ManejadorGlobalDeErrores
{
    public static function registrar(Exceptions $exceptions): void
    {
        // Los errores esperados (4xx y el 503 controlado) no ensucian el log.
        // El fallo del SOAP se registra explícitamente en CartaService.
        $exceptions->dontReportWhen(fn (Throwable $e) => $e instanceof ApiException
            && ($e->estadoHttp < 500 || $e instanceof ServicioExternoNoDisponibleException));

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api', 'api/*')) {
                return null; // fuera de la API, Laravel decide
            }

            return (new self)->convertir($e, $request);
        });
    }

    public function convertir(Throwable $e, Request $request): JsonResponse
    {
        $ruta = '/'.ltrim($request->path(), '/');

        [$estado, $codigo, $mensaje, $detalles, $cabeceras] = match (true) {
            $e instanceof ApiException => [
                $e->estadoHttp, $e->codigo, $e->getMessage(), $e->detalles, $e->cabeceras,
            ],
            $e instanceof ValidationException => [
                400, 'VALIDACION_FALLIDA', 'Los datos enviados no son válidos.', $this->detallesDeValidacion($e), [],
            ],
            $e instanceof NotFoundHttpException => [
                404, 'RUTA_NO_ENCONTRADA', "La ruta {$ruta} no existe.", [], [],
            ],
            $e instanceof MethodNotAllowedHttpException => [
                405, 'METODO_NO_PERMITIDO', "El método {$request->method()} no está permitido en {$ruta}.", [], $e->getHeaders(),
            ],
            $e instanceof ThrottleRequestsException => [
                429, 'DEMASIADAS_SOLICITUDES', 'Demasiados intentos. Espera un momento y vuelve a intentar.', [], $e->getHeaders(),
            ],
            $e instanceof AccessDeniedHttpException => [
                403, 'ACCESO_DENEGADO', 'No tienes permiso para esta operación.', [], $e->getHeaders(),
            ],
            $e instanceof HttpExceptionInterface => [
                $e->getStatusCode(), 'ERROR_HTTP', $e->getMessage() !== '' ? $e->getMessage() : 'Error HTTP.', [], $e->getHeaders(),
            ],
            default => [
                500, 'ERROR_INTERNO', 'Ocurrió un error inesperado en el servidor.', $this->detallesDeDepuracion($e), [],
            ],
        };

        return RespuestaError::crear($estado, $codigo, $mensaje, $detalles, $request, $cabeceras);
    }

    /** @return list<array{campo: string, mensaje: string}> */
    private function detallesDeValidacion(ValidationException $e): array
    {
        $detalles = [];
        foreach ($e->errors() as $campo => $mensajes) {
            foreach ($mensajes as $mensaje) {
                $detalles[] = ['campo' => $campo, 'mensaje' => $mensaje];
            }
        }

        return $detalles;
    }

    /** Solo con APP_DEBUG=true se muestra qué excepción ocurrió. */
    private function detallesDeDepuracion(Throwable $e): array
    {
        if (! config('app.debug')) {
            return [];
        }

        return [[
            'excepcion' => $e::class,
            'mensaje' => $e->getMessage(),
            'ubicacion' => basename($e->getFile()).':'.$e->getLine(),
        ]];
    }
}
