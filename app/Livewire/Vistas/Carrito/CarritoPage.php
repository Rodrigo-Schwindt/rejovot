<?php

namespace App\Livewire\Vistas\Carrito;

use App\Livewire\Vistas\Productos\ProductosPage;
use App\Services\Carrito\Carrito;
use App\Services\Odoo\OdooEnvios;
use App\Services\Odoo\OdooException;
use App\Services\Pedidos\EnviarPedido;
use App\Services\Sesion\ClienteActivo;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class CarritoPage extends Component
{
    /** Cantidades editables, indexadas por clave (código sin puntos). */
    public array $cantidades = [];

    /** Id de la forma de entrega (delivery.carrier de Odoo). */
    public ?int $entrega = null;

    public string $mensaje = '';

    public function mount(Carrito $carrito, OdooEnvios $envios): void
    {
        $this->sincronizarCantidades($carrito);
        $this->entrega ??= $envios->porDefecto()['id'] ?? null;
    }

    /** Livewire avisa qué clave del array cambió. */
    public function updatedCantidades($value, $key): void
    {
        $carrito = app(Carrito::class);

        foreach ($carrito->items() as $item) {
            if (ProductosPage::clave($item['producto']['codigo']) === $key) {
                $carrito->actualizar($item['producto']['codigo'], max(1, (int) $value));
                break;
            }
        }

        $this->sincronizarCantidades($carrito);
    }

    public function quitar(string $codigo, Carrito $carrito): void
    {
        $carrito->quitar($codigo);
        $this->sincronizarCantidades($carrito);

        $this->dispatch('show-toast', message: "Se quitó {$codigo} del carrito.", type: 'success');
    }

    public function cancelar(Carrito $carrito): void
    {
        $carrito->vaciar();
        $this->sincronizarCantidades($carrito);

        $this->dispatch('show-toast', message: 'Se vació el carrito.', type: 'success');
    }

    /** Manda el carrito a Odoo como presupuesto y lleva a Mis Pedidos. */
    public function realizarPedido(Carrito $carrito, ClienteActivo $clienteActivo, OdooEnvios $envios, EnviarPedido $enviar)
    {
        if (! $clienteActivo->puedeOperar()) {
            $this->dispatch('show-toast', message: $clienteActivo->motivo(), type: 'error');

            return null;
        }

        if (! $carrito->items()) {
            $this->dispatch('show-toast', message: 'El carrito está vacío.', type: 'error');

            return null;
        }

        $envio = $this->entrega ? $envios->porId($this->entrega) : $envios->porDefecto();

        try {
            $pedido = $enviar->desdeCarrito($carrito, $envio, $this->mensaje);
        } catch (\RuntimeException $e) {
            $this->dispatch('show-toast', message: $e->getMessage(), type: 'error');

            return null;
        } catch (OdooException $e) {
            // El carrito queda como está: el cliente puede volver a intentar.
            Log::error('No se pudo crear el pedido en Odoo: ' . $e->getMessage());
            $this->dispatch('show-toast', message: 'No pudimos enviar el pedido. Probá de nuevo en un momento.', type: 'error');

            return null;
        }

        // Lo sin stock sigue en el carrito para cuando ingrese.
        $carrito->quitarVarios($pedido['enviados']);
        $this->mensaje = '';

        $quedan = count($pedido['quedan']);
        $aviso = match (true) {
            $quedan === 0 => '',
            $quedan === 1 => ' Un producto sin stock quedó en el carrito.',
            default => " {$quedan} productos sin stock quedaron en el carrito.",
        };

        session()->flash('toast', "Recibimos tu pedido {$pedido['name']}. Te lo confirmamos a la brevedad.{$aviso}");

        return $this->redirect(route('pedidos'), navigate: true);
    }

    public function render(Carrito $carrito, OdooEnvios $envios)
    {
        $seleccionado = $this->entrega ? $envios->porId($this->entrega) : $envios->porDefecto();

        return view('livewire.vistas.carrito.carrito-page', [
            'clienteActivo' => app(ClienteActivo::class)->actual(),
            'motivoBloqueo' => app(ClienteActivo::class)->motivo(),
            'items' => $carrito->items(),
            'sinStock' => count($carrito->itemsSinStock()),
            // Marca en la lista qué formas de entrega ya quedan sin cargo con este carrito.
            'envios' => array_map(fn (array $e) => $e + ['bonificado' => $carrito->envioBonificado($e)], $envios->disponibles()),
            'envioElegido' => $seleccionado,
            'totales' => $carrito->totales($seleccionado),
            'envioBonificado' => $carrito->envioBonificado($seleccionado),
        ]);
    }

    private function sincronizarCantidades(Carrito $carrito): void
    {
        $this->cantidades = collect($carrito->items())
            ->mapWithKeys(fn (array $item) => [
                ProductosPage::clave($item['producto']['codigo']) => $item['cantidad'],
            ])
            ->all();
    }
}
