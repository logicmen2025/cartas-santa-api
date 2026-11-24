<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * php artisan jwt:secret
 *
 * Genera un JWT_SECRET aleatorio y lo escribe en el .env local. Así el secreto
 * nunca está en el repositorio (en .env.example la variable va vacía).
 */
class GenerarSecretoJwt extends Command
{
    protected $signature = 'jwt:secret
                            {--force : Reemplaza el secreto aunque ya exista}
                            {--show : Solo muestra un secreto nuevo, sin tocar el .env}';

    protected $description = 'Genera JWT_SECRET en el archivo .env';

    public function handle(): int
    {
        $secreto = bin2hex(random_bytes(32)); // 64 caracteres hex = 256 bits

        if ($this->option('show')) {
            $this->line($secreto);

            return self::SUCCESS;
        }

        $rutaEnv = $this->laravel->environmentFilePath();

        if (! is_file($rutaEnv)) {
            $this->components->error('No existe el archivo .env. Primero cópialo desde .env.example.');

            return self::FAILURE;
        }

        $contenido = (string) file_get_contents($rutaEnv);
        $actual = preg_match('/^JWT_SECRET=(.*)$/m', $contenido, $m) ? trim($m[1], " \t\"'") : null;

        if ($actual !== null && $actual !== '' && ! $this->option('force')) {
            $this->components->info('JWT_SECRET ya estaba definido; lo dejo igual (usa --force para regenerarlo).');

            return self::SUCCESS;
        }

        $linea = 'JWT_SECRET='.$secreto;
        $contenido = $actual === null
            ? rtrim($contenido).PHP_EOL.$linea.PHP_EOL
            : preg_replace('/^JWT_SECRET=.*$/m', $linea, $contenido);

        file_put_contents($rutaEnv, $contenido);

        $this->components->info('JWT_SECRET generado en el .env.');

        return self::SUCCESS;
    }
}
