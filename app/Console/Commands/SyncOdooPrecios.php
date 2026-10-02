<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Catalogo\GuardarProductos;
use App\Services\Catalogo\RefrescoEnVivo;
use App\Services\Odoo\OdooCatalog;
use App\Services\Odoo\OdooException;
use Illuminate\Console\Command;

/**
 * Relee de todo el catálogo lo que tiene que estar siempre al día: precio,
 * stock, activo y publicado. Son pocos campos, así que una pasada completa
 * tarda alrededor de un minuto y se puede correr cada pocos minutos.
 *
 * El sync del catálogo es incremental por fecha de modificación, y eso no
 * alcanza: el precio se calcula al leerlo (costo, margen, cotización) y el
 * stock sale de los movimientos, ninguno de los dos cambia esa fecha.
 *
 * De paso, lo que ya no está en Odoo (borrado, o dejó de venderse) se oculta,
 * y lo que esté en Odoo y falte acá se trae completo.
 */
class SyncOdooPrecios extends Command
{
    protected $signature = 'odoo:sync-precios {--page=2000 : Productos por página}';

    protected $description = 'Actualiza precio, stock, activo y publicado de todo el catálogo desde Odoo';

    public function handle(OdooCatalog $catalog, GuardarProductos $guardar): int
    {
        $inicio = microtime(true);
        $porPagina = max(100, (int) $this->option('page'));
        $vistos = [];
        $faltantes = [];
        $cambiados = 0;
        $offset = 0;

        try {
            do {
                $rows = $catalog->estadosPage($offset, $porPagina);

                if (! $rows) {
                    break;
                }

                $locales = Product::whereIn('odoo_id', array_column($rows, 'id'))
                    ->get(['id', 'odoo_id', 'list_price', 'stock', 'active', 'published'])
                    ->keyBy('odoo_id');

                foreach ($rows as $row) {
                    $vistos[$row['id']] = true;
                    $local = $locales[$row['id']] ?? null;

                    if (! $local) {
                        $faltantes[] = $row['id'];

                        continue;
                    }

                    // Guarda sólo si cambió algo: casi siempre es una fracción mínima.
                    $cambiados += RefrescoEnVivo::aplicar($local, $row) ? 1 : 0;
                }

                $offset += count($rows);
            } while (count($rows) === $porPagina);

            $nuevos = $this->traerFaltantes($catalog, $guardar, $faltantes);
            $ocultos = $this->ocultarLosQueNoEstan($vistos);
        } catch (OdooException $e) {
            // A medias no se oculta nada: faltaría ver el resto del catálogo.
            $this->error('  Error de Odoo: ' . $e->getMessage());

            return self::FAILURE;
        }

        $segundos = round(microtime(true) - $inicio);
        $this->info("  {$offset} productos revisados en {$segundos}s: {$cambiados} actualizados, {$nuevos} nuevos, {$ocultos} ya no están en Odoo.");

        return self::SUCCESS;
    }

    private function traerFaltantes(OdooCatalog $catalog, GuardarProductos $guardar, array $ids): int
    {
        foreach (array_chunk($ids, 500) as $lote) {
            foreach ($guardar->guardar($catalog->productsByIds($lote)) as $fallido) {
                $this->warn('  No se pudo guardar ' . $fallido);
            }
        }

        return count($ids);
    }

    /** Lo que está acá y Odoo ya no devuelve deja de mostrarse. */
    private function ocultarLosQueNoEstan(array $vistos): int
    {
        // Si Odoo devolvió el catálogo vacío algo anda mal: no se oculta todo.
        if (! $vistos) {
            return 0;
        }

        $sobran = Product::where('active', true)
            ->pluck('odoo_id')
            ->reject(fn ($id) => isset($vistos[$id]))
            ->all();

        foreach (array_chunk($sobran, 1000) as $lote) {
            Product::whereIn('odoo_id', $lote)->update(['active' => false]);
        }

        return count($sobran);
    }
}
