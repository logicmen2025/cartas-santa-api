<?php

namespace App\Models;

use App\Enums\Rol;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Usuario interno del Taller (elfos). Solo se usa para emitir el JWT.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property Rol $rol
 */
class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => Rol::class,
        ];
    }
}
