<?php

use App\Exceptions\ManejadorGlobalDeErrores;
use App\Http\Middleware\ForzarRespuestaJson;
use App\Http\Middleware\IdentificarUsuarioOpcional;
use App\Http\Middleware\SimularFecha;
use App\Http\Middleware\ValidarCuerpoJson;
use App\Http\Middleware\VerificarRol;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Se ejecutan en todas las rutas /api, en este orden
        $middleware->api(prepend: [
            ForzarRespuestaJson::class,
            SimularFecha::class,
            ValidarCuerpoJson::class,
        ]);

        // Middleware de seguridad usados en routes/api.php
        $middleware->alias([
            'rol' => VerificarRol::class,
            'auth.opcional' => IdentificarUsuarioOpcional::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Manejador global: todo error de /api sale con el mismo formato JSON
        ManejadorGlobalDeErrores::registrar($exceptions);
    })->create();
