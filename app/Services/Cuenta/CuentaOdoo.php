<?php

namespace App\Services\Cuenta;

use App\Contracts\CuentaRepository;
use App\Services\Odoo\OdooCuenta;
use App\Services\Odoo\OdooException;
use App\Services\Sesion\ClienteActivo;
use Illuminate\Support\Facades\Log;

/**
 * Cuenta corriente leída de Odoo, siempre la del cliente activo.
 *
 * Se lee en vivo: los saldos cambian con cada factura y cada pago, y un espejo
 * quedaría mostrando deuda que ya se cobró.
 */
class CuentaOdoo implements CuentaRepository
{
    public function __construct(
        private OdooCuenta $odoo,
        private ClienteActivo $clienteActivo,
    ) {
    }

    public function saldos(): array
    {
        $partnerId = $this->partnerId();

        if (! $partnerId) {
            return ['vencido' => 0.0, 'total' => 0.0];
        }

        try {
            return $this->odoo->saldos($partnerId);
        } catch (OdooException $e) {
            Log::warning('No se pudieron leer los saldos de Odoo: ' . $e->getMessage());

            return ['vencido' => 0.0, 'total' => 0.0];
        }
    }

    /** Tipo que se muestra para los comprobantes que vienen sin tipo en el nombre. */
    private const TIPOS = [
        'out_invoice' => 'FC',
        'out_refund' => 'NC',
        'entry' => 'REC',
    ];

    public function movimientos(string $cuenta = '', int $limite = 50): array
    {
        $partnerId = $this->partnerId();

        if (! $partnerId) {
            return [];
        }

        $empresa = $cuenta === 'info' ? config('odoo.company_info_id') : config('odoo.company_id');

        try {
            $rows = $this->odoo->movimientos($partnerId, (int) $empresa, $limite);

            // La otra empresa numera «REJ/2026/…», sin el tipo adelante.
            $sinTipo = array_filter($rows, fn (array $row) => ! str_contains($this->nombreComprobante($row), ' '));
            $tipos = $this->odoo->tiposDeComprobante(array_map(fn (array $row) => $row['move_id'][0], $sinTipo));
        } catch (OdooException $e) {
            Log::warning('No se pudieron leer los movimientos de Odoo: ' . $e->getMessage());

            return [];
        }

        return array_map(fn (array $row) => $this->comoArray($row, $tipos), $rows);
    }

    public function tieneInfo(): bool
    {
        $partnerId = $this->partnerId();

        if (! $partnerId || ! config('odoo.company_info_id')) {
            return false;
        }

        try {
            return $this->odoo->tieneMovimientos($partnerId, (int) config('odoo.company_info_id'));
        } catch (OdooException $e) {
            Log::warning('No se pudo revisar la cuenta INFO en Odoo: ' . $e->getMessage());

            return false;
        }
    }

    /** Objetivo de compra del mes; null si el cliente no tiene escala asignada. */
    public function objetivos(): ?array
    {
        $partnerId = $this->partnerId();

        if (! $partnerId) {
            return null;
        }

        try {
            return $this->odoo->objetivos($partnerId);
        } catch (OdooException $e) {
            Log::warning('No se pudieron leer los objetivos de Odoo: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * PDF del comprobante, emitido por Odoo. Null si no es del cliente activo.
     *
     * @return array{nombre:string, contenido:string}|null
     */
    public function comprobante(int $moveId): ?array
    {
        $partnerId = $this->partnerId();

        if (! $partnerId) {
            return null;
        }

        try {
            return $this->odoo->comprobantePdf($moveId, $partnerId);
        } catch (OdooException $e) {
            Log::warning('No se pudo bajar el comprobante ' . $moveId . ': ' . $e->getMessage());

            return null;
        }
    }

    private function partnerId(): ?int
    {
        return $this->clienteActivo->actual()?->odoo_id;
    }

    private function comoArray(array $row, array $tipos = []): array
    {
        $vencimiento = $row['date_maturity'] ?: $row['date'];
        $comprobante = $this->nombreComprobante($row);
        [$tipo, $numero] = $this->tipoYNumero($comprobante);

        // Sin tipo en el nombre: se toma del comprobante en Odoo.
        if ($tipo === '—' && isset($tipos[$row['move_id'][0] ?? 0])) {
            $tipo = self::TIPOS[$tipos[$row['move_id'][0]]] ?? '—';
        }

        // Como lo pidió Rejovot: las notas de crédito van en negativo; un recibo
        // con plata de más (el cliente pagó de más) va en positivo y «a favor».
        $notaCredito = str_starts_with(mb_strtoupper($tipo), 'NC');
        $signo = $notaCredito ? -1 : 1;
        $importe = abs((float) $row['debit'] - (float) $row['credit']);
        $saldo = abs((float) $row['amount_residual']);
        $aFavor = ! $notaCredito && (float) $row['amount_residual'] < 0;

        return [
            'emision' => $this->fecha($row['date']),
            'vencimiento' => $this->fecha($vencimiento),
            'tipo' => $tipo,
            'numero' => $numero,
            'importe' => $signo * $importe,
            // Lo que ya se pagó (o se aplicó) del comprobante; sin pagos, 0.
            'pago_a_cuenta' => $signo * max(0, round($importe - $saldo, 2)),
            // Lo que falta pagar; sin pagos, igual al importe.
            'saldo' => $signo * $saldo,
            'a_favor' => $aFavor,
            // Como en Odoo: «Pagado» si no le queda saldo, si no «Pendiente de pago».
            'estado' => match (true) {
                abs((float) $row['amount_residual']) < 0.005 => 'pagado',
                $aFavor => 'a_favor',
                default => 'pendiente',
            },
            'nota_credito' => $notaCredito,
            'linea' => $row['id'] ?? null,
            // Sólo vence lo que el cliente debe: ni las notas de crédito ni lo a favor.
            'vencido' => (float) $row['amount_residual'] > 0 && $vencimiento < now()->toDateString(),
            'comprobante' => $comprobante,
            // Odoo sólo entrega el PDF de facturas y notas de crédito: los
            // recibos (son pagos) quedan sin descarga.
            'move_id' => str_starts_with(mb_strtoupper($tipo), 'REC') ? null : ($row['move_id'][0] ?? null),
        ];
    }

    /**
     * Nombre actual del comprobante. Se toma del asiento y no del `move_name`
     * del apunte: en Odoo hay apuntes con un nombre viejo guardado (una «NC A»
     * que no existe sobre lo que hoy es una «FC A»). Odoo le agrega la
     * referencia entre paréntesis al nombre a mostrar: se le saca.
     */
    private function nombreComprobante(array $row): string
    {
        $delAsiento = trim(preg_replace('/\s*\(.*\)$/', '', (string) ($row['move_id'][1] ?? '')));

        return $delAsiento !== '' ? $delAsiento : trim((string) ($row['move_name'] ?? ''));
    }

    /**
     * Odoo nombra los comprobantes «FC A 0005-00089388»: adelante el tipo y
     * atrás el número.
     */
    private function tipoYNumero(string $nombre): array
    {
        $nombre = trim($nombre);

        if ($nombre === '') {
            return ['—', '—'];
        }

        $partes = preg_split('/\s+/', $nombre);
        $numero = array_pop($partes);

        return [implode(' ', $partes) ?: '—', $numero];
    }

    private function fecha(?string $fecha): string
    {
        return $fecha ? date('d/m/Y', strtotime($fecha)) : '';
    }
}
