<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * La lanza el adaptador SOAP cuando no puede obtener una respuesta útil.
 * No es una excepción HTTP: el servicio de cartas decide qué hacer con ella.
 */
class ListaNoDisponibleException extends RuntimeException
{
    public const FAULT = 'FAULT';
    public const TIMEOUT = 'TIMEOUT';
    public const SIN_CONEXION = 'SIN_CONEXION';
    public const RESPUESTA_INVALIDA = 'RESPUESTA_INVALIDA';

    public function __construct(public readonly string $motivo, string $mensaje, ?Throwable $anterior = null)
    {
        parent::__construct($mensaje, 0, $anterior);
    }

    public static function fault(string $faultcode, string $faultstring): self
    {
        return new self(self::FAULT, "soap:Fault {$faultcode}: {$faultstring}");
    }

    public static function timeout(float $segundos, ?Throwable $anterior = null): self
    {
        return new self(self::TIMEOUT, sprintf('El servicio no respondió en %s segundos.', rtrim(rtrim(number_format($segundos, 2, '.', ''), '0'), '.')), $anterior);
    }

    public static function sinConexion(string $detalle, ?Throwable $anterior = null): self
    {
        return new self(self::SIN_CONEXION, "No se pudo conectar con el servicio: {$detalle}", $anterior);
    }

    public static function respuestaInvalida(string $detalle, ?Throwable $anterior = null): self
    {
        return new self(self::RESPUESTA_INVALIDA, "Respuesta no válida del servicio: {$detalle}", $anterior);
    }
}
