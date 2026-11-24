<?php

namespace App\Exceptions;

use App\Enums\EstadoCarta;

/** 409: la operación choca con el estado actual de la carta. */
class ConflictoEstadoException extends ApiException
{
    /** Regla 3: cualquier transición fuera del mapa permitido. */
    public static function transicionInvalida(EstadoCarta $desde, EstadoCarta $hacia, array $permitidas): self
    {
        return new self(409, 'TRANSICION_INVALIDA', "No se puede pasar de {$desde->value} a {$hacia->value}", [
            [
                'estadoActual' => $desde->value,
                'estadoSolicitado' => $hacia->value,
                'transicionesPermitidas' => array_map(fn (EstadoCarta $e) => $e->value, $permitidas),
            ],
        ]);
    }

    /** PUT solo se permite en RECIBIDA. */
    public static function cartaNoEditable(EstadoCarta $estado): self
    {
        return new self(409, 'CARTA_NO_EDITABLE', "Solo se pueden editar cartas en estado RECIBIDA; esta carta está en {$estado->value}.", [
            ['estadoActual' => $estado->value],
        ]);
    }

    /** Regla 5: una carta ENTREGADA no se modifica ni se elimina. */
    public static function cartaEntregada(string $accion): self
    {
        return new self(409, 'CARTA_ENTREGADA', "Una carta ENTREGADA no se puede {$accion}.", [
            ['estadoActual' => EstadoCarta::ENTREGADA->value],
        ]);
    }
}
