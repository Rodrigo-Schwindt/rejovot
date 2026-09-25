<?php

namespace App\Livewire\Vistas\Reclamos;

use App\Models\Claim;
use App\Services\Sesion\ClienteActivo;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Detalle de un reclamo, con la respuesta de Rejovot si la hay. */
#[Layout('layouts.public')]
class ReclamoDetallePage extends Component
{
    public Claim $reclamo;

    public function mount(Claim $reclamo, ClienteActivo $clienteActivo): void
    {
        // Nadie ve el reclamo de otro cliente.
        abort_unless($reclamo->customer_id === $clienteActivo->actual()?->id, 404);

        $this->reclamo = $reclamo->load(['items', 'fotos', 'user']);
    }

    public function render()
    {
        return view('livewire.vistas.reclamos.reclamo-detalle-page');
    }
}
