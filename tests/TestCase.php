<?php

namespace Tests;

use App\Enums\Rol;
use App\Models\Carta;
use App\Models\User;
use App\Services\Auth\JwtService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected const URL_SOAP = 'http://lista-soap.test/ListaBuenosTraviesosService';

    protected function setUp(): void
    {
        parent::setUp();

        // Valores solo para pruebas: nada de esto vive en el repositorio como secreto
        config([
            'polonorte.jwt.secret' => Str::random(64),
            'polonorte.demo_password' => 'clave-de-prueba',
            'polonorte.lista_soap.url' => self::URL_SOAP,
            'polonorte.lista_soap.namespace' => 'http://polonorte.beatlech/lista',
            'polonorte.lista_soap.timeout' => 3,
            'polonorte.permitir_fecha_simulada' => true,
        ]);
        $this->app->forgetInstance(JwtService::class);

        // Por defecto ninguna prueba sale a la red
        Http::preventStrayRequests();
    }

    protected function tokenPara(Rol $rol): string
    {
        $usuario = User::query()->create([
            'name' => 'Usuario '.$rol->value,
            'email' => strtolower($rol->value).'.'.Str::random(6).'@polonorte.test',
            'password' => 'clave-de-prueba',
            'rol' => $rol,
        ]);

        return app(JwtService::class)->emitir($usuario)['token'];
    }

    /** @return array<string, string> */
    protected function conToken(Rol $rol): array
    {
        return ['Authorization' => 'Bearer '.$this->tokenPara($rol)];
    }

    /** @param array<string, mixed> $cambios */
    protected function datosCarta(array $cambios = []): array
    {
        return array_merge([
            'remitente' => 'Valentina Gómez',
            'edad' => 8,
            'ciudad' => 'Guayaquil',
            'regalos' => ['Bicicleta', 'Libro de dinosaurios'],
        ], $cambios);
    }

    /** Crea una carta directamente en BD (atajo para preparar los casos de prueba). */
    protected function cartaEn(string $estado, array $cambios = []): Carta
    {
        return Carta::query()->create(array_merge($this->datosCarta(), [
            'estado' => $estado,
            'fecha_recepcion' => now(),
        ], $cambios));
    }

    protected function respuestaSoap(string $resultado): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:pn="http://polonorte.beatlech/lista">
  <soapenv:Body>
    <pn:ConsultarComportamientoResponse>
      <pn:resultado>{$resultado}</pn:resultado>
    </pn:ConsultarComportamientoResponse>
  </soapenv:Body>
</soapenv:Envelope>
XML;
    }

    protected function faultSoap(string $texto = 'Lista en mantenimiento'): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <soap:Fault>
      <faultcode>soap:Server</faultcode>
      <faultstring>{$texto}</faultstring>
    </soap:Fault>
  </soap:Body>
</soap:Envelope>
XML;
    }

    protected function soapResponde(string $resultado): void
    {
        Http::fake([self::URL_SOAP => Http::response($this->respuestaSoap($resultado), 200, ['Content-Type' => 'text/xml'])]);
    }

    protected function soapRespondeFault(): void
    {
        Http::fake([self::URL_SOAP => Http::response($this->faultSoap(), 500, ['Content-Type' => 'text/xml'])]);
    }

    protected function soapNoResponde(): void
    {
        Http::fake([self::URL_SOAP => Http::failedConnection('cURL error 28: Operation timed out after 3001 milliseconds with 0 bytes received')]);
    }

    /** Verifica el formato único de error. */
    protected function assertFormatoDeError($respuesta, int $estado, string $codigo): void
    {
        $respuesta->assertStatus($estado)
            ->assertJsonStructure(['codigo', 'mensaje', 'detalles', 'timestamp', 'ruta'])
            ->assertJsonPath('codigo', $codigo);

        $json = $respuesta->json();
        $this->assertSame(['codigo', 'mensaje', 'detalles', 'timestamp', 'ruta'], array_keys($json));
        $this->assertIsArray($json['detalles']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $json['timestamp']);
        $this->assertStringStartsWith('/api/', $json['ruta']);
    }
}
