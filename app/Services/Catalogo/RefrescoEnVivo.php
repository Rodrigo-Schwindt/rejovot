<?php

namespace App\Services\Catalogo;

use App\Models\Product;
use App\Services\Odoo\OdooClient;
use App\Services\Odoo\OdooException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Trae de Odoo, en el momento, precio, stock, activo y publicado de los
 * productos que alguien está mirando: la página del listado, el detalle y el
 * carrito. Lo guarda en la base local, así el resto del sitio también lo ve.
 *
 * De paso revisa si cambiaron las ofertas (OfertasEnVivo): son reglas de la
 * tarifa y no se ven en el precio del producto.
 *
 * El sync cada 5 minutos (odoo:sync-precios) sigue cubriendo todo el
 * catálogo para los filtros y los totales. Si Odoo no responde rápido, se
 * sigue con el último dato guardado.
 */
class RefrescoEnVivo
{
    /** Un mismo producto no se vuelve a consultar antes de este tiempo. */
    private const SEGUNDOS_ENTRE_CONSULTAS = 10;

    /** Más que esto y se usa el dato guardado. */
    private const TIMEOUT = 4;

    public function __construct(private OdooClient $odoo, private OfertasEnVivo $ofertas)
    {
    }

    /**
     * @param  array<int, string>  $codigos
     * @return bool si cambió algo: la pantalla tiene que volver a leerlos
     */
    public function codigos(array $codigos): bool
    {
        $ofertasNuevas = $this->ofertas->verificar();

        return $this->precios($codigos) || $ofertasNuevas;
    }

    /** @param  array<int, string>  $codigos */
    private function precios(array $codigos): bool
    {
        $codigos = array_values(array_unique(array_filter($codigos)));

        if (! $codigos) {
            return false;
        }

        $productos = Product::whereIn('code', $codigos)
            ->whereNotNull('odoo_id')
            ->get(['id', 'odoo_id', 'code', 'list_price', 'stock', 'active', 'published'])
            // Varios visitantes mirando el mismo producto: una consulta cada tanto.
            ->filter(fn (Product $p) => Cache::add('refresco-vivo:' . $p->odoo_id, true, self::SEGUNDOS_ENTRE_CONSULTAS))
            ->keyBy('odoo_id');

        if ($productos->isEmpty()) {
            return false;
        }

        try {
            $rows = $this->odoo->conTimeout(self::TIMEOUT)->call('product.product', 'read', [
                $productos->keys()->all(),
                ['lst_price_with_margin', 'qty_available', 'active', 'website_published'],
            ], [
                'context' => ['active_test' => false, 'location' => config('odoo.stock_location_id')],
            ]);
        } catch (OdooException $e) {
            // Que el próximo que entre lo vuelva a intentar.
            foreach ($productos->keys() as $id) {
                Cache::forget('refresco-vivo:' . $id);
            }

            Log::info('Refresco en vivo sin respuesta de Odoo: ' . $e->getMessage());

            return false;
        }

        $cambio = false;

        foreach ((array) $rows as $row) {
            if ($local = $productos[$row['id']] ?? null) {
                $cambio = self::aplicar($local, $row) || $cambio;
            }
        }

        return $cambio;
    }

    /**
     * Pasa a la base local lo que dice Odoo. Guarda sólo si cambió algo.
     * Lo usa también el sync de todo el catálogo.
     */
    public static function aplicar(Product $local, array $row): bool
    {
        $local->fill([
            'list_price' => round((float) ($row['lst_price_with_margin'] ?? 0), 2),
            'stock' => (float) ($row['qty_available'] ?? 0),
            'active' => (bool) $row['active'],
            'published' => (bool) $row['website_published'],
        ]);

        if (! $local->isDirty()) {
            return false;
        }

        $local->save();

        return true;
    }
}
