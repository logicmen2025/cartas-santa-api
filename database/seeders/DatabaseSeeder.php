<?php

namespace Database\Seeders;

use App\Enums\Comportamiento;
use App\Enums\EstadoCarta;
use App\Enums\Rol;
use App\Models\Carta;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('polonorte.demo_password');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException('Define DEMO_PASSWORD en el .env antes de crear los usuarios de prueba.');
        }

        // Usuarios de prueba (se pueden volver a sembrar sin duplicarse)
        $usuarios = [
            ['name' => 'Elfo Jefe', 'email' => 'jefe@polonorte.test', 'rol' => Rol::ELFO_JEFE],
            ['name' => 'Elfo Ayudante', 'email' => 'elfo@polonorte.test', 'rol' => Rol::ELFO],
        ];

        foreach ($usuarios as $usuario) {
            User::query()->updateOrCreate(
                ['email' => $usuario['email']],
                ['name' => $usuario['name'], 'rol' => $usuario['rol'], 'password' => $password],
            );
        }

        // Unas cartas de ejemplo para que el listado no arranque vacío
        if (Carta::query()->exists()) {
            return;
        }

        $ejemplos = [
            ['Valentina Gómez', 8, 'Guayaquil', ['Bicicleta', 'Libro de dinosaurios'], EstadoCarta::RECIBIDA, null],
            ['Mateo Andrade', 11, 'Quito', ['Telescopio'], EstadoCarta::APROBADA, Comportamiento::BUENO],
            ['Camila Torres', 6, 'Cuenca', ['Muñeca', 'Rompecabezas'], EstadoCarta::EN_PRODUCCION, Comportamiento::BUENO],
            ['Sebastián Ruiz', 9, 'Guayaquil', ['Pelota de fútbol'], EstadoCarta::ENTREGADA, Comportamiento::BUENO],
            ['Pedro Travieso', 10, 'Manta', ['Patineta'], EstadoCarta::RECHAZADA, Comportamiento::TRAVIESO],
        ];

        foreach ($ejemplos as $i => [$remitente, $edad, $ciudad, $regalos, $estado, $comportamiento]) {
            Carta::query()->create([
                'remitente' => $remitente,
                'edad' => $edad,
                'ciudad' => $ciudad,
                'regalos' => $regalos,
                'estado' => $estado,
                'comportamiento' => $comportamiento,
                'fecha_recepcion' => now()->subDays(10 - $i),
            ]);
        }
    }
}
