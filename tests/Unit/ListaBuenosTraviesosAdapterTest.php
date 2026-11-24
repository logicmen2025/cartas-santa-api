<?php

namespace Tests\Unit;

use App\Enums\Comportamiento;
use App\Exceptions\ListaNoDisponibleException;
use App\Integrations\Soap\ListaBuenosTraviesosAdapter;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ListaBuenosTraviesosAdapterTest extends TestCase
{
    private function adaptador(): ListaBuenosTraviesosAdapter
    {
        return new ListaBuenosTraviesosAdapter(self::URL_SOAP, 'http://polonorte.beatlech/lista', 'ConsultarComportamiento', 3);
    }

    private function motivoDelFallo(callable $accion): string
    {
        try {
            $accion();
        } catch (ListaNoDisponibleException $e) {
            return $e->motivo;
        }
        $this->fail('Se esperaba ListaNoDisponibleException');
    }

    public function test_arma_el_sobre_soap_como_en_el_enunciado(): void
    {
        $xml = $this->adaptador()->construirSolicitud('Valentina', 'Guayaquil');

        $doc = new \DOMDocument;
        $this->assertTrue($doc->loadXML($xml));
        $xp = new \DOMXPath($doc);
        $xp->registerNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xp->registerNamespace('pn', 'http://polonorte.beatlech/lista');

        $this->assertSame('Valentina', $xp->evaluate('string(/soapenv:Envelope/soapenv:Body/pn:ConsultarComportamientoRequest/pn:nombre)'));
        $this->assertSame('Guayaquil', $xp->evaluate('string(/soapenv:Envelope/soapenv:Body/pn:ConsultarComportamientoRequest/pn:ciudad)'));
    }

    public function test_escapa_caracteres_especiales_del_nombre(): void
    {
        $xml = $this->adaptador()->construirSolicitud('Ana & <Luis>', "Pto. O'Brien");

        $doc = new \DOMDocument;
        $this->assertTrue($doc->loadXML($xml));
        $this->assertStringContainsString('Ana &amp; &lt;Luis&gt;', $xml);
    }

    public function test_envia_post_xml_con_soapaction_y_traduce_bueno(): void
    {
        $this->soapResponde('BUENO');

        $this->assertSame(Comportamiento::BUENO, $this->adaptador()->consultarComportamiento('Valentina', 'Guayaquil'));

        Http::assertSent(fn (Request $r) => $r->url() === self::URL_SOAP
            && $r->method() === 'POST'
            && str_contains($r->header('Content-Type')[0] ?? '', 'text/xml')
            && ($r->header('SOAPAction')[0] ?? null) === '"ConsultarComportamiento"');
    }

    public function test_traduce_travieso(): void
    {
        $this->soapResponde('TRAVIESO');

        $this->assertSame(Comportamiento::TRAVIESO, $this->adaptador()->consultarComportamiento('Pedro', 'Quito'));
    }

    public function test_fault_se_convierte_en_excepcion_de_dominio(): void
    {
        $this->soapRespondeFault();

        try {
            $this->adaptador()->consultarComportamiento('Grinch', 'Quito');
            $this->fail('Se esperaba ListaNoDisponibleException');
        } catch (ListaNoDisponibleException $e) {
            $this->assertSame(ListaNoDisponibleException::FAULT, $e->motivo);
            $this->assertStringContainsString('soap:Server', $e->getMessage());
            $this->assertStringContainsString('Lista en mantenimiento', $e->getMessage());
        }
    }

    public function test_entiende_un_fault_soap_1_2(): void
    {
        Http::fake([self::URL_SOAP => Http::response(<<<'XML'
<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope">
  <env:Body><env:Fault>
    <env:Code><env:Value>env:Receiver</env:Value></env:Code>
    <env:Reason><env:Text xml:lang="es">Lista en mantenimiento</env:Text></env:Reason>
  </env:Fault></env:Body>
</env:Envelope>
XML, 500)]);

        $this->assertSame(ListaNoDisponibleException::FAULT, $this->motivoDelFallo(fn () => $this->adaptador()->consultarComportamiento('Grinch', 'Quito')));
    }

    public function test_timeout_se_convierte_en_excepcion_de_dominio(): void
    {
        $this->soapNoResponde();

        $this->assertSame(ListaNoDisponibleException::TIMEOUT, $this->motivoDelFallo(fn () => $this->adaptador()->consultarComportamiento('Ana', 'Quito')));
    }

    public function test_rechaza_respuestas_con_doctype(): void
    {
        Http::fake([self::URL_SOAP => Http::response('<?xml version="1.0"?><!DOCTYPE x [<!ENTITY e SYSTEM "file:///etc/passwd">]><x>&e;</x>', 200)]);

        $this->assertSame(ListaNoDisponibleException::RESPUESTA_INVALIDA, $this->motivoDelFallo(fn () => $this->adaptador()->consultarComportamiento('Ana', 'Quito')));
    }

    public function test_resultado_desconocido_es_respuesta_invalida(): void
    {
        $this->soapResponde('MAS_O_MENOS');

        $this->assertSame(ListaNoDisponibleException::RESPUESTA_INVALIDA, $this->motivoDelFallo(fn () => $this->adaptador()->consultarComportamiento('Ana', 'Quito')));
    }
}
