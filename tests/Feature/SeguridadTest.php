<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_sin_token_responde_401(): void
    {
        $carta = $this->cartaEn('RECIBIDA');

        $respuesta = $this->deleteJson("/api/cartas/{$carta->id}");

        $this->assertFormatoDeError($respuesta, 401, 'TOKEN_REQUERIDO');
        $respuesta->assertHeader('WWW-Authenticate', 'Bearer');
        $this->assertDatabaseHas('cartas', ['id' => $carta->id]);
    }

    public function test_patch_sin_token_responde_401(): void
    {
        $carta = $this->cartaEn('RECIBIDA');

        $this->assertFormatoDeError($this->patchJson("/api/cartas/{$carta->id}/estado", ['estado' => 'APROBADA']), 401, 'TOKEN_REQUERIDO');
    }

    public function test_token_basura_responde_401(): void
    {
        $carta = $this->cartaEn('RECIBIDA');

        $this->assertFormatoDeError(
            $this->deleteJson("/api/cartas/{$carta->id}", [], ['Authorization' => 'Bearer esto.no.sirve']),
            401,
            'TOKEN_INVALIDO',
        );
    }

    public function test_token_firmado_con_otro_secreto_responde_401(): void
    {
        $carta = $this->cartaEn('RECIBIDA');
        $falso = JWT::encode(['iss' => 'cartas-santa-api', 'rol' => 'ELFO_JEFE', 'exp' => time() + 600], str_repeat('x', 64), 'HS256');

        $this->assertFormatoDeError(
            $this->deleteJson("/api/cartas/{$carta->id}", [], ['Authorization' => "Bearer {$falso}"]),
            401,
            'TOKEN_INVALIDO',
        );
    }

    public function test_token_expirado_responde_401(): void
    {
        $carta = $this->cartaEn('RECIBIDA');
        $vencido = JWT::encode(
            ['iss' => 'cartas-santa-api', 'rol' => 'ELFO_JEFE', 'iat' => time() - 7200, 'exp' => time() - 3600],
            config('polonorte.jwt.secret'),
            'HS256',
        );

        $this->assertFormatoDeError(
            $this->deleteJson("/api/cartas/{$carta->id}", [], ['Authorization' => "Bearer {$vencido}"]),
            401,
            'TOKEN_EXPIRADO',
        );
    }

    public function test_rol_elfo_responde_403_en_delete_y_en_patch(): void
    {
        $carta = $this->cartaEn('RECIBIDA');
        $cabeceras = $this->conToken(Rol::ELFO);

        $this->assertFormatoDeError($this->deleteJson("/api/cartas/{$carta->id}", [], $cabeceras), 403, 'ROL_INSUFICIENTE');
        $this->assertFormatoDeError($this->patchJson("/api/cartas/{$carta->id}/estado", ['estado' => 'RECHAZADA'], $cabeceras), 403, 'ROL_INSUFICIENTE');

        $this->assertDatabaseHas('cartas', ['id' => $carta->id, 'estado' => 'RECIBIDA']);
    }

    public function test_login_correcto_entrega_un_token_que_sirve(): void
    {
        User::query()->create(['name' => 'Jefe', 'email' => 'jefe@polonorte.test', 'password' => 'clave-de-prueba', 'rol' => Rol::ELFO_JEFE]);
        $carta = $this->cartaEn('RECIBIDA');

        $token = $this->postJson('/api/auth/login', ['email' => 'jefe@polonorte.test', 'password' => 'clave-de-prueba'])
            ->assertOk()
            ->assertJsonPath('data.tipo', 'Bearer')
            ->assertJsonPath('data.usuario.rol', 'ELFO_JEFE')
            ->json('data.token');

        $this->deleteJson("/api/cartas/{$carta->id}", [], ['Authorization' => "Bearer {$token}"])->assertNoContent();
    }

    public function test_login_con_clave_incorrecta_responde_401(): void
    {
        User::query()->create(['name' => 'Jefe', 'email' => 'jefe@polonorte.test', 'password' => 'clave-de-prueba', 'rol' => Rol::ELFO_JEFE]);

        $this->assertFormatoDeError(
            $this->postJson('/api/auth/login', ['email' => 'jefe@polonorte.test', 'password' => 'otra']),
            401,
            'CREDENCIALES_INVALIDAS',
        );
    }

    public function test_login_sin_datos_responde_400(): void
    {
        $this->assertFormatoDeError($this->postJson('/api/auth/login', []), 400, 'VALIDACION_FALLIDA');
    }

    public function test_los_endpoints_publicos_no_piden_token(): void
    {
        $carta = $this->cartaEn('RECIBIDA');

        $this->getJson('/api/cartas')->assertOk();
        $this->getJson("/api/cartas/{$carta->id}")->assertOk();
        $this->postJson('/api/cartas', $this->datosCarta())->assertCreated();
        $this->putJson("/api/cartas/{$carta->id}", $this->datosCarta())->assertOk();
    }
}
