<?php

namespace App\Enums;

/**
 * Roles del Escenario A:
 * - ELFO_JEFE: puede eliminar cartas y cambiar su estado.
 * - ELFO: está autenticado, pero esas operaciones le responden 403.
 */
enum Rol: string
{
    case ELFO_JEFE = 'ELFO_JEFE';
    case ELFO = 'ELFO';
}
