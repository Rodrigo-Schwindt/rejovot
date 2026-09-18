<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Odoo\OdooException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Trae los productos relacionados que Odoo carga en la pestaña Ventas:
 * «Productos alternativos» y «Productos de accesorio».
 *
 * Va aparte del sync del catálogo porque necesita que estén todos los
 * productos ya cargados para poder cruzar los ids.
 */
class SyncOdooRelacionados extends Command
{
    protected $signature = 'odoo:sync-relacionados';

    protected $description = 'Sincroniza productos alternativos y accesorios desde Odoo';

    public function handle(\App\Services\Odoo\OdooClient $odoo): int
    {
        try {
            // Los alternativos apuntan a plantillas; los accesorios, a variantes.
            $rows = $odoo->searchRead('product.template', [
                ['sale_ok', '=', true],
                '|', ['alternative_product_ids', '!=', false], ['accessory_product_ids', '!=', false],
            ], ['alternative_product_ids', 'accessory_product_ids'], [
                'context' => ['active_test' => false],
            ]);
        } catch (OdooException $e) {
            $this->error('  Error de Odoo: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->line('  Productos con relacionados en Odoo: ' . count($rows));

        $porTmpl = Product::pluck('id', 'odoo_tmpl_id');
        $porVariante = Product::pluck('id', 'odoo_id');

        $filas = [];
        $ahora = now();

        foreach ($rows as $row) {
            $origen = $porTmpl[$row['id']] ?? null;

            if (! $origen) {
                continue;
            }

            foreach ($row['alternative_product_ids'] as $tmplId) {
                if (($destino = $porTmpl[$tmplId] ?? null) && $destino !== $origen) {
                    $filas[] = ['product_id' => $origen, 'related_id' => $destino, 'tipo' => 'alternativo', 'created_at' => $ahora, 'updated_at' => $ahora];
                }
            }

            foreach ($row['accessory_product_ids'] as $varianteId) {
                if (($destino = $porVariante[$varianteId] ?? null) && $destino !== $origen) {
                    $filas[] = ['product_id' => $origen, 'related_id' => $destino, 'tipo' => 'accesorio', 'created_at' => $ahora, 'updated_at' => $ahora];
                }
            }
        }

        DB::transaction(function () use ($filas) {
            // delete() y no truncate(): truncate hace commit implícito en MySQL.
            DB::table('product_related')->delete();

            foreach (array_chunk($filas, 1000) as $tanda) {
                DB::table('product_related')->insertOrIgnore($tanda);
            }
        });

        $this->info('  Relaciones guardadas: ' . number_format(DB::table('product_related')->count(), 0, ',', '.'));
        $this->info('    alternativos: ' . DB::table('product_related')->where('tipo', 'alternativo')->count());
        $this->info('    accesorios  : ' . DB::table('product_related')->where('tipo', 'accesorio')->count());

        return self::SUCCESS;
    }
}
