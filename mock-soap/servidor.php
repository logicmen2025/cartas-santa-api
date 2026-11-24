<?php

/*
|--------------------------------------------------------------------------
| Mock local de ListaBuenosTraviesosService (SOAP 1.1)
|--------------------------------------------------------------------------
|
| Simula el servicio heredado para poder desarrollar y probar sin depender
| del mock del docente. NO es parte de la API: es "el sistema externo".
|
| Levantar:   php -S 127.0.0.1:8001 mock-soap/servidor.php
|   (o)       composer mock-soap
|
| Endpoint:   POST http://127.0.0.1:8001/ListaBuenosTraviesosService
| WSDL:       GET  http://127.0.0.1:8001/ListaBuenosTraviesosService?wsdl
|
| Comportamiento del mock:
|   nombre "Grinch"                 -> soap:Fault (soap:Server, "Lista en mantenimiento")
|   nombre "Krampus"                -> tarda 4 s en responder (para probar el timeout de 3 s)
|   nombre con "travies" (Pedro Travieso, Ana Traviesa) -> TRAVIESO
|   cualquier otro nombre           -> BUENO
*/

date_default_timezone_set('America/Guayaquil');

function registrar(string $mensaje): void
{
    file_put_contents('php://stderr', '[mock-soap '.date('H:i:s')."] {$mensaje}\n");
}

function responder(int $estado, string $xml): void
{
    http_response_code($estado);
    header('Content-Type: text/xml; charset=utf-8');
    echo $xml;
}

function fault(string $codigo, string $texto): void
{
    $texto = htmlspecialchars($texto, ENT_XML1);
    responder(500, <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <soap:Fault>
      <faultcode>{$codigo}</faultcode>
      <faultstring>{$texto}</faultstring>
    </soap:Fault>
  </soap:Body>
</soap:Envelope>
XML);
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8001';

if ($metodo === 'GET') {
    if (isset($_GET['wsdl']) || isset($_GET['WSDL']) || str_ends_with((string) $ruta, '.wsdl')) {
        $wsdl = file_get_contents(__DIR__.'/ListaBuenosTraviesos.wsdl');
        responder(200, str_replace('{{URL_SERVICIO}}', "http://{$host}/ListaBuenosTraviesosService", $wsdl));

        return;
    }

    header('Content-Type: text/plain; charset=utf-8');
    echo "Mock de ListaBuenosTraviesosService activo.\n";
    echo "POST SOAP a http://{$host}/ListaBuenosTraviesosService\n";
    echo "WSDL en  http://{$host}/ListaBuenosTraviesosService?wsdl\n";

    return;
}

if ($metodo !== 'POST') {
    http_response_code(405);

    return;
}

$cuerpo = file_get_contents('php://input');
$doc = new DOMDocument;
libxml_use_internal_errors(true);

if ($cuerpo === '' || ! $doc->loadXML($cuerpo, LIBXML_NONET)) {
    registrar('request con XML inválido -> Fault soap:Client');
    fault('soap:Client', 'El mensaje no es XML válido');

    return;
}

$xpath = new DOMXPath($doc);
$nombre = trim($xpath->evaluate("string(//*[local-name()='ConsultarComportamientoRequest']/*[local-name()='nombre'])"));
$ciudad = trim($xpath->evaluate("string(//*[local-name()='ConsultarComportamientoRequest']/*[local-name()='ciudad'])"));

if ($nombre === '') {
    registrar('request sin <nombre> -> Fault soap:Client');
    fault('soap:Client', 'Falta el nombre');

    return;
}

if (strcasecmp($nombre, 'Grinch') === 0) {
    registrar("nombre={$nombre} ciudad={$ciudad} -> soap:Fault (Lista en mantenimiento)");
    fault('soap:Server', 'Lista en mantenimiento');

    return;
}

if (strcasecmp($nombre, 'Krampus') === 0) {
    registrar("nombre={$nombre} ciudad={$ciudad} -> esperando 4 s (simula servicio colgado)");
    sleep(4);
}

$resultado = str_contains(mb_strtolower($nombre), 'travies') ? 'TRAVIESO' : 'BUENO';
registrar("nombre={$nombre} ciudad={$ciudad} -> {$resultado}");

responder(200, <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:pn="http://polonorte.beatlech/lista">
  <soapenv:Body>
    <pn:ConsultarComportamientoResponse>
      <pn:resultado>{$resultado}</pn:resultado>
    </pn:ConsultarComportamientoResponse>
  </soapenv:Body>
</soapenv:Envelope>
XML);
