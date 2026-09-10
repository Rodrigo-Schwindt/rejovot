<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Services\Odoo\OdooCatalog;
use App\Services\Odoo\OdooException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Descuentos puntuales cargados en Odoo como reglas de tarifa
 * (product.pricelist.item), que pueden ir entre fechas.
 *
 * Las reglas aplican por producto, por plantilla o por categoría; acá se
 * resuelven todas a productos concretos.
 */
class SyncOdooOfertas extends Command
{
    protected $signature = 'odoo:sync-ofertas';

    protected $description = 'Trae los descuentos vigentes de la tarifa de Odoo';

    public function handle(OdooCatalog $catalog): int
    {
        try {
            $reglas = $catalog->reglasConDescuento();
        } catch (OdooException $e) {
            $this->error('  Error de Odoo: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->line('  Reglas con descuento en la tarifa: ' . count($reglas));

        $porProducto = [];

        foreach ($reglas as $regla) {
            $descuento = (float) $regla['price_discount'];

            if ($descuento <= 0) {
                continue;
            }

            $datos = [
                'discount_percent' => $descuento,
                'discount_from' => $regla['date_start'] ?: null,
                'discount_to' => $regla['date_end'] ?: null,
            ];

            foreach ($this->productosDeLaRegla($regla) as $id) {
                // Si un producto cae en varias reglas, gana el descuento mayor.
                if (! isset($porProducto[$id]) || $porProducto[$id]['discount_percent'] < $descuento) {
                    $porProducto[$id] = $datos;
                }
            }
        }

        DB::transaction(function () use ($porProducto) {
            // Limpio las ofertas anteriores para no dejar descuentos vencidos.
            Product::whereNotNull('discount_percent')->update([
                'discount_percent' => null,
                'discount_from' => null,
                'discount_to' => null,
            ]);

            foreach (collect($porProducto)->chunk(200) as $tanda) {
                foreach ($tanda as $id => $datos) {
                    Product::where('id', $id)->update($datos);
                }
            }
        });

        $this->info('  Productos con descuento: ' . number_format(count($porProducto), 0, ',', '.'));
        $this->info('  Con oferta vigente hoy: ' . number_format(Product::enOferta()->count(), 0, ',', '.'));

        return self::SUCCESS;
    }

    /**
     * Ids locales de los productos que alcanza una regla.
     *
     * @return array<int, int>
     */
    protected function productosDeLaRegla(array $regla): array
    {
        return match ($regla['applied_on']) {
            '0_product_variant' => Product::where('odoo_id', $regla['product_id'][0] ?? 0)->pluck('id')->all(),
            '1_product' => Product::where('odoo_tmpl_id', $regla['product_tmpl_id'][0] ?? 0)->pluck('id')->all(),
            '2_product_category' => $this->porCategoria($regla['categ_id'][0] ?? 0),
            // La regla global es la base del precio, no una oferta.
            default => [],
        };
    }

    /** Una regla de categoría alcanza también a las subcategorías. */
    protected function porCategoria(int $odooCategId): array
    {
        $categoria = Category::where('odoo_id', $odooCategId)->first();

        if (! $categoria) {
            return [];
        }

        $ids = [$categoria->id];
        $pendientes = [$categoria->id];

        while ($pendientes) {
            $hijas = Category::whereIn('parent_id', $pendientes)->pluck('id')->all();

            if (! $hijas) {
                break;
            }

            $ids = array_merge($ids, $hijas);
            $pendientes = $hijas;
        }

        return Product::whereIn('category_id', $ids)->pluck('id')->all();
    }
}
