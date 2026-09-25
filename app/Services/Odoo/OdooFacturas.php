<?php

namespace App\Services\Odoo;

/**
 * Facturas de un cliente en Odoo, para que al hacer un reclamo pueda elegir
 * sobre qué compra y qué artículo reclama.
 */
class OdooFacturas
{
    public function __construct(protected OdooClient $odoo)
    {
    }

    /** Últimas facturas del cliente, de la más nueva a la más vieja. */
    public function delCliente(int $partnerId, int $limite = 60): array
    {
        return $this->odoo->searchRead('account.move', [
            ['partner_id', '=', $partnerId],
            ['move_type', '=', 'out_invoice'],
            ['state', '=', 'posted'],
            ['company_id', '=', config('odoo.company_id')],
        ], ['name', 'invoice_date', 'amount_total'], [
            'limit' => $limite,
            'order' => 'invoice_date desc, id desc',
        ]);
    }

    /**
     * Artículos de una factura, siempre validando que sea de ese cliente.
     *
     * @return array<int, array{product_id:int, nombre:string, cantidad:float}>
     */
    public function lineas(int $moveId, int $partnerId): array
    {
        $move = $this->odoo->read('account.move', [$moveId], ['partner_id', 'invoice_line_ids'])[0] ?? null;

        if (! $move || ($move['partner_id'][0] ?? null) !== $partnerId || ! $move['invoice_line_ids']) {
            return [];
        }

        $rows = $this->odoo->read('account.move.line', $move['invoice_line_ids'], [
            'product_id', 'name', 'quantity', 'display_type',
        ]);

        $lineas = [];

        foreach ($rows as $row) {
            // Secciones, notas y líneas sin producto (el envío, por ejemplo) no se reclaman.
            if (! empty($row['display_type']) || empty($row['product_id'])) {
                continue;
            }

            $lineas[] = [
                'product_id' => $row['product_id'][0],
                'nombre' => $row['name'],
                'cantidad' => (float) $row['quantity'],
            ];
        }

        return $lineas;
    }
}
