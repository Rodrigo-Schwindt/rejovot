<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Services\Odoo\OdooCatalog;
use App\Services\Odoo\OdooException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncOdooCatalog extends Command
{
    protected $signature = 'odoo:sync-catalog
        {--full : Trae todo el catálogo en vez de sólo lo modificado}
        {--limit=0 : Corta después de N productos (para probar)}
        {--page=500 : Productos por página}';

    protected $description = 'Sincroniza categorías y productos desde Odoo a la base local';

    /** Filas que no se pudieron guardar, para reportarlas al final. */
    protected array $fallidos = [];

    public function handle(OdooCatalog $catalog): int
    {
        $inicio = microtime(true);

        try {
            $this->syncCategories($catalog);

            // Incremental por write_date: sólo lo que cambió desde la última corrida.
            $since = $this->option('full') ? null : Product::max('odoo_write_date');

            if ($since) {
                $this->line('  Sincronizando cambios desde ' . $since);
            } else {
                $this->line('  Sincronización completa');
            }

            $pendientes = $catalog->productsCount($since);
            $tope = (int) $this->option('limit');
            $porPagina = max(50, (int) $this->option('page'));

            if ($tope > 0) {
                $pendientes = min($pendientes, $tope);
                $porPagina = min($porPagina, $tope);
            }

            $this->line('  Productos a procesar: ' . number_format($pendientes, 0, ',', '.'));

            if ($pendientes === 0) {
                $this->info('  Nada para actualizar.');

                return self::SUCCESS;
            }

            $barra = $this->output->createProgressBar($pendientes);
            $barra->start();

            $offset = 0;
            $total = 0;

            do {
                $rows = $catalog->productsPage($since, $offset, $porPagina);

                if (! $rows) {
                    break;
                }

                $this->guardarPagina($rows);

                $offset += count($rows);
                $total += count($rows);
                $barra->advance(count($rows));

                if ($tope > 0 && $total >= $tope) {
                    break;
                }
            } while (count($rows) === $porPagina);

            $barra->finish();
            $this->newLine(2);

            $segundos = round(microtime(true) - $inicio);
            $this->info("  {$total} productos sincronizados en {$segundos}s.");
            $this->info('  Total en base: ' . number_format(Product::count(), 0, ',', '.'));

            if ($this->fallidos) {
                $this->newLine();
                $this->warn('  ' . count($this->fallidos) . ' producto(s) no se pudieron guardar:');

                foreach (array_slice($this->fallidos, 0, 10) as $fallido) {
                    $this->warn('    ' . $fallido);
                }
            }
        } catch (OdooException $e) {
            $this->newLine();
            $this->error('  Error de Odoo: ' . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /** Guarda una página de productos. El costo de Rejovot no se sincroniza. */
    protected function guardarPagina(array $rows): void
    {
        $categorias = $this->mapaCategorias();

        DB::transaction(function () use ($rows, $categorias) {
            foreach ($rows as $row) {
                $tmplId = $row['product_tmpl_id'][0] ?? null;
                $categoriaOdoo = $row['categ_id'][0] ?? null;

                try {
                    Product::updateOrCreate(['odoo_id' => $row['id']], [
                        'odoo_tmpl_id' => $tmplId,
                        // Hay un producto con un texto largo cargado como código.
                        'code' => $this->recortar($row['default_code'], 120),
                        'name' => $row['name'],
                        'oem_codes' => $row['oem_code'] ?: null,
                        'type' => $row['type'] ?: null,
                        'extra_image_ids' => $row['product_template_image_ids'] ?: null,
                        'category_id' => $categorias[$categoriaOdoo] ?? null,
                        // lst_price_with_margin ya es el precio de la tarifa pública.
                        'list_price' => $row['lst_price_with_margin'] ?? 0,
                        'stock' => $row['qty_available'] ?? 0,
                        'active' => (bool) $row['active'],
                        'published' => (bool) $row['website_published'],
                        'odoo_write_date' => $row['write_date'],
                    ]);
                } catch (\Throwable $e) {
                    // Una fila rara no puede cortar una sincronización de 55k.
                    $this->fallidos[] = $row['id'] . ': ' . $e->getMessage();
                }
            }
        });
    }

    /** Recorta un valor de Odoo al largo que soporta la columna. */
    protected function recortar($valor, int $largo): ?string
    {
        $valor = trim((string) ($valor ?: ''));

        if ($valor === '') {
            return null;
        }

        return mb_substr($valor, 0, $largo);
    }

    /** @return array<int, int> odoo_id => id local */
    protected function mapaCategorias(): array
    {
        static $mapa = null;

        return $mapa ??= Category::pluck('id', 'odoo_id')->all();
    }

    protected function syncCategories(OdooCatalog $catalog): void
    {
        $rows = $catalog->categories();

        foreach ($rows as $row) {
            Category::updateOrCreate(['odoo_id' => $row['id']], [
                'name' => $row['name'],
                'complete_name' => $row['complete_name'] ?? null,
            ]);
        }

        // El padre se asigna en una segunda pasada: puede venir después que el hijo.
        $locales = Category::pluck('id', 'odoo_id');

        foreach ($rows as $row) {
            if (empty($row['parent_id'])) {
                continue;
            }

            Category::where('odoo_id', $row['id'])->update([
                'parent_id' => $locales[$row['parent_id'][0]] ?? null,
            ]);
        }

        $this->info('  Categorías sincronizadas: ' . count($rows));
    }
}
