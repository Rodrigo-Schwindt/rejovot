<?php

namespace App\Livewire\Vistas\Pedidos;

use App\Contracts\PedidosRepository;
use App\Services\Sesion\ClienteActivo;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Historial de pedidos del cliente activo, leído de Odoo en vivo.
 * Sin cliente elegido no hay nada que mostrar.
 */
#[Layout('layouts.public')]
class MisPedidosPage extends Component
{
    /** Número del pedido con el detalle desplegado; viaja en la URL. */
    #[Url(except: '')]
    public string $abierto = '';

    /** De a cuántos se traen: los pedidos se leen en vivo, no conviene traer todo. */
    public int $cantidad = 25;

    public function verDetalle(string $numero): void
    {
        $this->abierto = $this->abierto === $numero ? '' : $numero;
    }

    public function verMas(): void
    {
        $this->cantidad += 25;
    }

    public function render(PedidosRepository $pedidos, ClienteActivo $clienteActivo)
    {
        $cliente = $clienteActivo->actual();
        $listado = $cliente ? $pedidos->pedidos($this->cantidad) : [];

        return view('livewire.vistas.pedidos.mis-pedidos-page', [
            'pedidos' => $listado,
            'cliente' => $cliente,
            // El motivo es propio de esta pantalla: acá no se compra, se consulta.
            'motivo' => $cliente ? null : ($clienteActivo->puedeElegir()
                ? 'Elegí un cliente en Productos para ver sus pedidos.'
                : 'Ingresá con tu usuario para ver tus pedidos.'),
            // Si vino una página completa, seguramente haya más para traer.
            'hayMas' => count($listado) >= $this->cantidad,
        ]);
    }
}
