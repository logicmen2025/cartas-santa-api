<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación completa de una carta (respuestas a quien la creó o editó,
 * y consultas hechas con token de ELFO_JEFE).
 *
 * @mixin \App\Models\Carta
 */
class CartaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'remitente' => $this->remitente,
            'edad' => $this->edad,
            'ciudad' => $this->ciudad,
            'regalos' => $this->regalos,
            'estado' => $this->estado->value,
            'comportamiento' => $this->comportamiento?->value,
            'fechaRecepcion' => $this->fecha_recepcion->toIso8601ZuluString(),
        ];
    }
}
