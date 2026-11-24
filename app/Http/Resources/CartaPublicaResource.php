<?php

namespace App\Http\Resources;

use App\Support\Privacidad;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vista pública (sin token de ELFO_JEFE): los remitentes son menores, así que
 * solo se muestra la inicial del apellido y no se muestra la edad.
 *
 * @mixin \App\Models\Carta
 */
class CartaPublicaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'remitente' => Privacidad::nombreConInicial($this->remitente),
            'ciudad' => $this->ciudad,
            'regalos' => $this->regalos,
            'estado' => $this->estado->value,
            'comportamiento' => $this->comportamiento?->value,
            'fechaRecepcion' => $this->fecha_recepcion->toIso8601ZuluString(),
        ];
    }
}
