<?php

namespace App\Support;

/** Minimización de datos de menores (extra A). */
final class Privacidad
{
    /**
     * "Valentina Gómez" -> "Valentina G."
     * Se conserva el primer nombre y solo la inicial de la palabra siguiente.
     */
    public static function nombreConInicial(string $nombreCompleto): string
    {
        $partes = preg_split('/\s+/u', trim($nombreCompleto), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($partes) <= 1) {
            return $partes[0] ?? '';
        }

        return $partes[0].' '.mb_strtoupper(mb_substr($partes[1], 0, 1)).'.';
    }
}
