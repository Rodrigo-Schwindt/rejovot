<?php

namespace App\Services\Catalogo;

use App\Models\Category;
use App\Models\Product;
use App\Services\Odoo\OdooCatalog;
use Illuminate\Support\Facades\DB;

/**
 * Guarda en la base local productos leídos de Odoo. Lo usan el sync del
 * catálogo y el refresco de precios (para los que falten). El costo de
 * Rejovot no se sincroniza.
 */
class GuardarProductos
{
    /** @var array<int, float>|null id de impuesto => porcentaje */
    private ?array $tasas = null;

    /** @var array<int, int>|null odoo_id de categoría => id local */
    private ?array $categorias = null;

    public function __construct(private OdooCatalog $catalog)
    {
    }

    /**
     * @return array<int, string> filas que no se pudieron guardar
     */
    public function guardar(array $rows): array
    {
        $fallidos = [];
        $categorias = $this->categorias ??= Category::pluck('id', 'odoo_id')->all();

        // La fecha que cuenta para el sync incremental es la más nueva entre la
        // variante y su plantilla (ahí se despublica o se cambia el precio).
        $plantillas = $this->catalog->fechasPlantillas(
            array_filter(array_map(fn (array $row) => $row['product_tmpl_id'][0] ?? null, $rows)),
        );

        DB::transaction(function () use ($rows, $categorias, $plantillas, &$fallidos) {
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
                        'tax_percent' => $this->iva($row['taxes_id'] ?? []),
                        'stock' => $row['qty_available'] ?? 0,
                        'active' => (bool) $row['active'],
                        'published' => (bool) $row['website_published'],
                        'odoo_write_date' => max($row['write_date'], $plantillas[$tmplId] ?? ''),
                    ]);
                } catch (\Throwable $e) {
                    // Una fila rara no puede cortar una sincronización de 55k.
                    $fallidos[] = $row['id'] . ': ' . $e->getMessage();
                }
            }
        });

        return $fallidos;
    }

    /**
     * IVA del producto. Los impuestos son varios (IVA y percepciones); las
     * percepciones están en 0, así que sumar los porcentuales da el IVA.
     */
    private function iva(array $taxIds): float
    {
        $this->tasas ??= collect($this->catalog->impuestosDeVenta())
            ->pluck('amount', 'id')
            ->map(fn ($v) => (float) $v)
            ->all();

        $total = 0.0;

        foreach ($taxIds as $id) {
            $total += $this->tasas[$id] ?? 0;
        }

        return $total > 0 ? $total : 21.0;
    }

    /** Recorta un valor de Odoo al largo que soporta la columna. */
    private function recortar($valor, int $largo): ?string
    {
        $valor = trim((string) ($valor ?: ''));

        if ($valor === '') {
            return null;
        }

        return mb_substr($valor, 0, $largo);
    }
}
