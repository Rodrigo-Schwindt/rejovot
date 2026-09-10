<?php

namespace App\Livewire\Vistas\Precios;

use App\Models\PriceList;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class ListaPreciosPage extends Component
{
    /** Id de la lista con el detalle desplegado. */
    public ?int $abierta = null;

    public function verDetalle(int $id): void
    {
        $this->abierta = $this->abierta === $id ? null : $id;
    }

    public function render()
    {
        return view('livewire.vistas.precios.lista-precios-page', [
            'listas' => PriceList::publicadas()->orderBy('sort_order')->orderByDesc('id')->get(),
        ]);
    }
}
