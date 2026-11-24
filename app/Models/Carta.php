<?php

namespace App\Models;

use App\Enums\Comportamiento;
use App\Enums\EstadoCarta;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $remitente
 * @property int $edad
 * @property string $ciudad
 * @property list<string> $regalos
 * @property EstadoCarta $estado
 * @property Comportamiento|null $comportamiento
 * @property \Illuminate\Support\Carbon $fecha_recepcion
 */
class Carta extends Model
{
    protected $table = 'cartas';

    protected $fillable = [
        'remitente',
        'edad',
        'ciudad',
        'regalos',
        'estado',
        'comportamiento',
        'fecha_recepcion',
    ];

    protected function casts(): array
    {
        return [
            'edad' => 'integer',
            'regalos' => 'array',
            'estado' => EstadoCarta::class,
            'comportamiento' => Comportamiento::class,
            'fecha_recepcion' => 'datetime',
        ];
    }
}
