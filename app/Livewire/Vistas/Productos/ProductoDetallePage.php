<?php

namespace App\Livewire\Vistas\Productos;

use App\Contracts\CatalogoRepository;
use App\Models\Contact;
use App\Services\Carrito\Carrito;
use App\Services\Margenes\Margenes;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class ProductoDetallePage extends Component
{
    public string $codigo = '';

    public int $cantidad = 1;

    public function mount(string $codigo, CatalogoRepository $catalogo): void
    {
        abort_unless($catalogo->detalle($codigo), 404);

        $this->codigo = $codigo;
    }

    public function agregar(string $codigo, CatalogoRepository $catalogo, Carrito $carrito): void
    {
        $producto = $catalogo->detalle($codigo);

        if (! $producto) {
            return;
        }

        $cantidad = $codigo === $this->codigo ? max(1, $this->cantidad) : 1;
        $carrito->agregar($codigo, $cantidad);

        $this->dispatch('show-toast', message: "{$producto['codigo']} agregado al carrito.", type: 'success');
    }

    public function render(CatalogoRepository $catalogo, Margenes $margenes)
    {
        $producto = $margenes->aplicar($catalogo->detalle($this->codigo));

        // Relacionados: primero los del mismo rubro y, si faltan, se completa con el resto.
        $relacionados = collect($catalogo->productos(['rubro' => $producto['rubro']]))
            ->concat($catalogo->productos())
            ->reject(fn (array $p) => $p['codigo'] === $producto['codigo'])
            ->unique('codigo')
            ->take(3)
            ->map(fn (array $p) => $margenes->aplicar($p))
            ->values()
            ->all();

        return view('livewire.vistas.productos.producto-detalle-page', [
            'producto' => $producto,
            'relacionados' => $relacionados,
            'wssp' => Contact::first()?->wssp,
        ]);
    }
}
