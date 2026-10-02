<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Cada tarea deja su salida acá: en el hosting el cron no puede redirigirla,
// y así se ve cuándo corrió cada una y si falló.
$registro = storage_path('logs/cron.log');
$marcar = fn (string $tarea) => fn () => file_put_contents(
    $registro,
    PHP_EOL . '[' . now('America/Argentina/Buenos_Aires')->format('d/m/Y H:i:s') . "] {$tarea}" . PHP_EOL,
    FILE_APPEND,
);

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Frecuencias: el cron del hosting entra cada 5 minutos, así que todas van
// en múltiplos de 5. Las tareas que tocan juntas corren en este orden.

// El catálogo se refresca solo: el sync es incremental por write_date
// (de la variante y de la plantilla, donde se despublica o archiva).
Schedule::command('odoo:sync-catalog')->before($marcar('odoo:sync-catalog'))->everyFiveMinutes()->withoutOverlapping()->appendOutputTo($registro);

// Precio, stock, activo y publicado de todo el catálogo: el precio y el stock
// cambian sin tocar la fecha de modificación, así que se releen seguido.
// Una pasada tarda alrededor de un minuto.
Schedule::command('odoo:sync-precios')->before($marcar('odoo:sync-precios'))->everyFiveMinutes()->withoutOverlapping()->appendOutputTo($registro);

// Ofertas: los descuentos van entre fechas, así que se revisan seguido.
Schedule::command('odoo:sync-ofertas')->before($marcar('odoo:sync-ofertas'))->everyFiveMinutes()->withoutOverlapping()->appendOutputTo($registro);

// Clientes y vendedores: también incremental, trae sólo lo que cambió.
Schedule::command('odoo:sync-clientes')->before($marcar('odoo:sync-clientes'))->everyFiveMinutes()->withoutOverlapping()->appendOutputTo($registro);

// Alternativos y accesorios: necesitan el catálogo ya sincronizado.
Schedule::command('odoo:sync-relacionados')->before($marcar('odoo:sync-relacionados'))->hourlyAt(15)->withoutOverlapping()->appendOutputTo($registro);

// Las marcas se deducen del nombre, así que se recalculan después del sync.
Schedule::command('catalogo:marcas')->before($marcar('catalogo:marcas'))->hourlyAt(45)->withoutOverlapping()->appendOutputTo($registro);

// La lista de precios descargable se rearma cada media hora, a las :10 y :40
// para no caer junto con el resto en las horas en punto.
Schedule::command('precios:generar')->before($marcar('precios:generar'))->cron('10,40 * * * *')->withoutOverlapping()->appendOutputTo($registro);
