<?php

namespace Tests\Feature;

use App\Enums\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrearYConsultarCartasTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_una_carta_valida_y_responde_201(): void
    {
        $respuesta = $this->postJson('/api/cartas', $this->datosCarta());

        $respuesta->assertCreated()
            ->assertHeader('Location')
            ->assertJsonPath('data.remitente', 'Valentina Gómez')
            ->assertJsonPath('data.edad', 8)
            ->assertJsonPath('data.estado', 'RECIBIDA')
            ->assertJsonPath('data.comportamiento', null)
            ->assertJsonStructure(['data' => ['id', 'remitente', 'edad', 'ciudad', 'regalos', 'estado', 'comportamiento', 'fechaRecepcion']]);

        $this->assertDatabaseHas('cartas', ['remitente' => 'Valentina Gómez', 'estado' => 'RECIBIDA']);
    }

    public function test_una_carta_nueva_siempre_nace_recibida_aunque_el_cliente_mande_otra_cosa(): void
    {
        $respuesta = $this->postJson('/api/cartas', $this->datosCarta([
            'estado' => 'ENTREGADA',
            'comportamiento' => 'BUENO',
            'id' => 5000,
            'fechaRecepcion' => '2020-01-01T00:00:00Z',
        ]));

        $respuesta->assertCreated()
            ->assertJsonPath('data.estado', 'RECIBIDA')
            ->assertJsonPath('data.comportamiento', null);
        $this->assertNotSame(5000, $respuesta->json('data.id'));
        $this->assertNotSame('2020-01-01T00:00:00Z', $respuesta->json('data.fechaRecepcion'));
    }

    public function test_consulta_el_detalle_de_una_carta_existente(): void
    {
        $carta = $this->cartaEn('RECIBIDA');

        $this->getJson("/api/cartas/{$carta->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $carta->id);
    }

    public function test_detalle_de_carta_inexistente_responde_404(): void
    {
        $this->assertFormatoDeError($this->getJson('/api/cartas/123456'), 404, 'CARTA_NO_ENCONTRADA');
    }

    public function test_el_listado_publico_muestra_solo_la_inicial_del_apellido_y_oculta_la_edad(): void
    {
        $this->cartaEn('RECIBIDA', ['remitente' => 'Valentina Gómez']);

        $respuesta = $this->getJson('/api/cartas')->assertOk();

        $this->assertSame('Valentina G.', $respuesta->json('data.0.remitente'));
        $this->assertArrayNotHasKey('edad', $respuesta->json('data.0'));
        $respuesta->assertJsonPath('meta.total', 1);
    }

    public function test_con_token_de_elfo_jefe_el_listado_muestra_los_datos_completos(): void
    {
        $this->cartaEn('RECIBIDA', ['remitente' => 'Valentina Gómez', 'edad' => 8]);

        $this->getJson('/api/cartas', $this->conToken(Rol::ELFO_JEFE))
            ->assertOk()
            ->assertJsonPath('data.0.remitente', 'Valentina Gómez')
            ->assertJsonPath('data.0.edad', 8);
    }

    public function test_filtra_por_estado_y_por_ciudad_sin_distinguir_mayusculas(): void
    {
        $this->cartaEn('RECIBIDA', ['ciudad' => 'Quito']);
        $this->cartaEn('APROBADA', ['ciudad' => 'Quito']);
        $this->cartaEn('RECIBIDA', ['ciudad' => 'Cuenca']);

        $this->getJson('/api/cartas?estado=RECIBIDA')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/cartas?ciudad=quito')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/cartas?estado=aprobada&ciudad=Quito')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/cartas?ciudad=Loja')->assertOk()->assertJsonCount(0, 'data');
    }
}
