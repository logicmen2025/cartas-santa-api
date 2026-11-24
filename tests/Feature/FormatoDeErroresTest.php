<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormatoDeErroresTest extends TestCase
{
    use RefreshDatabase;

    public function test_ruta_inexistente_usa_el_formato_estandar(): void
    {
        $respuesta = $this->getJson('/api/no-existe');

        $this->assertFormatoDeError($respuesta, 404, 'RUTA_NO_ENCONTRADA');
        $respuesta->assertJsonPath('ruta', '/api/no-existe');
    }

    public function test_metodo_no_permitido_usa_el_formato_estandar(): void
    {
        $carta = $this->cartaEn('RECIBIDA');

        $this->assertFormatoDeError($this->postJson("/api/cartas/{$carta->id}"), 405, 'METODO_NO_PERMITIDO');
    }

    public function test_aunque_el_cliente_no_pida_json_la_api_responde_json(): void
    {
        $respuesta = $this->get('/api/cartas/987654', ['Accept' => 'text/html']);

        $respuesta->assertStatus(404)->assertHeader('Content-Type', 'application/json');
        $this->assertSame('CARTA_NO_ENCONTRADA', $respuesta->json('codigo'));
    }

    public function test_la_ruta_del_error_es_la_de_la_peticion(): void
    {
        $this->getJson('/api/cartas/31337')->assertJsonPath('ruta', '/api/cartas/31337');
    }
}
