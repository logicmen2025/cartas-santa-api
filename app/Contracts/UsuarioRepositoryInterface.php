<?php

namespace App\Contracts;

use App\Models\User;

interface UsuarioRepositoryInterface
{
    public function buscarPorEmail(string $email): ?User;
}
