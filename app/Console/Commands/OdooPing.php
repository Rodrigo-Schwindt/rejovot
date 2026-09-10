<?php

namespace App\Console\Commands;

use App\Services\Odoo\OdooClient;
use App\Services\Odoo\OdooException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Verifica que la conexión, la API Key y los permisos del usuario de servicio
 * estén bien antes de escribir cualquier sincronización.
 */
class OdooPing extends Command
{
    protected $signature = 'odoo:ping {--codigo=BS009.0868 : Código de producto para probar el precio}';

    protected $description = 'Prueba la conexión con Odoo: versión, login, permisos y precio de tarifa';

    public function handle(OdooClient $odoo): int
    {
        Cache::forget('odoo:uid');

        $this->line('');
        $this->line('  <fg=gray>URL:</> ' . config('odoo.url') . '  <fg=gray>BD:</> ' . config('odoo.db'));
        $this->line('');

        try {
            $version = $odoo->version();
            $this->info('  Servidor .......... ' . ($version['server_version'] ?? '?'));

            $uid = $odoo->uid();
            $this->info('  Login ............. uid ' . $uid);

            $usuario = $odoo->read('res.users', [$uid], ['name', 'login', 'share']);
            $usuario = $usuario[0] ?? [];
            $this->info('  Usuario ........... ' . ($usuario['name'] ?? '?') . ' (' . ($usuario['login'] ?? '?') . ')');

            if (! empty($usuario['share'])) {
                $this->warn('  Ojo: es un usuario de portal. Para leer productos y ventas tiene que ser interno.');
            }
        } catch (OdooException $e) {
            $this->line('');
            $this->error('  No se pudo conectar: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->line('');
        $this->line('  <fg=gray>Permisos de lectura</>');

        $modelos = [
            'product.product' => [['sale_ok', '=', true]],
            'product.category' => [],
            'res.partner' => [['customer_rank', '>', 0]],
            'sale.order' => [],
            'account.move' => [['move_type', 'in', ['out_invoice', 'out_refund']]],
            'product.pricelist' => [],
            'product.supplierinfo' => [],
        ];

        foreach ($modelos as $modelo => $dominio) {
            try {
                $total = $odoo->searchCount($modelo, $dominio);
                $this->info(sprintf('  %-24s %s registros', $modelo, number_format($total, 0, ',', '.')));
            } catch (OdooException $e) {
                $this->error(sprintf('  %-24s sin acceso (%s)', $modelo, $e->getMessage()));
            }
        }

        $this->probarPrecio($odoo);

        $this->line('');
        $this->info('  Conexión OK.');
        $this->line('');

        return self::SUCCESS;
    }

    /** El precio real sale de la tarifa: list_price viene en 0 en esta instancia. */
    protected function probarPrecio(OdooClient $odoo): void
    {
        $codigo = (string) $this->option('codigo');

        $this->line('');
        $this->line('  <fg=gray>Precio de tarifa</> (' . $codigo . ')');

        try {
            $productos = $odoo->searchRead('product.product', [
                ['default_code', '=', $codigo],
            ], ['id', 'name', 'default_code', 'qty_available'], ['limit' => 1]);

            if (! $productos) {
                $this->warn('  No se encontró el producto ' . $codigo);

                return;
            }

            $producto = $productos[0];

            $conPrecio = $odoo->read('product.product', [$producto['id']], ['price'], [
                'pricelist' => config('odoo.pricelist_id'),
                'quantity' => 1,
            ]);

            $precio = $conPrecio[0]['price'] ?? null;

            $this->info('  ' . $producto['name']);
            $this->info('  Precio tarifa ' . config('odoo.pricelist_id') . ' ..... $' . number_format((float) $precio, 2, ',', '.'));
            $this->info('  Stock ............... ' . $producto['qty_available']);
        } catch (OdooException $e) {
            $this->error('  No se pudo leer el precio: ' . $e->getMessage());
        }
    }
}
