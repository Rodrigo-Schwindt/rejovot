<?php

namespace App\Services\Odoo;

use Illuminate\Support\Facades\Http;

/**
 * Cuenta corriente y objetivos del cliente en Odoo.
 *
 * Los objetivos salen del módulo `website_sale_automatic_discount`: cada
 * cliente tiene una escala (`sale.category.discount`) con un monto objetivo y
 * un porcentaje, y Odoo calcula cuánto compró **en el mes en curso**.
 */
class OdooCuenta
{
    /** Apuntes de la cuenta por cobrar que todavía tienen saldo. */
    protected function dominio(int $partnerId): array
    {
        return [
            ['partner_id', '=', $partnerId],
            ['account_id.internal_type', '=', 'receivable'],
            ['parent_state', '=', 'posted'],
            ['amount_residual', '!=', 0],
        ];
    }

    public function __construct(protected OdooClient $odoo)
    {
    }

    /** Saldo total y cuánto de eso ya está vencido. */
    public function saldos(int $partnerId): array
    {
        $total = $this->sumar($this->dominio($partnerId));

        $vencido = $this->sumar(array_merge($this->dominio($partnerId), [
            ['date_maturity', '<', now()->toDateString()],
        ]));

        return ['total' => $total, 'vencido' => $vencido];
    }

    /** Movimientos pendientes, del más nuevo al más viejo. */
    public function movimientos(int $partnerId, int $limite = 100): array
    {
        return $this->odoo->searchRead('account.move.line', $this->dominio($partnerId), [
            'move_id',
            'move_name',
            'date',
            'date_maturity',
            'debit',
            'credit',
            'amount_residual',
            'amount_currency',
            'currency_id',
        ], [
            'limit' => $limite,
            'order' => 'date_maturity desc, id desc',
        ]);
    }

    /**
     * Objetivo de compra del cliente y cómo viene en el mes.
     *
     * `current_purchase_amount` es lo comprado en el período en curso, que es
     * mensual: coincide con los pedidos confirmados del mes, sin IVA.
     */
    public function objetivos(int $partnerId): ?array
    {
        $rows = $this->odoo->read('res.partner', [$partnerId], [
            'active_discount',
            'current_discount',
            'current_purchase_amount',
            'remaining_target_amount',
            'target_amount',
            'discount_id',
        ]);

        $partner = $rows[0] ?? null;

        if (! $partner) {
            return null;
        }

        return [
            'escala' => $this->escala($partner['discount_id'][0] ?? null),
            'descuento_activo' => (float) $partner['active_discount'],
            'objetivo' => (float) $partner['target_amount'],
            'comprado' => (float) $partner['current_purchase_amount'],
            'acumulado' => (float) $partner['current_discount'],
            'restante' => (float) $partner['remaining_target_amount'],
        ];
    }

    /** Categoría y porcentaje de la escala asignada al cliente. */
    protected function escala(?int $id): ?array
    {
        if (! $id) {
            return null;
        }

        $rows = $this->odoo->read('sale.category.discount', [$id], [
            'category_id',
            'target_amount',
            'discount_percentage',
        ]);

        if (! $rows) {
            return null;
        }

        return [
            'categoria' => $rows[0]['category_id'][1] ?? '',
            'objetivo' => (float) $rows[0]['target_amount'],
            'porcentaje' => (float) $rows[0]['discount_percentage'],
        ];
    }

    /**
     * PDF del comprobante, tal cual lo emite Odoo.
     *
     * La API no expone el generador de PDF (el método es privado), pero el
     * portal sí lo sirve con el token de acceso del propio comprobante. El
     * token no sale nunca de acá: la descarga la hace el servidor.
     *
     * @return array{nombre:string, contenido:string}|null
     */
    public function comprobantePdf(int $moveId, int $partnerId): ?array
    {
        $rows = $this->odoo->read('account.move', [$moveId], ['name', 'access_token', 'partner_id']);
        $move = $rows[0] ?? null;

        // Nadie puede bajarse el comprobante de otro cliente.
        if (! $move || ($move['partner_id'][0] ?? null) !== $partnerId || empty($move['access_token'])) {
            return null;
        }

        $respuesta = Http::timeout(60)->get(
            rtrim((string) config('odoo.url'), '/') . '/my/invoices/' . $moveId,
            ['access_token' => $move['access_token'], 'report_type' => 'pdf', 'download' => 'true'],
        );

        if (! $respuesta->successful() || ! str_starts_with($respuesta->body(), '%PDF')) {
            return null;
        }

        return [
            // El nombre trae barras y espacios: se limpia para el archivo.
            'nombre' => preg_replace('/[^A-Za-z0-9 ._-]+/', '-', $move['name']) . '.pdf',
            'contenido' => $respuesta->body(),
        ];
    }

    protected function sumar(array $dominio): float
    {
        $grupo = $this->odoo->call('account.move.line', 'read_group', [
            $dominio, ['amount_residual'], [],
        ]);

        return (float) ($grupo[0]['amount_residual'] ?? 0);
    }
}
