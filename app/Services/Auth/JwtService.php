<?php

namespace App\Services\Auth;

use App\Enums\Rol;
use App\Exceptions\ConfiguracionInvalidaException;
use App\Exceptions\NoAutenticadoException;
use App\Models\User;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Throwable;

/**
 * Emite y valida tokens JWT firmados con HS256.
 * El secreto viene de JWT_SECRET (.env) y nunca está en el repositorio.
 */
class JwtService
{
    private const ALGORITMO = 'HS256';

    public function __construct(
        private readonly ?string $secreto,
        private readonly int $ttlSegundos,
        private readonly string $emisor,
    ) {}

    /** @return array{token: string, expiraEn: int} */
    public function emitir(User $usuario): array
    {
        $ahora = time();

        $payload = [
            'iss' => $this->emisor,
            'sub' => (string) $usuario->id,
            'nombre' => $usuario->name,
            'email' => $usuario->email,
            'rol' => $usuario->rol->value,
            'iat' => $ahora,
            'nbf' => $ahora,
            'exp' => $ahora + $this->ttlSegundos,
        ];

        return [
            'token' => JWT::encode($payload, $this->secreto(), self::ALGORITMO),
            'expiraEn' => $this->ttlSegundos,
        ];
    }

    /**
     * Verifica firma, expiración y emisor. Devuelve los claims del token.
     *
     * @return array<string, mixed>
     *
     * @throws NoAutenticadoException
     */
    public function validar(string $token): array
    {
        $secreto = $this->secreto();

        try {
            $claims = (array) JWT::decode($token, new Key($secreto, self::ALGORITMO));
        } catch (ExpiredException) {
            throw NoAutenticadoException::tokenExpirado();
        } catch (Throwable) {
            throw NoAutenticadoException::tokenInvalido();
        }

        if (($claims['iss'] ?? null) !== $this->emisor || Rol::tryFrom((string) ($claims['rol'] ?? '')) === null) {
            throw NoAutenticadoException::tokenInvalido();
        }

        return $claims;
    }

    /** Acepta "Authorization: Bearer <token>" y también el token solo. */
    public static function tokenDeLaPeticion(Request $request): ?string
    {
        $cabecera = trim((string) $request->header('Authorization', ''));
        if ($cabecera === '') {
            return null;
        }

        if (preg_match('/^Bearer\s+(.+)$/i', $cabecera, $m)) {
            $cabecera = trim($m[1]);
        }

        return $cabecera !== '' ? $cabecera : null;
    }

    private function secreto(): string
    {
        if (! is_string($this->secreto) || strlen($this->secreto) < 32) {
            throw new ConfiguracionInvalidaException(
                'JWT_SECRET no está configurado o tiene menos de 32 caracteres. Ejecuta: php artisan jwt:secret'
            );
        }

        return $this->secreto;
    }
}
