<?php

/*
|--------------------------------------------------------------------------
| Configuración propia del proyecto (Escenario A · Cartas a Santa)
|--------------------------------------------------------------------------
|
| Todo lo que cambia entre máquinas o es secreto sale del .env. Aquí solo
| quedan nombres de variables y valores por defecto que no son secretos.
|
*/

return [

    // Servicio SOAP heredado: ListaBuenosTraviesosService (operación ConsultarComportamiento)
    'lista_soap' => [
        // Endpoint SOAP. Por defecto apunta al mock local (mock-soap/servidor.php).
        'url' => env('LISTA_SOAP_URL', 'http://127.0.0.1:8001/ListaBuenosTraviesosService'),

        // targetNamespace del WSDL (prefijo pn: en el request)
        'namespace' => env('LISTA_SOAP_NAMESPACE', 'http://polonorte.beatlech/lista'),

        // Cabecera SOAPAction (SOAP 1.1)
        'soap_action' => env('LISTA_SOAP_ACTION', 'ConsultarComportamiento'),

        // Segundos máximos de espera. El enunciado pide 3.
        'timeout' => (float) env('LISTA_SOAP_TIMEOUT', 3),
    ],

    // Tokens JWT (HS256) para los endpoints protegidos
    'jwt' => [
        'secret' => env('JWT_SECRET'),
        'ttl' => (int) env('JWT_TTL', 7200),
        'emisor' => env('JWT_EMISOR', 'cartas-santa-api'),
    ],

    // Contraseña de los usuarios de prueba que crea el seeder
    'demo_password' => env('DEMO_PASSWORD'),

    // Permite el header X-Fecha-Simulada (solo fuera de producción) para
    // demostrar la regla del 24 de diciembre sin cambiar el reloj del equipo
    'permitir_fecha_simulada' => (bool) env('PERMITIR_FECHA_SIMULADA', false),

];
