<?php

namespace App\Services\Pedidos;

use App\Contracts\PedidosRepository;
use App\Models\Product;
use App\Services\Odoo\OdooEnvios;
use App\Services\Odoo\OdooException;
use App\Services\Odoo\OdooPedidos;
use App\Services\Sesion\ClienteActivo;
use Illuminate\Support\Facades\Log;

/**
 * Historial de pedidos leído de Odoo, siempre del cliente activo.
 *
 * Sin cliente elegido no hay pedidos: un vendedor tiene que elegir uno de su
 * cartera y un visitante sin login no ve nada.
 */
class PedidosOdoo implements PedidosRepository
{
    public function __construct(
        private OdooPedidos $odoo,
        private ClienteActivo $clienteActivo,
    ) {
    }

    public function pedidos(int $limite = 25): array
    {
        $partnerId = $this->partnerId();

        if (! $partnerId) {
            return [];
        }

        try {
            $rows = $this->odoo->delCliente($partnerId, $limite);
            $lineas = $this->odoo->lineas($rows);
        } catch (OdooException $e) {
            Log::warning('No se pudieron leer los pedidos de Odoo: ' . $e->getMessage());

            return [];
        }

        $productos = $this->productosDeLasLineas($lineas);

        return array_map(
            fn (array $row) => $this->comoArray($row, $lineas[$row['id']] ?? [], $productos),
            $rows,
        );
    }

    public function pedido(string $numero): ?array
    {
        $partnerId = $this->partnerId();

        if (! $partnerId) {
            return null;
        }

        try {
            $row = $this->odoo->porNumero($partnerId, $numero);

            if (! $row) {
                return null;
            }

            $lineas = $this->odoo->lineas([$row]);
        } catch (OdooException $e) {
            Log::warning('No se pudo leer el pedido ' . $numero . ': ' . $e->getMessage());

            return null;
        }

        return $this->comoArray($row, $lineas[$row['id']] ?? [], $this->productosDeLasLineas($lineas));
    }

    /** Cuántos pedidos tiene el cliente en total, para saber si falta mostrar. */
    public function total(): int
    {
        $partnerId = $this->partnerId();

        if (! $partnerId) {
            return 0;
        }

        try {
            return $this->odoo->cuantos($partnerId);
        } catch (OdooException $e) {
            return 0;
        }
    }

    private function partnerId(): ?int
    {
        return $this->clienteActivo->actual()?->odoo_id;
    }

    /**
     * Los productos del pedido salen del espejo local: ahí está el código con
     * el que trabajan el catálogo y el carrito.
     *
     * @return array<int, Product>
     */
    private function productosDeLasLineas(array $lineas): array
    {
        $ids = [];

        foreach ($lineas as $delPedido) {
            foreach ($delPedido as $linea) {
                if (! empty($linea['product_id'][0])) {
                    $ids[] = $linea['product_id'][0];
                }
            }
        }

        if (! $ids) {
            return [];
        }

        return Product::whereIn('odoo_id', array_unique($ids))->get()->keyBy('odoo_id')->all();
    }

    /** Traduce el pedido de Odoo al formato que ya consumen las vistas. */
    private function comoArray(array $row, array $lineasOdoo, array $productos): array
    {
        $lineas = [];
        $aEntregar = 0;
        $entregadas = 0;

        foreach ($lineasOdoo as $linea) {
            $cantidad = (float) $linea['product_uom_qty'];
            $subtotal = (float) $linea['price_subtotal'];
            $producto = $productos[$linea['product_id'][0] ?? 0] ?? null;

            // El costo del envío viaja como una línea más del pedido.
            $esEnvio = ! empty($linea['is_delivery']);

            // La línea del envío nunca se entrega: no cuenta para el estado.
            if (! $esEnvio && $cantidad > 0) {
                $aEntregar++;

                if ((float) $linea['qty_delivered'] >= $cantidad) {
                    $entregadas++;
                }
            }

            $lineas[] = [
                'producto' => [
                    // Si el producto ya no está en el catálogo, queda el texto de la línea.
                    'codigo' => $esEnvio ? '' : ($producto?->code ?? $this->codigoDelTexto($linea['name'])),
                    'nombre' => $esEnvio
                        ? 'Envío · ' . OdooEnvios::limpiarNombre($linea['name'])
                        : ($producto?->name ?? $linea['name']),
                    // El precio del pedido es el que se facturó, con su descuento.
                    'costo' => $cantidad > 0 ? $subtotal / $cantidad : (float) $linea['price_unit'],
                ],
                'cantidad' => $cantidad == (int) $cantidad ? (int) $cantidad : $cantidad,
                'subtotal' => $subtotal,
                'envio' => $esEnvio,
            ];
        }

        return [
            'numero' => $row['name'],
            'fecha' => $this->fecha($row['date_order']),
            // El importe de la tabla es sin IVA, igual que los precios del sitio.
            'importe' => (float) $row['amount_untaxed'],
            'iva' => (float) $row['amount_tax'],
            'total' => (float) $row['amount_total'],
            'estado' => $this->estado($row['state'], $aEntregar, $entregadas),
            'entrega' => $this->entrega($row),
            'lineas' => $lineas,
        ];
    }

    /**
     * Odoo deja casi todos los pedidos en «sale», así que el estado que le
     * importa al cliente sale de las entregas, no del estado del pedido.
     */
    private function estado(string $state, int $aEntregar, int $entregadas): string
    {
        if ($state === 'cancel') {
            return 'cancelado';
        }

        // Presupuesto cargado desde la web que Rejovot todavía no confirmó.
        if (in_array($state, ['draft', 'sent'], true)) {
            return 'revision';
        }

        if ($aEntregar > 0 && $entregadas === $aEntregar) {
            return 'entregado';
        }

        return 'pendiente';
    }

    private function entrega(array $row): string
    {
        $carrier = OdooEnvios::limpiarNombre((string) ($row['carrier_id'][1] ?? ''));

        return $carrier !== '' ? $carrier : 'A convenir';
    }

    private function fecha(?string $fecha): string
    {
        return $fecha ? date('d/m/Y', strtotime($fecha)) : '';
    }

    /** El nombre de la línea viene como «[CODIGO] Descripción». */
    private function codigoDelTexto(string $texto): string
    {
        return preg_match('/^\[([^\]]+)\]/', trim($texto), $m) ? $m[1] : '';
    }
}
