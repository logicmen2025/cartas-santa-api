<?php

namespace App\Enums;

enum EstadoCarta: string
{
    case RECIBIDA = 'RECIBIDA';
    case APROBADA = 'APROBADA';
    case EN_PRODUCCION = 'EN_PRODUCCION';
    case ENTREGADA = 'ENTREGADA';
    case RECHAZADA = 'RECHAZADA';

    /** @return list<string> */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
