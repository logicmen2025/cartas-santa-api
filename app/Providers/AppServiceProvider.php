<?php

namespace App\Providers;

use App\Contracts\CartaRepositoryInterface;
use App\Contracts\ListaBuenosTraviesosInterface;
use App\Contracts\UsuarioRepositoryInterface;
use App\Integrations\Soap\ListaBuenosTraviesosAdapter;
use App\Repositories\EloquentCartaRepository;
use App\Repositories\EloquentUsuarioRepository;
use App\Services\Auth\JwtService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Aquí se conectan las interfaces con sus implementaciones. Si mañana la
     * Lista deja de ser SOAP, solo cambia este enlace y el adaptador.
     */
    public function register(): void
    {
        $this->app->bind(CartaRepositoryInterface::class, EloquentCartaRepository::class);
        $this->app->bind(UsuarioRepositoryInterface::class, EloquentUsuarioRepository::class);

        $this->app->bind(ListaBuenosTraviesosInterface::class, fn () => new ListaBuenosTraviesosAdapter(
            url: (string) config('polonorte.lista_soap.url'),
            namespace: (string) config('polonorte.lista_soap.namespace'),
            soapAction: (string) config('polonorte.lista_soap.soap_action'),
            timeoutSegundos: (float) config('polonorte.lista_soap.timeout'),
        ));

        $this->app->singleton(JwtService::class, fn () => new JwtService(
            secreto: config('polonorte.jwt.secret'),
            ttlSegundos: (int) config('polonorte.jwt.ttl'),
            emisor: (string) config('polonorte.jwt.emisor'),
        ));
    }

    public function boot(): void
    {
        //
    }
}
