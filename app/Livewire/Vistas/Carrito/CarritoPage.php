<?php

namespace App\Livewire\Vistas\Carrito;

use App\Livewire\Vistas\Productos\ProductosPage;
use App\Services\Carrito\Carrito;
use App\Services\Odoo\OdooEnvios;
use App\Services\Sesion\ClienteActivo;
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

    public function realizarPedido(Carrito $carrito, ClienteActivo $clienteActivo): void
    {
        if (! $clienteActivo->puedeOperar()) {
            $this->dispatch('show-toast', message: $clienteActivo->motivo(), type: 'error');

            return;
        }

        if (! $carrito->items()) {
            $this->dispatch('show-toast', message: 'El carrito está vacío.', type: 'error');

            return;
        }

        // El pedido se va a crear en Odoo (sale.order); por ahora sólo confirmamos.
        $cliente = $clienteActivo->actual();
        $this->dispatch('show-toast', message: "Pedido registrado a nombre de {$cliente->name}.", type: 'success');
    }

    public function render(Carrito $carrito, OdooEnvios $envios)
    {
        $seleccionado = $this->entrega ? $envios->porId($this->entrega) : $envios->porDefecto();

        return view('livewire.vistas.carrito.carrito-page', [
            'clienteActivo' => app(ClienteActivo::class)->actual(),
            'motivoBloqueo' => app(ClienteActivo::class)->motivo(),
            'items' => $carrito->items(),
            'envios' => $envios->disponibles(),
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
