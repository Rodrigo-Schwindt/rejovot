<?php

namespace App\Services\Odoo;

/**
 * Pedidos del cliente en Odoo (sale.order).
 *
 * Se leen en vivo: un pedido cambia de estado todo el tiempo y no tiene sentido
 * espejarlo como el catálogo. Siempre acotado al partner del cliente activo,
 * para que nadie pueda ver los pedidos de otro.
 */
class OdooPedidos
{
    /** Presupuestos y borradores no se muestran: el cliente ve lo confirmado. */
    protected const ESTADOS_VISIBLES = ['sale', 'done', 'cancel'];

    protected const CAMPOS = [
        'name',
        'date_order',
        'amount_untaxed',
        'amount_tax',
        'amount_total',
        'state',
        'carrier_id',
        'client_order_ref',
        'order_line',
    ];

    public function __construct(protected OdooClient $odoo)
    {
    }

    /** Últimos pedidos del cliente, del más nuevo al más viejo. */
    public function delCliente(int $partnerId, int $limite = 25): array
    {
        return $this->odoo->searchRead('sale.order', $this->dominio($partnerId), self::CAMPOS, [
            'limit' => $limite,
            'order' => 'date_order desc, id desc',
        ]);
    }

    /** Un pedido por número, siempre validando que sea de ese cliente. */
    public function porNumero(int $partnerId, string $numero): ?array
    {
        $rows = $this->odoo->searchRead('sale.order', [
            ...$this->dominio($partnerId),
            ['name', '=', $numero],
        ], self::CAMPOS, ['limit' => 1]);

        return $rows[0] ?? null;
    }

    public function cuantos(int $partnerId): int
    {
        return $this->odoo->searchCount('sale.order', $this->dominio($partnerId));
    }

    /**
     * Líneas de varios pedidos en una sola llamada, agrupadas por pedido.
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function lineas(array $pedidos): array
    {
        $ids = [];

        foreach ($pedidos as $pedido) {
            foreach ($pedido['order_line'] ?? [] as $lineaId) {
                $ids[] = $lineaId;
            }
        }

        if (! $ids) {
            return [];
        }

        $rows = $this->odoo->read('sale.order.line', $ids, [
            'order_id',
            'product_id',
            'name',
            'product_uom_qty',
            'qty_delivered',
            'price_unit',
            'price_subtotal',
            'discount',
            'display_type',
            'is_delivery',
        ]);

        $porPedido = [];

        foreach ($rows as $row) {
            // Las secciones y notas del pedido no son productos.
            if (! empty($row['display_type'])) {
                continue;
            }

            $porPedido[$row['order_id'][0]][] = $row;
        }

        return $porPedido;
    }

    protected function dominio(int $partnerId): array
    {
        return [
            ['partner_id', '=', $partnerId],
            ['company_id', '=', config('odoo.company_id')],
            ['state', 'in', self::ESTADOS_VISIBLES],
        ];
    }
}
