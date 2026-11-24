<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de la API (prefijo /api)
|--------------------------------------------------------------------------
| Rutas -> Controladores -> Servicios -> Repositorio.
| 'rol:ELFO_JEFE'  = middleware de seguridad (401 sin token / 403 otro rol).
| 'auth.opcional'  = si llega token válido, se identifica al usuario (no exige).
*/

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');

Route::prefix('cartas')
    ->where(['id' => '[0-9]{1,18}'])
    ->controller(CartaController::class)
    ->group(function () {
        Route::get('/', 'index')->middleware('auth.opcional');
        Route::post('/', 'store');
        Route::get('/{id}', 'show')->middleware('auth.opcional');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy')->middleware('rol:ELFO_JEFE');
        Route::patch('/{id}/estado', 'cambiarEstado')->middleware('rol:ELFO_JEFE');
    });
