<?php

/**
 * Mantenimiento en producción: limpia la caché y corre las migraciones.
 *
 * Va en public_html y se abre desde el navegador:
 *   https://rejovot.com.ar/script.php?clave=6d671daefbb3986fbd105c31cb23a7fa
 *
 * El proyecto Laravel está al lado de public_html, en ../rejovot.
 */

const CLAVE = '6d671daefbb3986fbd105c31cb23a7fa';

if (! hash_equals(CLAVE, (string) ($_GET['clave'] ?? ''))) {
    http_response_code(404);
    exit;
}

set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');

$base = realpath(__DIR__ . '/../rejovot');

if (! $base || ! file_exists($base . '/artisan')) {
    exit('No encuentro el proyecto en ' . __DIR__ . '/../rejovot');
}

// Con &log=1 sólo muestra lo último que corrió el cron, sin ejecutar nada.
if (isset($_GET['log'])) {
    $log = $base . '/storage/logs/cron.log';

    if (! file_exists($log)) {
        exit("Todavía no hay registro del cron ({$log}).\nSi pasaron más de 10 minutos, el cron no está corriendo.\n");
    }

    echo "Últimas líneas de {$log} (hora argentina):\n";
    echo "Ahora: " . (new DateTime('now', new DateTimeZone('America/Argentina/Buenos_Aires')))->format('d/m/Y H:i:s') . "\n\n";
    echo implode('', array_slice(file($log), -80));
    exit;
}

// OPcache guarda los .php compilados en memoria: si el hosting no revisa la
// fecha de los archivos, sigue usando las rutas y controladores viejos aunque
// se suban nuevos. optimize:clear no lo toca; esto sí. Las corridas de tareas
// (&cron=…) lo saltean: el cron entra cada 5 minutos y resetear OPcache tan
// seguido haría más lento el sitio entero.
if (! isset($_GET['cron'])) {
    echo "== OPcache\n";

    if (function_exists('opcache_get_status') && ($estado = @opcache_get_status(false)) && $estado['opcache_enabled']) {
        echo '  validate_timestamps=' . ini_get('opcache.validate_timestamps')
            . ' revalidate_freq=' . ini_get('opcache.revalidate_freq') . "s\n";
        echo opcache_reset() ? "  reseteado: OK\n\n" : "  no se pudo resetear\n\n";
    } else {
        echo "  desactivado o sin acceso\n\n";
    }
}

require $base . '/vendor/autoload.php';

$app = require $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Con &cron=… corre a mano las tareas programadas (lo que hace el cron), sin
// esperar a que les toque: &cron=todas, o una sola: &cron=precios, etc.
$tareas = [
    'catalogo' => 'odoo:sync-catalog',
    'clientes' => 'odoo:sync-clientes',
    'ofertas' => 'odoo:sync-ofertas',
    'relacionados' => 'odoo:sync-relacionados',
    'marcas' => 'catalogo:marcas',
    'precios' => 'odoo:sync-precios',
    'lista' => 'precios:generar',
];

// &cron=auto es lo que llama la tarea programada del hosting (con curl, cada
// 5 minutos): corre sólo las tareas a las que les toca ahora, igual que
// `php artisan schedule:run`, pero dentro de este proceso. Así no depende de
// la ruta del PHP de consola, que en DonWeb no funcionó.
if (($_GET['cron'] ?? '') === 'auto') {
    set_time_limit(1800);
    ignore_user_abort(true);

    $registro = $base . '/storage/logs/cron.log';

    // Se escribe cada 5 minutos: pasado 1 MB se queda sólo con lo último.
    if (file_exists($registro) && filesize($registro) > 1024 * 1024) {
        file_put_contents($registro, substr(file_get_contents($registro), -256 * 1024));
    }

    $schedule = $app->make(Illuminate\Console\Scheduling\Schedule::class);
    $corridas = [];

    foreach ($schedule->dueEvents($app) as $evento) {
        if (! $evento->filtersPass($app)) {
            continue;
        }

        // Señal de vida y cualquier otra tarea que sea una función.
        if ($evento instanceof Illuminate\Console\Scheduling\CallbackEvent) {
            $evento->run($app);
            $corridas[] = $evento->getSummaryForDisplay();

            continue;
        }

        // «'…/php' 'artisan' odoo:sync-precios» → odoo:sync-precios
        if (! preg_match('/[\'"]?artisan[\'"]?\s+(\S+)/', (string) $evento->command, $m)) {
            continue;
        }

        $comando = $m[1];

        // Si la corrida anterior de esta tarea sigue en curso, no se pisa.
        $lock = Illuminate\Support\Facades\Cache::lock('cron-web:' . $comando, 1800);

        if (! $lock->get()) {
            file_put_contents($registro, "  {$comando}: sigue corriendo la anterior, se saltea\n", FILE_APPEND);

            continue;
        }

        try {
            $evento->callBeforeCallbacks($app);
            $kernel->call($comando);
            file_put_contents($registro, $kernel->output(), FILE_APPEND);
            $corridas[] = $comando;
        } catch (Throwable $e) {
            file_put_contents($registro, "  ERROR: {$e->getMessage()}\n", FILE_APPEND);
        } finally {
            $lock->release();
        }
    }

    exit('OK: ' . (implode(', ', $corridas) ?: 'nada para correr ahora') . "\n");
}

if (isset($_GET['cron'])) {
    $elegidas = $_GET['cron'] === 'todas' ? $tareas : array_intersect_key($tareas, [$_GET['cron'] => true]);

    if (! $elegidas) {
        exit("Tarea desconocida. Opciones: todas, " . implode(', ', array_keys($tareas)) . "\n");
    }

    set_time_limit(1800);
    ignore_user_abort(true);

    foreach ($elegidas as $comando) {
        echo "== php artisan {$comando}\n";
        @ob_flush();
        flush();

        try {
            $inicio = microtime(true);
            $codigo = $kernel->call($comando);
            echo trim($kernel->output()) . "\n";
            echo ($codigo === 0 ? 'OK' : "Terminó con código {$codigo}") . ' (' . round(microtime(true) - $inicio) . "s)\n\n";
        } catch (Throwable $e) {
            echo 'ERROR: ' . $e->getMessage() . "\n\n";
        }

        @ob_flush();
        flush();
    }

    exit("Listo.\n");
}

$comandos = [
    // Primero la caché: así se toman las rutas y la config nuevas.
    ['optimize:clear', []],
    ['migrate', ['--force' => true]],
];

foreach ($comandos as [$comando, $opciones]) {
    echo "== php artisan {$comando}\n";

    try {
        $codigo = $kernel->call($comando, $opciones);
        echo trim($kernel->output()) . "\n";
        echo $codigo === 0 ? "OK\n\n" : "Terminó con código {$codigo}\n\n";
    } catch (Throwable $e) {
        echo 'ERROR: ' . $e->getMessage() . "\n\n";
    }
}

// Diagnóstico: ¿está subido el routes/web.php nuevo y quedó cargado?
$rutas = $base . '/routes/web.php';
$cache = $base . '/bootstrap/cache/routes-v7.php';

echo "== Chequeo de rutas\n";
echo 'routes/web.php: ' . $rutas . ' (modificado ' . date('d/m/Y H:i', filemtime($rutas)) . ")\n";
echo str_contains(file_get_contents($rutas), "->name('admin.usuarios.index')")
    ? "  tiene las rutas nuevas de usuarios: OK\n"
    : "  NO tiene las rutas nuevas de usuarios: falta subir routes/web.php a esa carpeta\n";

echo "  líneas con «usuarios»:\n";
foreach (file($rutas) as $n => $linea) {
    if (stripos($linea, 'usuarios') !== false) {
        echo '    ' . ($n + 1) . ': ' . trim($linea) . "\n";
    }
}
echo file_exists($cache)
    ? "  hay rutas en caché: {$cache} (borralo a mano si sigue fallando)\n"
    : "  sin rutas en caché: OK\n";

// Las rutas se leen de nuevo para ver si la de usuarios existe.
$app->make('router')->setRoutes(new Illuminate\Routing\RouteCollection());
require $rutas;
$app->make('router')->getRoutes()->refreshNameLookups();
echo $app->make('router')->has('admin.usuarios.index')
    ? "  ruta admin.usuarios.index: OK\n"
    : "  ruta admin.usuarios.index: NO EXISTE\n";

echo "  rutas cargadas en admin/usuarios:\n";
foreach ($app->make('router')->getRoutes() as $ruta) {
    if (str_starts_with($ruta->uri(), 'admin/usuarios')) {
        echo '    ' . implode('|', $ruta->methods()) . ' ' . $ruta->uri() . ' -> ' . ($ruta->getName() ?? '(sin nombre)') . "\n";
    }
}
echo '  PHP ' . PHP_VERSION . "\n";

echo "\nListo.\n";
