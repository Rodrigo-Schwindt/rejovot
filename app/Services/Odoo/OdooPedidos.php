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
    /** Lo confirmado, más los presupuestos que el propio cliente cargó desde la web. */
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

    /**
     * Crea el pedido en Odoo como presupuesto (borrador), para que Rejovot lo
     * revise y lo confirme desde el ERP.
     *
     * Los precios NO se mandan: Odoo los calcula con la tarifa del cliente al
     * crear cada línea, igual que cuando cargan un pedido a mano. Lo único que
     * se manda con precio es la línea del envío, que es como la arma Odoo.
     *
     * @param  array<int, array{product_id:int, cantidad:float}>  $lineas
     * @param  array{id:int, producto_id:?int, importe:float}|null  $envio
     * @return array{id:int, name:string}
     */
    public function crear(int $partnerId, array $lineas, ?array $envio, ?int $vendedorUid, string $observaciones = ''): array
    {
        $partner = $this->odoo->read('res.partner', [$partnerId], [
            'property_payment_term_id',
            'property_account_position_id',
            'user_id',
        ])[0] ?? [];

        $orderLines = array_map(fn (array $l) => [0, 0, [
            'product_id' => $l['product_id'],
            'product_uom_qty' => $l['cantidad'],
        ]], $lineas);

        if ($envio && $envio['producto_id']) {
            $orderLines[] = [0, 0, [
                'product_id' => $envio['producto_id'],
                'product_uom_qty' => 1,
                'price_unit' => $envio['importe'],
                'is_delivery' => true,
            ]];
        }

        $id = $this->odoo->create('sale.order', [
            'partner_id' => $partnerId,
            'company_id' => config('odoo.company_id'),
            'warehouse_id' => config('odoo.warehouse_id'),
            // Lo que en pantalla pone el onchange del cliente.
            'payment_term_id' => $partner['property_payment_term_id'][0] ?? false,
            'fiscal_position_id' => $partner['property_account_position_id'][0] ?? false,
            // La venta se le acredita al vendedor que la cargó; si no, al de la cartera.
            'user_id' => $vendedorUid ?: ($partner['user_id'][0] ?? false),
            'carrier_id' => $envio['id'] ?? false,
            'origin' => 'Web',
            'order_line' => $orderLines,
        ]);

        if (trim($observaciones) !== '') {
            // Al chatter del pedido: ahí lo ve quien lo prepara.
            $this->odoo->call('sale.order', 'message_post', [[$id]], [
                'body' => 'Mensaje del cliente desde la web: ' . e(trim($observaciones)),
                'message_type' => 'comment',
            ]);
        }

        $creado = $this->odoo->read('sale.order', [$id], ['name'])[0] ?? [];

        return ['id' => $id, 'name' => $creado['name'] ?? (string) $id];
    }

    protected function dominio(int $partnerId): array
    {
        return [
            ['partner_id', '=', $partnerId],
            ['company_id', '=', config('odoo.company_id')],
            '|',
            ['state', 'in', self::ESTADOS_VISIBLES],
            '&', ['state', 'in', ['draft', 'sent']], ['origin', '=', 'Web'],
        ];
    }
}
