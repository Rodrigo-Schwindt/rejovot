<?php

namespace App\Livewire\Vistas\Productos;

use App\Contracts\CatalogoRepository;
use App\Models\Contact;
use App\Services\Carrito\Carrito;
use App\Services\Margenes\Margenes;
use App\Services\Sesion\ClienteActivo;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class ProductoDetallePage extends Component
{
    public string $codigo = '';

    public int $cantidad = 1;

    /** Cantidad elegida en cada card de relacionados, por clave de código. */
    public array $cantidades = [];

    /** Se eligen una vez al entrar, así no cambian con cada interacción. */
    public array $relacionadosCodigos = [];

    /** Qué es cada relacionado (alternativo o accesorio), por código. */
    public array $relacionTipos = [];

    public function mount(string $codigo, CatalogoRepository $catalogo): void
    {
        $producto = $catalogo->detalle($codigo);
        abort_unless($producto, 404);

        $this->codigo = $codigo;
        $this->relacionadosCodigos = $this->elegirRelacionados($producto, $catalogo);

        // Cada card de relacionados arranca con cantidad 1.
        foreach ($this->relacionadosCodigos as $relacionado) {
            $this->cantidades[ProductosPage::clave($relacionado)] = 1;
        }
    }

    /**
     * Si Odoo le cargó alternativos o accesorios, se muestran ésos y nada más:
     * son los que de verdad tienen que ver con el producto. Recién si no tiene
     * ninguno se completa con productos del mismo rubro, al azar.
     */
    private function elegirRelacionados(array $producto, CatalogoRepository $catalogo): array
    {
        $ajenos = fn (array $p) => $p['codigo'] !== $producto['codigo'];

        $deOdoo = collect($catalogo->relacionados($producto['codigo']))->filter($ajenos);

        if ($deOdoo->isNotEmpty()) {
            $elegidos = $deOdoo->take(4);
            $this->relacionTipos = $elegidos->pluck('relacion', 'codigo')->all();

            return $elegidos->pluck('codigo')->all();
        }

        $mismoRubro = collect($catalogo->productos(['rubro' => $producto['rubro']], 500))
            ->filter($ajenos)
            ->shuffle();

        $resto = collect($catalogo->productos([], 500))
            ->filter($ajenos)
            ->shuffle();

        return $mismoRubro->concat($resto)
            ->unique('codigo')
            ->take(4)
            ->pluck('codigo')
            ->values()
            ->all();
    }

    /** Click en una card de relacionados: lleva a ese producto. */
    public function seleccionar(string $codigo)
    {
        return $this->redirectRoute('producto', ['codigo' => $codigo], navigate: true);
    }

    public function agregar(string $codigo, CatalogoRepository $catalogo, Carrito $carrito): void
    {
        $producto = $catalogo->detalle($codigo);

        if (! $producto) {
            return;
        }

        $cantidad = $codigo === $this->codigo
            ? max(1, $this->cantidad)
            : max(1, (int) ($this->cantidades[ProductosPage::clave($codigo)] ?? 1));
        $carrito->agregar($codigo, $cantidad);

        $this->dispatch('show-toast', message: "{$producto['codigo']} agregado al carrito.", type: 'success');
    }

    public function render(CatalogoRepository $catalogo, Margenes $margenes)
    {
        $producto = $margenes->aplicar($catalogo->detalle($this->codigo));

        $relacionados = collect($this->relacionadosCodigos)
            ->map(fn (string $codigo) => $catalogo->detalle($codigo))
            ->filter()
            // array_merge y no «+»: detalle() ya trae esas claves en null.
            ->map(fn (array $p) => array_merge($margenes->aplicar($p), [
                // Para que la card diga si es alternativo o accesorio, y de cuál.
                'relacion' => $this->relacionTipos[$p['codigo']] ?? null,
                'relacion_de' => isset($this->relacionTipos[$p['codigo']]) ? $this->codigo : null,
            ]))
            ->values()
            ->all();

        return view('livewire.vistas.productos.producto-detalle-page', [
            'producto' => $producto,
            'relacionados' => $relacionados,
            'wssp' => Contact::first()?->wssp,
            // Lo que necesita la card del catálogo.
            'seleccionado' => null,
            'mostrador' => false,
            'puedeOperar' => app(ClienteActivo::class)->puedeOperar(),
            'motivoBloqueo' => app(ClienteActivo::class)->motivo(),
        ]);
    }
}
