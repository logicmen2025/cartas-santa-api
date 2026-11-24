<?php

namespace App\Enums;

/**
 * Lo llena el sistema, nunca el cliente.
 * - BUENO / TRAVIESO: lo que respondió la Lista de Buenos y Traviesos.
 * - PENDIENTE_VERIFICACION: la Lista falló (Fault o timeout) al intentar aprobar.
 * Mientras no se haya consultado la Lista, el campo queda en null.
 */
enum Comportamiento: string
{
    case BUENO = 'BUENO';
    case TRAVIESO = 'TRAVIESO';
    case PENDIENTE_VERIFICACION = 'PENDIENTE_VERIFICACION';
}
