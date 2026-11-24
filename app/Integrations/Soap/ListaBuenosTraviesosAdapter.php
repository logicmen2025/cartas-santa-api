<?php

namespace App\Integrations\Soap;

use App\Contracts\ListaBuenosTraviesosInterface;
use App\Enums\Comportamiento;
use App\Exceptions\ListaNoDisponibleException;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Adaptador del servicio SOAP heredado ListaBuenosTraviesosService.
 *
 * Es la ÚNICA clase del proyecto que conoce XML:
 *   - arma el sobre SOAP 1.1 de ConsultarComportamiento (dominio -> XML)
 *   - interpreta <pn:resultado> o el soap:Fault (XML -> dominio)
 *   - corta la llamada si pasan más de N segundos (3 por defecto)
 *
 * Cualquier problema (Fault, timeout, sin conexión, respuesta rara) se traduce
 * a ListaNoDisponibleException. Nada de lo que pase aquí puede terminar en 500.
 */
class ListaBuenosTraviesosAdapter implements ListaBuenosTraviesosInterface
{
    private const SOAP_ENV = 'http://schemas.xmlsoap.org/soap/envelope/';

    public function __construct(
        private readonly string $url,
        private readonly string $namespace,
        private readonly string $soapAction,
        private readonly float $timeoutSegundos,
    ) {}

    public function consultarComportamiento(string $nombre, string $ciudad): Comportamiento
    {
        try {
            $respuesta = Http::withHeaders([
                'SOAPAction' => '"'.$this->soapAction.'"',
                'Accept' => 'text/xml',
            ])
                ->timeout($this->timeoutSegundos)
                ->connectTimeout($this->timeoutSegundos)
                ->withBody($this->construirSolicitud($nombre, $ciudad), 'text/xml; charset=utf-8')
                ->post($this->url);
        } catch (ConnectionException $e) {
            throw $this->esTimeout($e)
                ? ListaNoDisponibleException::timeout($this->timeoutSegundos, $e)
                : ListaNoDisponibleException::sinConexion($this->resumir($e->getMessage()), $e);
        } catch (Throwable $e) {
            throw ListaNoDisponibleException::sinConexion($this->resumir($e->getMessage()), $e);
        }

        return $this->interpretarRespuesta($respuesta->status(), $respuesta->body());
    }

    /** Dominio -> XML: el mismo sobre que muestra el enunciado. */
    public function construirSolicitud(string $nombre, string $ciudad): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $envelope = $doc->createElementNS(self::SOAP_ENV, 'soapenv:Envelope');
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:pn', $this->namespace);
        $doc->appendChild($envelope);

        $body = $doc->createElementNS(self::SOAP_ENV, 'soapenv:Body');
        $envelope->appendChild($body);

        $solicitud = $doc->createElementNS($this->namespace, 'pn:ConsultarComportamientoRequest');
        $body->appendChild($solicitud);

        // createTextNode escapa &, <, > ... así un nombre raro no rompe el XML
        foreach (['nombre' => $nombre, 'ciudad' => $ciudad] as $campo => $valor) {
            $nodo = $doc->createElementNS($this->namespace, "pn:{$campo}");
            $nodo->appendChild($doc->createTextNode($valor));
            $solicitud->appendChild($nodo);
        }

        return $doc->saveXML();
    }

    /** XML -> dominio. */
    private function interpretarRespuesta(int $estadoHttp, string $cuerpo): Comportamiento
    {
        $doc = $this->cargarXml($cuerpo);
        if ($doc === null) {
            throw ListaNoDisponibleException::respuestaInvalida("HTTP {$estadoHttp}, el cuerpo no es XML válido");
        }

        $xpath = new DOMXPath($doc);

        // soap:Fault (SOAP 1.1: faultcode/faultstring; SOAP 1.2: Code/Reason)
        $fault = $xpath->query("//*[local-name()='Body']/*[local-name()='Fault']")->item(0);
        if ($fault !== null) {
            $codigo = trim($xpath->evaluate("string(*[local-name()='faultcode'] | *[local-name()='Code']/*[local-name()='Value'])", $fault));
            $texto = trim($xpath->evaluate("string(*[local-name()='faultstring'] | *[local-name()='Reason']/*[local-name()='Text'])", $fault));

            throw ListaNoDisponibleException::fault($codigo !== '' ? $codigo : 'desconocido', $texto !== '' ? $texto : 'sin descripción');
        }

        if ($estadoHttp < 200 || $estadoHttp >= 300) {
            throw ListaNoDisponibleException::respuestaInvalida("HTTP {$estadoHttp} sin soap:Fault");
        }

        $resultado = strtoupper(trim($xpath->evaluate("string(//*[local-name()='Body']//*[local-name()='resultado'])")));

        return match ($resultado) {
            'BUENO' => Comportamiento::BUENO,
            'TRAVIESO' => Comportamiento::TRAVIESO,
            '' => throw ListaNoDisponibleException::respuestaInvalida('la respuesta no trae <resultado>'),
            default => throw ListaNoDisponibleException::respuestaInvalida("resultado desconocido: {$resultado}"),
        };
    }

    private function cargarXml(string $cuerpo): ?DOMDocument
    {
        if (trim($cuerpo) === '') {
            return null;
        }

        $doc = new DOMDocument;
        $erroresPrevios = libxml_use_internal_errors(true);
        // LIBXML_NONET: nunca descargar nada externo mientras se lee la respuesta
        $ok = $doc->loadXML($cuerpo, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($erroresPrevios);

        // Un mensaje SOAP no puede traer DTD; si la trae, no la procesamos (evita XXE)
        if (! $ok || $doc->doctype !== null) {
            return null;
        }

        return $doc;
    }

    private function esTimeout(ConnectionException $e): bool
    {
        $mensaje = strtolower($e->getMessage());

        return str_contains($mensaje, 'curl error 28') || str_contains($mensaje, 'timed out') || str_contains($mensaje, 'timeout');
    }

    private function resumir(string $mensaje): string
    {
        return mb_strimwidth(trim(preg_replace('/\s+/', ' ', $mensaje)), 0, 180, '…');
    }
}
