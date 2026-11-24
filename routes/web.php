<?php

use Illuminate\Support\Facades\Route;

// Página de inicio: solo confirma que la API está arriba y lista los endpoints.
Route::get('/', fn () => response()->json([
    'api' => 'Taller de Santa · Cartas a Santa (Escenario A)',
    'estado' => 'en línea',
    'endpoints' => [
        'POST /api/auth/login',
        'GET /api/cartas?estado=&ciudad=',
        'POST /api/cartas',
        'GET /api/cartas/{id}',
        'PUT /api/cartas/{id}',
        'DELETE /api/cartas/{id}  (ELFO_JEFE)',
        'PATCH /api/cartas/{id}/estado  (ELFO_JEFE)',
    ],
    'documentacion' => 'README.md',
], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
