<?php

namespace Tests\Feature;

use App\Enums\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReglasDeNegocioTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Regla 1: 24 de diciembre (422) ----------

    public function test_no_acepta_cartas_despues_del_24_de_diciembre(): void
    {
        $this->travelTo(Carbon::create(2026, 12, 25, 10, 0, 0, 'America/Guayaquil'));

        $respuesta = $this->postJson('/api/cartas', $this->datosCarta());

        $this->assertFormatoDeError($respuesta, 422, 'FUERA_DE_PLAZO');
        $this->assertDatabaseCount('cartas', 0);
    }

    public function test_el_24_de_diciembre_a_las_23_59_todavia_acepta(): void
    {
        $this->travelTo(Carbon::create(2026, 12, 24, 23, 59, 59, 'America/Guayaquil'));

        $this->postJson('/api/cartas', $this->datosCarta())->assertCreated();
    }

    public function test_el_header_x_fecha_simulada_permite_demostrar_la_regla(): void
    {
        $respuesta = $this->postJson('/api/cartas', $this->datosCarta(), ['X-Fecha-Simulada' => '2026-12-26']);

        $this->assertFormatoDeError($respuesta, 422, 'FUERA_DE_PLAZO');
        $respuesta->assertHeader('X-Fecha-Simulada-Aplicada');
    }

    public function test_el_header_x_fecha_simulada_se_ignora_si_esta_desactivado(): void
    {
        config(['polonorte.permitir_fecha_simulada' => false]);

        $this->postJson('/api/cartas', $this->datosCarta(), ['X-Fecha-Simulada' => '2026-12-26'])->assertCreated();
    }

    // ---------- Regla 3: transiciones (409) ----------

    public function test_una_transicion_fuera_del_mapa_responde_409(): void
    {
        $carta = $this->cartaEn('RECIBIDA');

        $respuesta = $this->patchJson("/api/cartas/{$carta->id}/estado", ['estado' => 'EN_PRODUCCION'], $this->conToken(Rol::ELFO_JEFE));

        $this->assertFormatoDeError($respuesta, 409, 'TRANSICION_INVALIDA');
        $respuesta->assertJsonPath('mensaje', 'No se puede pasar de RECIBIDA a EN_PRODUCCION');
        $this->assertDatabaseHas('cartas', ['id' => $carta->id, 'estado' => 'RECIBIDA']);
    }

    public function test_flujo_completo_hasta_entregada_y_luego_ya_no_se_puede_tocar(): void
    {
        $this->soapResponde('BUENO');
        $cabeceras = $this->conToken(Rol::ELFO_JEFE);
        $id = $this->postJson('/api/cartas', $this->datosCarta())->json('data.id');

        $this->patchJson("/api/cartas/{$id}/estado", ['estado' => 'APROBADA'], $cabeceras)
            ->assertOk()->assertJsonPath('data.estado', 'APROBADA')->assertJsonPath('data.comportamiento', 'BUENO');
        $this->patchJson("/api/cartas/{$id}/estado", ['estado' => 'EN_PRODUCCION'], $cabeceras)
            ->assertOk()->assertJsonPath('data.estado', 'EN_PRODUCCION');
        $this->patchJson("/api/cartas/{$id}/estado", ['estado' => 'ENTREGADA'], $cabeceras)
            ->assertOk()->assertJsonPath('data.estado', 'ENTREGADA');

        // Regla 3 + 5: desde ENTREGADA no hay transiciones
        $r = $this->patchJson("/api/cartas/{$id}/estado", ['estado' => 'RECIBIDA'], $cabeceras);
        $this->assertFormatoDeError($r, 409, 'TRANSICION_INVALIDA');
        $r->assertJsonPath('mensaje', 'No se puede pasar de ENTREGADA a RECIBIDA');

        // Regla 5: ni editar ni eliminar
        $this->assertFormatoDeError($this->putJson("/api/cartas/{$id}", $this->datosCarta()), 409, 'CARTA_ENTREGADA');
        $this->assertFormatoDeError($this->deleteJson("/api/cartas/{$id}", [], $cabeceras), 409, 'CARTA_ENTREGADA');
        $this->assertDatabaseHas('cartas', ['id' => $id, 'estado' => 'ENTREGADA']);
    }

    public function test_rechazar_manualmente_no_consulta_la_lista(): void
    {
        Http::fake();
        $carta = $this->cartaEn('RECIBIDA');

        $this->patchJson("/api/cartas/{$carta->id}/estado", ['estado' => 'RECHAZADA'], $this->conToken(Rol::ELFO_JEFE))
            ->assertOk()->assertJsonPath('data.estado', 'RECHAZADA');

        Http::assertNothingSent();
    }

    // ---------- Regla 4: consulta SOAP al aprobar ----------

    public function test_si_la_lista_dice_travieso_la_carta_pasa_a_rechazada(): void
    {
        $this->soapResponde('TRAVIESO');
        $carta = $this->cartaEn('RECIBIDA', ['remitente' => 'Pedro Travieso']);

        $this->patchJson("/api/cartas/{$carta->id}/estado", ['estado' => 'APROBADA'], $this->conToken(Rol::ELFO_JEFE))
            ->assertOk()
            ->assertJsonPath('data.estado', 'RECHAZADA')
            ->assertJsonPath('data.comportamiento', 'TRAVIESO');

        Http::assertSent(fn ($request) => str_contains($request->body(), '<pn:nombre>Pedro Travieso</pn:nombre>')
            && str_contains($request->body(), '<pn:ciudad>Guayaquil</pn:ciudad>'));
    }

    // ---------- PUT solo en RECIBIDA ----------

    public function test_put_en_recibida_actualiza(): void
    {
        $carta = $this->cartaEn('RECIBIDA');

        $this->putJson("/api/cartas/{$carta->id}", $this->datosCarta(['regalos' => ['Patines']]))
            ->assertOk()
            ->assertJsonPath('data.regalos', ['Patines'])
            ->assertJsonPath('data.estado', 'RECIBIDA');
    }

    public function test_put_en_otro_estado_responde_409(): void
    {
        $carta = $this->cartaEn('APROBADA');

        $this->assertFormatoDeError($this->putJson("/api/cartas/{$carta->id}", $this->datosCarta()), 409, 'CARTA_NO_EDITABLE');
    }

    public function test_put_de_carta_inexistente_responde_404(): void
    {
        $this->assertFormatoDeError($this->putJson('/api/cartas/424242', $this->datosCarta()), 404, 'CARTA_NO_ENCONTRADA');
    }

    // ---------- DELETE ----------

    public function test_elfo_jefe_elimina_una_carta_no_entregada(): void
    {
        $carta = $this->cartaEn('RECHAZADA');

        $this->deleteJson("/api/cartas/{$carta->id}", [], $this->conToken(Rol::ELFO_JEFE))->assertNoContent();
        $this->assertDatabaseMissing('cartas', ['id' => $carta->id]);
        $this->assertFormatoDeError($this->getJson("/api/cartas/{$carta->id}"), 404, 'CARTA_NO_ENCONTRADA');
    }
}
