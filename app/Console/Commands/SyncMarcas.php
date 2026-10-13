<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use App\Services\Odoo\OdooCatalog;
use App\Services\Odoo\OdooException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Trae las categorías de ecommerce de Odoo (product.public.category), todas,
 * en el orden de Odoo: son los filtros de marca del sitio. Las que no son
 * marca (NOVEDADES, ver config odoo.categorias_no_marca) se muestran igual,
 * pero no se le asignan a un producto como su marca.
 *
 * A cada producto le guarda todas sus categorías y su marca, y borra las que
 * ya no existen en Odoo. El sync del catálogo también lo hace con los
 * productos que trae; esto corrige todo el catálogo de una.
 */
class SyncMarcas extends Command
{
    protected $signature = 'catalogo:marcas';

    protected $description = 'Trae las marcas (categorías de ecommerce) de Odoo y las asigna a los productos';

    public function handle(OdooCatalog $catalog): int
    {
        try {
            $categorias = $catalog->categoriasEcommerce();
        } catch (OdooException $e) {
            $this->error('  Error de Odoo: ' . $e->getMessage());

            return self::FAILURE;
        }

        $noMarca = array_map('mb_strtoupper', config('odoo.categorias_no_marca', []));
        $vigentes = [];

        foreach ($categorias as $orden => $categoria) {
            $nombre = trim((string) $categoria['name']);

            if ($nombre === '') {
                continue;
            }

            // Una marca de antes (deducida del nombre) con el mismo nombre se
            // reutiliza: así no cambia su id ni el margen que tenga cargado.
            $marca = Brand::where('odoo_id', $categoria['id'])->first()
                ?? Brand::whereNull('odoo_id')->where('name', $nombre)->first()
                ?? new Brand;

            $marca->fill([
                'odoo_id' => $categoria['id'],
                'name' => $nombre,
                'slug' => $this->slugLibre($nombre, $marca->id),
                'sort' => $orden,
                'es_marca' => ! in_array(mb_strtoupper($nombre), $noMarca, true),
            ])->save();

            $vigentes[] = $marca->id;
        }

        $this->info('  Categorías de ecommerce en Odoo: ' . count($vigentes));

        try {
            [$conMarca, $sinMarca] = $this->asignar($catalog, Brand::mapa(), Brand::mapa(soloMarcas: false));
        } catch (OdooException $e) {
            $this->error('  Error de Odoo: ' . $e->getMessage());

            return self::FAILURE;
        }

        // Las que ya no están en Odoo (y las deducidas del nombre, de antes).
        $sobran = Brand::whereNotIn('id', $vigentes ?: [0])->pluck('id');
        Product::whereIn('brand_id', $sobran)->update(['brand_id' => null]);
        Brand::whereIn('id', $sobran)->delete();

        $this->info('  Productos con marca: ' . number_format($conMarca, 0, ',', '.'));
        $this->info('  Productos sin marca: ' . number_format($sinMarca, 0, ',', '.'));

        if ($sobran->isNotEmpty()) {
            $this->info('  Marcas que ya no están en Odoo, borradas: ' . $sobran->count());
        }

        return self::SUCCESS;
    }

    /** @return array{0:int, 1:int} productos con y sin marca */
    private function asignar(OdooCatalog $catalog, array $mapa, array $mapaCompleto): array
    {
        $conMarca = 0;
        $sinMarca = 0;
        $offset = 0;
        $porPagina = 5000;

        do {
            $rows = $catalog->categoriasDeProductos($offset, $porPagina);
            $locales = Product::whereIn('odoo_id', array_column($rows, 'id'))->pluck('id', 'odoo_id');
            $porMarca = [];
            $pivote = [];

            foreach ($rows as $row) {
                $categorias = $row['public_categ_ids'] ?? [];
                $marca = Brand::deCategorias($categorias, $mapa);
                $porMarca[$marca ?? 0][] = $row['id'];
                $marca ? $conMarca++ : $sinMarca++;

                if ($productoId = $locales[$row['id']] ?? null) {
                    foreach (Brand::categoriasDe($categorias, $mapaCompleto) as $brandId) {
                        $pivote[] = ['brand_id' => $brandId, 'product_id' => $productoId];
                    }
                }
            }

            DB::transaction(function () use ($porMarca, $locales, $pivote) {
                foreach ($porMarca as $marca => $odooIds) {
                    foreach (array_chunk($odooIds, 1000) as $lote) {
                        Product::whereIn('odoo_id', $lote)->update(['brand_id' => $marca ?: null]);
                    }
                }

                // Las categorías de esta página se reemplazan de una.
                foreach ($locales->values()->chunk(1000) as $lote) {
                    DB::table('brand_product')->whereIn('product_id', $lote->all())->delete();
                }

                foreach (array_chunk($pivote, 1000) as $lote) {
                    DB::table('brand_product')->insert($lote);
                }
            });

            $offset += count($rows);
        } while (count($rows) === $porPagina);

        return [$conMarca, $sinMarca];
    }

    /** «OSSCA -- FIAT» y «OSSCA FIAT» darían el mismo slug: el segundo lleva un número. */
    private function slugLibre(string $nombre, ?int $id): string
    {
        $base = Str::slug($nombre) ?: 'marca';
        $slug = $base;

        for ($n = 2; Brand::where('slug', $slug)->when($id, fn ($q) => $q->where('id', '!=', $id))->exists(); $n++) {
            $slug = "{$base}-{$n}";
        }

        return $slug;
    }
}
