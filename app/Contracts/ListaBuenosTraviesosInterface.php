<?php

namespace App\Contracts;

use App\Enums\Comportamiento;
use App\Exceptions\ListaNoDisponibleException;

/**
 * Puerto hacia la Lista de Buenos y Traviesos.
 *
 * Quien implemente esta interfaz se encarga del protocolo (SOAP/XML). Para el
 * resto del sistema solo existen objetos de dominio: entra un nombre y una
 * ciudad, sale un Comportamiento.
 */
interface ListaBuenosTraviesosInterface
{
    /**
     * @return Comportamiento BUENO o TRAVIESO (nunca PENDIENTE_VERIFICACION)
     *
     * @throws ListaNoDisponibleException si el servicio responde un Fault, no
     *         responde a tiempo o devuelve algo que no se puede interpretar.
     */
    public function consultarComportamiento(string $nombre, string $ciudad): Comportamiento;
}
