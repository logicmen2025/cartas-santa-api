<?php

namespace App\Exceptions;

/** 404: el recurso pedido no existe. */
class RecursoNoEncontradoException extends ApiException
{
    public static function carta(int $id): self
    {
        return new self(404, 'CARTA_NO_ENCONTRADA', "No existe una carta con id {$id}.");
    }
}
