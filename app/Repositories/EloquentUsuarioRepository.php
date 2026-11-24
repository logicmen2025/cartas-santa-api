<?php

namespace App\Repositories;

use App\Contracts\UsuarioRepositoryInterface;
use App\Models\User;

class EloquentUsuarioRepository implements UsuarioRepositoryInterface
{
    public function buscarPorEmail(string $email): ?User
    {
        return User::query()->where('email', mb_strtolower(trim($email)))->first();
    }
}
