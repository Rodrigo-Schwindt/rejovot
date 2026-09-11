<?php

namespace App\Livewire\Vistas\Cuenta;

use App\Contracts\CuentaRepository;
use App\Services\Sesion\ClienteActivo;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Cuenta corriente del cliente activo, leída de Odoo.
 * Sin cliente elegido no hay saldos que mostrar.
 */
#[Layout('layouts.public')]
class EstadoCuentaPage extends Component
{
    public function render(CuentaRepository $cuenta, ClienteActivo $clienteActivo)
    {
        $cliente = $clienteActivo->actual();

        return view('livewire.vistas.cuenta.estado-cuenta-page', [
            'cliente' => $cliente,
            'saldos' => $cuenta->saldos(),
            'movimientos' => $cuenta->movimientos(),
            'objetivos' => $cliente ? $cuenta->objetivos() : null,
            'motivo' => $cliente ? null : ($clienteActivo->puedeElegir()
                ? 'Elegí un cliente en Productos para ver su cuenta.'
                : 'Ingresá con tu usuario para ver el estado de tu cuenta.'),
        ]);
    }
}
