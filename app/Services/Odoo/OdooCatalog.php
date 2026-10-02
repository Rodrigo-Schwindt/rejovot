<?php

namespace App\Services\Odoo;

/**
 * Lecturas del catálogo en Odoo. No toca la base local: eso lo hace el comando
 * de sincronización.
 *
 * Precios: el módulo product_price_margin arma la cadena
 *   costo Rejovot = supplierinfo.price × (1 + purchase_margin/100)
 *   precio lista  = costo Rejovot × (1 + sale_margin/100)  → lst_price_with_margin
 * El costo de Rejovot y purchase_margin NO se leen desde acá: son internos y no
 * pueden llegar al sitio. El precio de cada cliente se pide en vivo con su tarifa.
 */
class OdooCatalog
{
    /** Depósito propio: el resto son de otras compañías, Mercadolibre o prueba. */
    protected function contextoStock(): array
    {
        return ['location' => config('odoo.stock_location_id')];
    }

    /** Los productos son compartidos entre compañías; igual se acota a la nuestra. */
    protected function dominioBase(): array
    {
        return [
            ['sale_ok', '=', true],
            '|', ['company_id', '=', false], ['company_id', '=', config('odoo.company_id')],
        ];
    }

    public function __construct(protected OdooClient $odoo)
    {
    }

    /** Impuestos de venta de la compañía, para saber el % de cada producto. */
    public function impuestosDeVenta(): array
    {
        return $this->odoo->searchRead('account.tax', [
            ['type_tax_use', '=', 'sale'],
            ['company_id', '=', config('odoo.company_id')],
            ['amount_type', '=', 'percent'],
        ], ['amount']);
    }

    public function categories(): array
    {
        return $this->odoo->searchRead('product.category', [], ['name', 'complete_name', 'parent_id']);
    }

    /** Campos de producto que guarda el catálogo local. */
    protected const CAMPOS_PRODUCTO = [
        'id',
        'product_tmpl_id',
        'default_code',
        'name',
        'oem_code',
        // Almacenable / consumible / servicio.
        'type',
        // Imágenes adicionales (product.image); la principal sale del template.
        'product_template_image_ids',
        'categ_id',
        'qty_available',
        // Es el precio de lista público y coincide con el de la tarifa.
        'lst_price_with_margin',
        // «Impuestos cliente»: define el IVA de la línea.
        'taxes_id',
        'active',
        'website_published',
        'write_date',
    ];

    /**
     * Lo modificado desde $since. Publicado, activo y precio de lista viven en
     * la plantilla (product.template): al despublicar o cambiar el precio sólo
     * cambia la fecha de la plantilla, no la de la variante. Se miran las dos.
     */
    protected function dominioCambios(?string $since): array
    {
        $domain = $this->dominioBase();

        if ($since) {
            array_push($domain, '|', ['write_date', '>', $since], ['product_tmpl_id.write_date', '>', $since]);
        }

        return $domain;
    }

    /** Una página de productos, opcionalmente sólo los modificados desde $since. */
    public function productsPage(?string $since, int $offset, int $limit = 500): array
    {
        return $this->odoo->searchRead('product.product', $this->dominioCambios($since), self::CAMPOS_PRODUCTO, [
            'offset' => $offset,
            'limit' => $limit,
            'order' => 'write_date asc, id asc',
            'context' => ['active_test' => false] + $this->contextoStock(),
        ]);
    }

    /** Productos completos por id (los que faltan en la base local). */
    public function productsByIds(array $ids): array
    {
        if (! $ids) {
            return [];
        }

        return $this->odoo->searchRead('product.product', [...$this->dominioBase(), ['id', 'in', array_values($ids)]], self::CAMPOS_PRODUCTO, [
            'context' => ['active_test' => false] + $this->contextoStock(),
        ]);
    }

    /** @return array<int, string> id de plantilla => write_date */
    public function fechasPlantillas(array $tmplIds): array
    {
        if (! $tmplIds) {
            return [];
        }

        $rows = $this->odoo->call('product.template', 'read', [array_values(array_unique($tmplIds)), ['write_date']], [
            'context' => ['active_test' => false],
        ]);

        return collect($rows)->pluck('write_date', 'id')->all();
    }

    /**
     * Lo que cambia sin dejar rastro en la fecha de modificación: el precio se
     * calcula al leerlo (costo, margen, cotización) y el stock sale de los
     * movimientos. Página liviana, ordenada por id, para releer todo seguido.
     */
    public function estadosPage(int $offset, int $limit = 2000): array
    {
        return $this->odoo->searchRead('product.product', $this->dominioBase(), [
            'lst_price_with_margin',
            'qty_available',
            'active',
            'website_published',
        ], [
            'offset' => $offset,
            'limit' => $limit,
            'order' => 'id asc',
            'context' => ['active_test' => false] + $this->contextoStock(),
        ]);
    }

    public function productsCount(?string $since): int
    {
        return (int) $this->odoo->call('product.product', 'search_count', [$this->dominioCambios($since)], [
            'context' => ['active_test' => false],
        ]);
    }

    /**
     * Precio para un cliente concreto: sale de la tarifa del partner, nunca de
     * una cuenta hecha en PHP.
     *
     * @return array<int, float>
     */
    public function prices(array $productIds, ?int $pricelistId = null, ?int $partnerId = null, float $qty = 1): array
    {
        if (! $productIds) {
            return [];
        }

        $context = ['pricelist' => $pricelistId ?? config('odoo.pricelist_id'), 'quantity' => $qty];

        if ($partnerId) {
            $context['partner'] = $partnerId;
        }

        $rows = $this->odoo->read('product.product', $productIds, ['price'], $context);

        return collect($rows)->pluck('price', 'id')->map(fn ($v) => (float) $v)->all();
    }

    /** Stock del depósito propio. */
    public function stock(array $productIds): array
    {
        if (! $productIds) {
            return [];
        }

        $rows = $this->odoo->read('product.product', $productIds, ['qty_available', 'virtual_available'], $this->contextoStock());

        return collect($rows)->keyBy('id')->all();
    }

    /**
     * Reglas de la tarifa con descuento, vigentes o programadas. Se traen todas
     * y las fechas se evalúan acá, así una oferta futura ya queda cargada.
     */
    public function reglasConDescuento(?int $pricelistId = null): array
    {
        return $this->odoo->searchRead('product.pricelist.item', [
            ['pricelist_id', '=', $pricelistId ?? config('odoo.pricelist_id')],
            ['price_discount', '>', 0],
            ['active', '=', true],
        ], [
            'applied_on', 'compute_price', 'base', 'price_discount',
            'product_id', 'product_tmpl_id', 'categ_id',
            'date_start', 'date_end', 'min_quantity',
        ], ['order' => 'applied_on asc, id asc']);
    }

    /** Ítems de tarifa vigentes con descuento: son las "Ofertas" del sitio. */
    public function offers(?int $pricelistId = null): array
    {
        $pricelistId ??= config('odoo.pricelist_id');
        $ahora = now()->format('Y-m-d H:i:s');

        return $this->odoo->searchRead('product.pricelist.item', [
            ['pricelist_id', '=', $pricelistId],
            '|', ['date_start', '=', false], ['date_start', '<=', $ahora],
            '|', ['date_end', '=', false], ['date_end', '>=', $ahora],
            ['price_discount', '>', 0],
        ], ['product_tmpl_id', 'product_id', 'price_discount', 'date_start', 'date_end']);
    }
}
