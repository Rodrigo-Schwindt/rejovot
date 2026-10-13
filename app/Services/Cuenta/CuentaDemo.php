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

    public function tieneInfo(): bool
    {
        return false;
    }

    public function movimientos(string $cuenta = '', int $limite = 50): array
    {
        if ($cuenta === 'info') {
            return [];
        }

        return [
            [
                'emision' => '05/09/2026',
                'vencimiento' => '05/10/2026',
                'tipo' => 'RCC',
                'numero' => '43107',
                'importe' => 33765.00,
                'pago_a_cuenta' => 0.00,
                'saldo' => 33765.00,
                'a_favor' => false,
                'vencido' => false,
            ],
            [
                'emision' => '28/08/2026',
                'vencimiento' => '27/08/2026',
                'tipo' => 'RCC',
                'numero' => '43102',
                'importe' => 128450.75,
                'pago_a_cuenta' => 0.00,
                'saldo' => 128450.75,
                'a_favor' => false,
                'vencido' => true,
            ],
            [
                'emision' => '22/08/2026',
                'vencimiento' => '21/09/2026',
                'tipo' => 'FAC',
                'numero' => '00012845',
                'importe' => 250924.83,
                'pago_a_cuenta' => 0.00,
                'saldo' => 250924.83,
                'a_favor' => false,
                'vencido' => false,
            ],
            [
                'emision' => '15/08/2026',
                'vencimiento' => '14/08/2026',
                'tipo' => 'FAC',
                'numero' => '00012790',
                'importe' => 180382.22,
                'pago_a_cuenta' => 0.00,
                'saldo' => 180382.22,
                'a_favor' => false,
                'vencido' => true,
            ],
            [
                'emision' => '08/08/2026',
                'vencimiento' => '07/08/2026',
                'tipo' => 'FAC',
                'numero' => '00012701',
                'importe' => 96431.05,
                'pago_a_cuenta' => 0.00,
                'saldo' => 96431.05,
                'a_favor' => false,
                'vencido' => true,
            ],
            [
                'emision' => '30/07/2026',
                'vencimiento' => '29/08/2026',
                'tipo' => 'NC',
                'numero' => '00000341',
                'importe' => 18540.60,
                'pago_a_cuenta' => 0.00,
                'saldo' => 18540.60,
                'a_favor' => true,
                'vencido' => false,
            ],
            [
                'emision' => '18/07/2026',
                'vencimiento' => '17/08/2026',
                'tipo' => 'RCC',
                'numero' => '42980',
                'importe' => 42112.35,
                'pago_a_cuenta' => 0.00,
                'saldo' => 42112.35,
                'a_favor' => false,
                'vencido' => true,
            ],
        ];
    }
}
