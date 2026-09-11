<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// El catálogo se refresca solo: el sync es incremental por write_date.
Schedule::command('odoo:sync-catalog')->everyThirtyMinutes()->withoutOverlapping();

// Ofertas: los descuentos van entre fechas, así que se revisan seguido.
Schedule::command('odoo:sync-ofertas')->hourly()->withoutOverlapping();

// Clientes y vendedores: cambian menos que el catálogo.
Schedule::command('odoo:sync-clientes')->hourly()->withoutOverlapping();

// Las marcas se deducen del nombre, así que se recalculan después del sync.
Schedule::command('catalogo:marcas')->dailyAt('04:30')->withoutOverlapping();

// La lista de precios se rearma con el catálogo ya sincronizado.
Schedule::command('precios:generar')->dailyAt('05:00')->withoutOverlapping();
