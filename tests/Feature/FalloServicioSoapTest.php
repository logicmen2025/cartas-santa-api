<?php

namespace Tests\Feature;

use App\Enums\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Requisito 5 (Escenario A): si el SOAP responde Fault o no responde en 3 s,
 * la carta queda RECIBIDA con PENDIENTE_VERIFICACION y la API responde 503.
 * Nunca 500.
 */
class FalloServicioSoapTest extends TestCase
{
    use RefreshDatabase;

    private function aprobar(int $id)
    {
        return $this->patchJson("/api/cartas/{$id}/estado", ['estado' => 'APROBADA'], $this->conToken(Rol::ELFO_JEFE));
    }

    public function test_fault_del_soap_deja_la_carta_recibida_pendiente_y_responde_503(): void
    {
        $this->soapRespondeFault();
        $carta = $this->cartaEn('RECIBIDA', ['remitente' => 'Grinch']);

        $respuesta = $this->aprobar($carta->id);

        $this->assertFormatoDeError($respuesta, 503, 'SERVICIO_EXTERNO_NO_DISPONIBLE');
        $respuesta->assertJsonPath('detalles.0.motivo', 'FAULT')
            ->assertJsonPath('detalles.0.estadoCarta', 'RECIBIDA')
            ->assertJsonPath('detalles.0.comportamiento', 'PENDIENTE_VERIFICACION')
            ->assertHeader('Retry-After');
        $this->assertStringContainsString('Lista en mantenimiento', $respuesta->json('detalles.0.descripcion'));

        $this->assertDatabaseHas('cartas', [
            'id' => $carta->id,
            'estado' => 'RECIBIDA',
            'comportamiento' => 'PENDIENTE_VERIFICACION',
        ]);
    }

    public function test_timeout_del_soap_deja_la_carta_recibida_pendiente_y_responde_503(): void
    {
        $this->soapNoResponde();
        $carta = $this->cartaEn('RECIBIDA');

        $respuesta = $this->aprobar($carta->id);

        $this->assertFormatoDeError($respuesta, 503, 'SERVICIO_EXTERNO_NO_DISPONIBLE');
        $respuesta->assertJsonPath('detalles.0.motivo', 'TIMEOUT');
        $this->assertDatabaseHas('cartas', ['id' => $carta->id, 'estado' => 'RECIBIDA', 'comportamiento' => 'PENDIENTE_VERIFICACION']);
    }

    public function test_servicio_apagado_tambien_responde_503(): void
    {
        Http::fake([self::URL_SOAP => Http::failedConnection('cURL error 7: Failed to connect to 127.0.0.1 port 8001: Connection refused')]);
        $carta = $this->cartaEn('RECIBIDA');

        $this->assertFormatoDeError($this->aprobar($carta->id), 503, 'SERVICIO_EXTERNO_NO_DISPONIBLE');
        $this->assertDatabaseHas('cartas', ['id' => $carta->id, 'estado' => 'RECIBIDA', 'comportamiento' => 'PENDIENTE_VERIFICACION']);
    }

    public function test_respuesta_basura_o_error_http_sin_fault_tambien_responde_503(): void
    {
        $carta = $this->cartaEn('RECIBIDA');

        Http::fake([self::URL_SOAP => Http::sequence()
            ->push('<html>502 Bad Gateway</html', 502)
            ->push($this->respuestaSoap('TALVEZ'), 200)]);

        $this->assertFormatoDeError($this->aprobar($carta->id), 503, 'SERVICIO_EXTERNO_NO_DISPONIBLE');
        $this->assertFormatoDeError($this->aprobar($carta->id), 503, 'SERVICIO_EXTERNO_NO_DISPONIBLE');
        Http::assertSentCount(2);
    }

    public function test_cuando_el_servicio_vuelve_la_misma_carta_se_puede_aprobar(): void
    {
        $carta = $this->cartaEn('RECIBIDA');

        Http::fake([self::URL_SOAP => Http::sequence()
            ->pushFailedConnection('cURL error 28: Operation timed out after 3001 milliseconds')
            ->push($this->respuestaSoap('BUENO'), 200)]);

        $this->aprobar($carta->id)->assertStatus(503);
        $this->assertDatabaseHas('cartas', ['id' => $carta->id, 'estado' => 'RECIBIDA', 'comportamiento' => 'PENDIENTE_VERIFICACION']);

        $this->aprobar($carta->id)
            ->assertOk()
            ->assertJsonPath('data.estado', 'APROBADA')
            ->assertJsonPath('data.comportamiento', 'BUENO');
    }
}
