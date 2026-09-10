<?php

namespace App\Livewire\Vistas\Cuenta;

use App\Contracts\CuentaRepository;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class EstadoCuentaPage extends Component
{
    /** El comprobante lo va a servir Odoo; por ahora sólo avisamos. */
    public function descargar(string $numero): void
    {
        $this->dispatch(
            'show-toast',
            message: "El comprobante {$numero} se va a descargar desde Odoo.",
            type: 'info',
        );
    }

    public function render(CuentaRepository $cuenta)
    {
        return view('livewire.vistas.cuenta.estado-cuenta-page', [
            'saldos' => $cuenta->saldos(),
            'movimientos' => $cuenta->movimientos(),
        ]);
    }
}
