<?php

namespace Tests\Feature;

use App\Enums\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ValidacionesTest extends TestCase
{
    use RefreshDatabase;

    public static function datosInvalidos(): array
    {
        return [
            'sin remitente' => [['remitente' => null], 'remitente'],
            'remitente de 1 caracter' => [['remitente' => 'A'], 'remitente'],
            'remitente de 61 caracteres' => [['remitente' => str_repeat('a', 61)], 'remitente'],
            'edad 0' => [['edad' => 0], 'edad'],
            'edad 18' => [['edad' => 18], 'edad'],
            'edad como texto' => [['edad' => 'ocho'], 'edad'],
            'edad booleana' => [['edad' => true], 'edad'],
            'sin ciudad' => [['ciudad' => null], 'ciudad'],
            'regalos vacios' => [['regalos' => []], 'regalos'],
            'mas de 5 regalos' => [['regalos' => ['a', 'b', 'c', 'd', 'e', 'f']], 'regalos'],
            'regalos duplicados' => [['regalos' => ['Bicicleta', 'bicicleta']], 'regalos.0'],
            'regalos como objeto' => [['regalos' => ['x' => 'Bicicleta']], 'regalos'],
            'regalo vacio' => [['regalos' => ['Bicicleta', '']], 'regalos.1'],
        ];
    }

    #[DataProvider('datosInvalidos')]
    public function test_post_con_datos_invalidos_responde_400(array $cambios, string $campo): void
    {
        $respuesta = $this->postJson('/api/cartas', $this->datosCarta($cambios));

        $this->assertFormatoDeError($respuesta, 400, 'VALIDACION_FALLIDA');
        $this->assertContains($campo, array_column($respuesta->json('detalles'), 'campo'));
        $this->assertDatabaseCount('cartas', 0);
    }

    public function test_acepta_los_limites_validos(): void
    {
        $this->postJson('/api/cartas', $this->datosCarta([
            'remitente' => 'Al',
            'edad' => 17,
            'regalos' => ['a', 'b', 'c', 'd', 'e'],
        ]))->assertCreated();

        $this->postJson('/api/cartas', $this->datosCarta([
            'remitente' => str_repeat('a', 60),
            'edad' => 1,
            'regalos' => ['uno'],
        ]))->assertCreated();
    }

    public function test_json_mal_formado_responde_400(): void
    {
        $respuesta = $this->call('POST', '/api/cartas', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], '{"remitente": "Ana", ');

        $this->assertFormatoDeError($respuesta, 400, 'JSON_MALFORMADO');
    }

    public function test_put_tambien_valida_el_formato(): void
    {
        $carta = $this->cartaEn('RECIBIDA');

        $this->assertFormatoDeError(
            $this->putJson("/api/cartas/{$carta->id}", $this->datosCarta(['edad' => 40])),
            400,
            'VALIDACION_FALLIDA',
        );
    }

    public function test_filtro_de_estado_invalido_responde_400(): void
    {
        $this->assertFormatoDeError($this->getJson('/api/cartas?estado=PERDIDA'), 400, 'VALIDACION_FALLIDA');
    }

    public function test_cambio_a_un_estado_que_no_existe_responde_400(): void
    {
        $carta = $this->cartaEn('RECIBIDA');

        $this->assertFormatoDeError(
            $this->patchJson("/api/cartas/{$carta->id}/estado", ['estado' => 'VOLANDO'], $this->conToken(Rol::ELFO_JEFE)),
            400,
            'VALIDACION_FALLIDA',
        );
    }
}
