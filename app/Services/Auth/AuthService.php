<?php

namespace App\Services\Auth;

use App\Contracts\UsuarioRepositoryInterface;
use App\Exceptions\NoAutenticadoException;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly JwtService $jwt,
    ) {}

    /** @return array<string, mixed> */
    public function iniciarSesion(string $email, string $password): array
    {
        $usuario = $this->usuarios->buscarPorEmail($email);

        if ($usuario === null || ! Hash::check($password, $usuario->password)) {
            throw NoAutenticadoException::credencialesInvalidas();
        }

        $emitido = $this->jwt->emitir($usuario);

        return [
            'token' => $emitido['token'],
            'tipo' => 'Bearer',
            'expiraEn' => $emitido['expiraEn'],
            'usuario' => [
                'nombre' => $usuario->name,
                'email' => $usuario->email,
                'rol' => $usuario->rol->value,
            ],
        ];
    }
}
