<?php

namespace App\Providers;

use App\Contracts\CatalogoRepository;
use App\Contracts\CuentaRepository;
use App\Contracts\PedidosRepository;
use App\Services\Catalogo\CatalogoLocal;
use App\Services\Cuenta\CuentaDemo;
use App\Services\Odoo\OdooClient;
use App\Services\Pedidos\PedidosDemo;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // El catálogo real: lee la copia local que sincroniza odoo:sync-catalog.
        $this->app->bind(CatalogoRepository::class, CatalogoLocal::class);
        $this->app->bind(PedidosRepository::class, PedidosDemo::class);
        $this->app->bind(CuentaRepository::class, CuentaDemo::class);

        $this->app->singleton(OdooClient::class, fn () => new OdooClient(config('odoo')));
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.admin');
        Paginator::defaultSimpleView('vendor.pagination.admin');

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
