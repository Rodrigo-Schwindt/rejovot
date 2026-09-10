<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Salesperson;
use App\Services\Odoo\OdooClientes;
use App\Services\Odoo\OdooException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncOdooClientes extends Command
{
    protected $signature = 'odoo:sync-clientes
        {--full : Trae todos los clientes en vez de sólo lo modificado}
        {--limit=0 : Corta después de N clientes (para probar)}
        {--page=500 : Clientes por página}';

    protected $description = 'Sincroniza clientes y vendedores desde Odoo';

    protected array $fallidos = [];

    public function handle(OdooClientes $odoo): int
    {
        $inicio = microtime(true);

        try {
            $this->syncVendedores($odoo);

            $portales = $this->mapaPortales($odoo);

            $since = $this->option('full') ? null : Customer::max('odoo_write_date');
            $this->line($since ? "  Cambios desde {$since}" : '  Sincronización completa');

            $pendientes = $odoo->clientesCount($since);
            $tope = (int) $this->option('limit');
            $porPagina = max(50, (int) $this->option('page'));

            if ($tope > 0) {
                $pendientes = min($pendientes, $tope);
                $porPagina = min($porPagina, $tope);
            }

            $this->line('  Clientes a procesar: ' . number_format($pendientes, 0, ',', '.'));

            if ($pendientes === 0) {
                $this->info('  Nada para actualizar.');

                return self::SUCCESS;
            }

            $barra = $this->output->createProgressBar($pendientes);
            $barra->start();

            $vendedores = Salesperson::pluck('id', 'odoo_id')->all();
            $offset = 0;
            $total = 0;

            do {
                $rows = $odoo->clientesPage($since, $offset, $porPagina);

                if (! $rows) {
                    break;
                }

                $this->guardarPagina($rows, $vendedores, $portales);

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
            $this->info("  {$total} clientes sincronizados en {$segundos}s.");
            $this->info('  Total en base: ' . number_format(Customer::count(), 0, ',', '.'));
            $this->info('  Con vendedor: ' . number_format(Customer::whereNotNull('salesperson_id')->count(), 0, ',', '.'));
            $this->info('  Con portal: ' . number_format(Customer::where('has_portal', true)->count(), 0, ',', '.'));

            if ($this->fallidos) {
                $this->newLine();
                $this->warn('  ' . count($this->fallidos) . ' cliente(s) no se pudieron guardar:');

                foreach (array_slice($this->fallidos, 0, 5) as $fallido) {
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

    protected function guardarPagina(array $rows, array $vendedores, array $portales): void
    {
        DB::transaction(function () use ($rows, $vendedores, $portales) {
            foreach ($rows as $row) {
                $vendedorOdoo = $row['user_id'][0] ?? null;
                $portal = $portales[$row['id']] ?? null;

                try {
                    Customer::updateOrCreate(['odoo_id' => $row['id']], [
                        'name' => mb_substr((string) $row['name'], 0, 255),
                        'vat' => $row['vat'] ?: null,
                        'email' => $row['email'] ?: null,
                        'phone' => $row['phone'] ?: null,
                        'city' => $row['city'] ?: null,
                        'salesperson_id' => $vendedorOdoo ? ($vendedores[$vendedorOdoo] ?? null) : null,
                        'pricelist_odoo_id' => $row['property_product_pricelist'][0] ?? null,
                        'price_discount' => $row['price_discount'] ?? 0,
                        'price_margin' => $row['price_margin'] ?? 0,
                        'credit' => $row['credit'] ?? 0,
                        'credit_limit' => $row['credit_limit'] ?? 0,
                        'portal_login' => $portal,
                        'has_portal' => (bool) $portal,
                        'active' => (bool) $row['active'],
                        'odoo_write_date' => $row['write_date'],
                    ]);
                } catch (\Throwable $e) {
                    $this->fallidos[] = $row['id'] . ': ' . $e->getMessage();
                }
            }
        });
    }

    protected function syncVendedores(OdooClientes $odoo): void
    {
        $rows = $odoo->vendedores();

        foreach ($rows as $row) {
            Salesperson::updateOrCreate(['odoo_id' => $row['id']], [
                'name' => $row['name'],
                'login' => $row['login'] ?? null,
                'active' => (bool) ($row['active'] ?? true),
            ]);
        }

        $this->info('  Vendedores sincronizados: ' . count($rows));
    }

    /** @return array<int, string> partner_id => login del usuario de portal */
    protected function mapaPortales(OdooClientes $odoo): array
    {
        $mapa = [];

        foreach ($odoo->usuariosPortal() as $usuario) {
            $partnerId = $usuario['partner_id'][0] ?? null;

            if ($partnerId) {
                $mapa[$partnerId] = $usuario['login'];
            }
        }

        $this->info('  Usuarios de portal: ' . count($mapa));

        return $mapa;
    }
}
