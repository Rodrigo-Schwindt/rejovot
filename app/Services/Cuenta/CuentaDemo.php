<?php

namespace App\Services\Cuenta;

use App\Contracts\CuentaRepository;

/**
 * Cuenta corriente hardcodeada. Los saldos salen de los propios movimientos
 * para que la pantalla cierre; en Odoo los trae la cuenta del cliente.
 */
class CuentaDemo implements CuentaRepository
{
    public function saldos(): array
    {
        $movimientos = $this->movimientos();

        return [
            'vencido' => collect($movimientos)->where('vencido', true)->sum('saldo'),
            'total' => collect($movimientos)->sum('saldo'),
        ];
    }

    /** El demo no simula objetivos: los trae Odoo. */
    public function objetivos(): ?array
    {
        return null;
    }

    public function movimientos(): array
    {
        return [
            [
                'emision' => '05/09/2026',
                'vencimiento' => '05/10/2026',
                'tipo' => 'RCC',
                'numero' => '43107',
                'haber' => 33765.00,
                'saldo' => 33765.00,
                'importe_origen' => 172702.39,
                'importe_bruto_origen' => 172702.39,
                'vencido' => false,
            ],
            [
                'emision' => '28/08/2026',
                'vencimiento' => '27/08/2026',
                'tipo' => 'RCC',
                'numero' => '43102',
                'haber' => 128450.75,
                'saldo' => 128450.75,
                'importe_origen' => 128450.75,
                'importe_bruto_origen' => 128450.75,
                'vencido' => true,
            ],
            [
                'emision' => '22/08/2026',
                'vencimiento' => '21/09/2026',
                'tipo' => 'FAC',
                'numero' => '00012845',
                'haber' => 250924.83,
                'saldo' => 250924.83,
                'importe_origen' => 250924.83,
                'importe_bruto_origen' => 250924.83,
                'vencido' => false,
            ],
            [
                'emision' => '15/08/2026',
                'vencimiento' => '14/08/2026',
                'tipo' => 'FAC',
                'numero' => '00012790',
                'haber' => 180382.22,
                'saldo' => 180382.22,
                'importe_origen' => 180382.22,
                'importe_bruto_origen' => 180382.22,
                'vencido' => true,
            ],
            [
                'emision' => '08/08/2026',
                'vencimiento' => '07/08/2026',
                'tipo' => 'FAC',
                'numero' => '00012701',
                'haber' => 96431.05,
                'saldo' => 96431.05,
                'importe_origen' => 96431.05,
                'importe_bruto_origen' => 96431.05,
                'vencido' => true,
            ],
            [
                'emision' => '30/07/2026',
                'vencimiento' => '29/08/2026',
                'tipo' => 'NC',
                'numero' => '00000341',
                'haber' => -18540.60,
                'saldo' => -18540.60,
                'importe_origen' => -18540.60,
                'importe_bruto_origen' => -18540.60,
                'vencido' => false,
            ],
            [
                'emision' => '18/07/2026',
                'vencimiento' => '17/08/2026',
                'tipo' => 'RCC',
                'numero' => '42980',
                'haber' => 42112.35,
                'saldo' => 42112.35,
                'importe_origen' => 42112.35,
                'importe_bruto_origen' => 42112.35,
                'vencido' => true,
            ],
        ];
    }
}
