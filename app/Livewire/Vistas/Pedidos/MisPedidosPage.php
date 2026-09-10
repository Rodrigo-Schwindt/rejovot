<?php

namespace App\Livewire\Vistas\Pedidos;

use App\Contracts\PedidosRepository;
use App\Services\Carrito\Carrito;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class MisPedidosPage extends Component
{
    /** Número del pedido con el detalle desplegado. */
    public string $abierto = '';

    public function verDetalle(string $numero): void
    {
        $this->abierto = $this->abierto === $numero ? '' : $numero;
    }

    /** Carga las líneas del pedido en el carrito y lleva al carrito. */
    public function recomprar(string $numero, PedidosRepository $pedidos, Carrito $carrito)
    {
        $pedido = $pedidos->pedido($numero);

        if (! $pedido) {
            $this->dispatch('show-toast', message: 'No encontramos ese pedido.', type: 'error');

            return null;
        }

        foreach ($pedido['lineas'] as $linea) {
            $carrito->agregar($linea['producto']['codigo'], $linea['cantidad']);
        }

        session()->flash('toast', "Se cargaron los productos del pedido {$numero} en el carrito.");

        return $this->redirect(route('carrito'), navigate: true);
    }

    public function render(PedidosRepository $pedidos)
    {
        return view('livewire.vistas.pedidos.mis-pedidos-page', [
            'pedidos' => $pedidos->pedidos(),
        ]);
    }
}
