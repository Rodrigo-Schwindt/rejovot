<?php

namespace App\Livewire\Vistas\Pedidos;

use App\Contracts\PedidosRepository;
use App\Services\Carrito\Carrito;
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

    /** Carga las líneas del pedido en el carrito y lleva al carrito. */
    public function recomprar(string $numero, PedidosRepository $pedidos, Carrito $carrito)
    {
        $pedido = $pedidos->pedido($numero);

        if (! $pedido) {
            $this->dispatch('show-toast', message: 'No encontramos ese pedido.', type: 'error');

            return null;
        }

        $cargados = 0;
        $salteados = 0;

        foreach ($pedido['lineas'] as $linea) {
            // El envío no es un producto: no se recompra ni se avisa.
            if (! empty($linea['envio'])) {
                continue;
            }

            $codigo = $linea['producto']['codigo'];

            // Un producto que ya no está en el catálogo no se puede volver a pedir.
            if ($codigo === '') {
                $salteados++;

                continue;
            }

            $carrito->agregar($codigo, (int) max(1, $linea['cantidad']));
            $cargados++;
        }

        if ($cargados === 0) {
            $this->dispatch('show-toast', message: 'Ninguno de esos productos sigue disponible.', type: 'error');

            return null;
        }

        $aviso = match (true) {
            $salteados === 0 => '',
            $salteados === 1 => ' Un producto ya no está disponible y quedó afuera.',
            default => " {$salteados} productos ya no están disponibles y quedaron afuera.",
        };

        $texto = $cargados === 1 ? 'Se cargó 1 producto' : "Se cargaron {$cargados} productos";

        session()->flash('toast', "{$texto} del pedido {$numero} en el carrito.{$aviso}");

        return $this->redirect(route('carrito'), navigate: true);
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
