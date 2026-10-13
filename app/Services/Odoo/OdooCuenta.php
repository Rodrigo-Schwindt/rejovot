<?php

namespace App\Services\Odoo;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Cuenta corriente y objetivos del cliente en Odoo.
 *
 * Los objetivos salen del módulo `website_sale_automatic_discount`: cada
 * cliente tiene una escala (`sale.category.discount`) con un monto objetivo y
 * un porcentaje, y Odoo calcula cuánto compró **en el mes en curso**.
 */
class OdooCuenta
{
    /**
     * Empresas cuya deuda es la cuenta del cliente: Rejovot y la de «INFO»
     * (PRUEBA). Las otras de la base (BENEIBRAK, A.S.M.) no van.
     *
     * @return array<int, int>
     */
    public function empresas(): array
    {
        return array_values(array_unique(array_filter([
            (int) config('odoo.company_id'),
            (int) config('odoo.company_info_id'),
        ])));
    }

    /**
     * Apuntes de la cuenta por cobrar que todavía tienen saldo. Sin empresa,
     * los de las dos (los saldos van unificados); con empresa, sólo esa (la
     * tabla muestra Rejovot o, con el botón «INFO», la otra).
     */
    protected function dominio(int $partnerId, ?int $empresa = null): array
    {
        return [
            ['partner_id', '=', $partnerId],
            $empresa ? ['company_id', '=', $empresa] : ['company_id', 'in', $this->empresas()],
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

        // Vencido es sólo lo que el cliente debe: una nota de crédito o un recibo
        // a favor con fecha pasada no «vence» ni se resta acá.
        $vencido = $this->sumar(array_merge($this->dominio($partnerId), [
            ['date_maturity', '<', now()->toDateString()],
            ['amount_residual', '>', 0],
        ]));

        return ['total' => $total, 'vencido' => $vencido];
    }

    /** Movimientos pendientes de una empresa, del más nuevo al más viejo. */
    /**
     * Lo que muestra la tabla, como la lista de facturas de Odoo: todas las
     * facturas y notas de crédito, pagadas o no, más lo que tenga saldo (un
     * recibo con plata a favor). Los saldos de arriba siguen usando dominio().
     */
    protected function dominioTabla(int $partnerId, int $empresa): array
    {
        return [
            ['partner_id', '=', $partnerId],
            ['company_id', '=', $empresa],
            ['account_id.internal_type', '=', 'receivable'],
            ['parent_state', '=', 'posted'],
            '|',
            ['amount_residual', '!=', 0],
            ['move_id.move_type', 'in', ['out_invoice', 'out_refund']],
        ];
    }

    /** Comprobantes de una empresa, del más nuevo al más viejo. */
    public function movimientos(int $partnerId, int $empresa, int $limite = 50): array
    {
        return $this->odoo->searchRead('account.move.line', $this->dominioTabla($partnerId, $empresa), [
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
            'order' => 'date desc, id desc',
        ]);
    }

    /** Si el cliente tiene comprobantes en esa empresa (para mostrar «INFO»). */
    public function tieneMovimientos(int $partnerId, int $empresa): bool
    {
        return $this->odoo->searchCount('account.move.line', $this->dominioTabla($partnerId, $empresa)) > 0;
    }

    /**
     * Tipo de cada comprobante (out_invoice, out_refund, entry…). Hace falta
     * para los de la otra empresa, que se numeran sin tipo («REJ/2026/…»).
     *
     * @return array<int, string> id de account.move => move_type
     */
    public function tiposDeComprobante(array $moveIds): array
    {
        if (! $moveIds) {
            return [];
        }

        return collect($this->odoo->read('account.move', array_values(array_unique($moveIds)), ['move_type']))
            ->pluck('move_type', 'id')
            ->all();
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
        $rows = $this->odoo->read('account.move', [$moveId], ['name', 'access_token', 'partner_id', 'company_id']);
        $move = $rows[0] ?? null;

        // Nadie puede bajarse el comprobante de otro cliente ni de otra compañía.
        if (! $move
            || ($move['partner_id'][0] ?? null) !== $partnerId
            || ! in_array($move['company_id'][0] ?? null, $this->empresas(), true)) {
            return null;
        }

        // Los de la otra empresa no siempre tienen el enlace del portal: se le
        // genera uno (es sólo la clave de acceso al PDF, no toca nada contable).
        if (empty($move['access_token'])) {
            $move['access_token'] = (string) Str::uuid();
            $this->odoo->write('account.move', [$moveId], ['access_token' => $move['access_token']]);
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
