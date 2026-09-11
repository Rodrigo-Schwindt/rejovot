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

    public function movimientos(): array
    {
        $partnerId = $this->partnerId();

        if (! $partnerId) {
            return [];
        }

        try {
            $rows = $this->odoo->movimientos($partnerId);
        } catch (OdooException $e) {
            Log::warning('No se pudieron leer los movimientos de Odoo: ' . $e->getMessage());

            return [];
        }

        return array_map(fn (array $row) => $this->comoArray($row), $rows);
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

    private function comoArray(array $row): array
    {
        $vencimiento = $row['date_maturity'] ?: $row['date'];
        [$tipo, $numero] = $this->tipoYNumero($row['move_name'] ?? '');

        return [
            'emision' => $this->fecha($row['date']),
            'vencimiento' => $this->fecha($vencimiento),
            'tipo' => $tipo,
            'numero' => $numero,
            // Lo que suma a la deuda va al debe; lo que la baja, al haber.
            'haber' => (float) $row['debit'] - (float) $row['credit'],
            'saldo' => (float) $row['amount_residual'],
            'importe_origen' => (float) $row['amount_currency'],
            'importe_bruto_origen' => (float) $row['amount_currency'],
            'vencido' => $vencimiento < now()->toDateString(),
            'comprobante' => $row['move_name'] ?? '',
            'move_id' => $row['move_id'][0] ?? null,
        ];
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
