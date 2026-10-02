<?php

namespace App\Livewire\Vistas\Productos;

use App\Contracts\CatalogoRepository;
use App\Models\Customer;
use App\Services\Carrito\Carrito;
use App\Services\Catalogo\RefrescoEnVivo;
use App\Services\Margenes\Margenes;
use App\Services\Sesion\ClienteActivo;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Catálogo. Los datos salen de CatalogoRepository, que hoy resuelve CatalogoLocal
 * (copia sincronizada de Odoo). El precio de venta lo calcula el margen que el
 * propio cliente administra desde la pantalla de Márgenes.
 */
#[Layout('layouts.public')]
class ProductosPage extends Component
{
    use WithPagination;

    /** Búsqueda del selector de clientes (sólo para vendedores). */
    public string $buscarCliente = '';

    /** Buscador libre: código, descripción y OEM. */
    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $marca = '';

    #[Url(except: '')]
    public string $rubro = '';

    #[Url(except: '')]
    public string $oem = '';

    /** Código del producto (referencia interna), no el OEM. */
    #[Url(except: '')]
    public string $codigo = '';

    /** Tipo de producto de Odoo: almacenable, consumible o servicio. */
    #[Url(except: '')]
    public string $tipo = '';

    public bool $soloOfertas = false;

    /** Preferencias de visualización. */
    #[Url(except: 'lista')]
    public string $vista = 'lista';

    /** Vista mostrador: oculta el precio de compra para atender al público. */
    public bool $mostrador = false;

    /** Producto abierto en el panel lateral. */
    public string $seleccionado = '';

    /** Cantidades por producto, indexadas por clave (código sin puntos). */
    public array $cantidades = [];

    /** Unidades en el carrito del cliente. */
    public int $itemsCarrito = 0;

    /** Para notar cambios hechos desde otra sesión del mismo cliente. */
    public string $firmaCarrito = '';

    /** Múltiplo de 3: así la cuadrícula no deja filas incompletas. */
    public int $porPagina = 21;

    public function mount(Carrito $carrito): void
    {
        $this->sincronizarContador($carrito);
    }

    /**
     * Cada tanto (wire:poll) se fija si alguien más que opera este cliente
     * cambió el carrito. Si no cambió nada, no se vuelve a dibujar la página.
     */
    public function sincronizarCarrito(Carrito $carrito): void
    {
        if ($carrito->firma() === $this->firmaCarrito) {
            $this->skipRender();

            return;
        }

        $this->sincronizarContador($carrito);
    }

    private function sincronizarContador(Carrito $carrito): void
    {
        $this->itemsCarrito = $carrito->cantidadTotal();
        $this->firmaCarrito = $carrito->firma();
    }

    /** Los códigos traen puntos y wire:model los interpreta como anidado. */
    public static function clave(string $codigo): string
    {
        return str_replace(['.', '-', ' ', '/'], '_', $codigo);
    }

    public function buscar(): void
    {
        $this->nuevaBusqueda();
    }

    public function limpiar(): void
    {
        $this->reset(['q', 'soloOfertas', 'marca', 'rubro', 'oem', 'codigo', 'tipo']);
        $this->nuevaBusqueda();
    }

    /** Cualquier filtro que cambie vuelve a la primera página. */
    public function updated($property): void
    {
        if (in_array($property, ['q', 'marca', 'rubro', 'oem', 'codigo', 'tipo', 'soloOfertas', 'porPagina'], true)) {
            $this->nuevaBusqueda();
        }
    }

    /**
     * Primera página y sin producto elegido: así el panel lateral muestra el
     * primer resultado de lo filtrado y no el que se había tocado antes.
     */
    private function nuevaBusqueda(): void
    {
        $this->seleccionado = '';
        $this->resetPage();
    }

    /** El vendedor elige un cliente de su cartera. */
    public function elegirCliente(int $id, ClienteActivo $clienteActivo, Carrito $carrito): void
    {
        if (! $clienteActivo->elegir($id)) {
            $this->dispatch('show-toast', message: 'Ese cliente no está en tu cartera.', type: 'error');

            return;
        }

        // El carrito es de cada cliente: al cambiar se ve el de ese cliente,
        // con lo que haya cargado él u otro vendedor. No se vacía.
        $this->sincronizarContador($carrito);
        $this->buscarCliente = '';
        $this->resetPage();

        $this->dispatch('show-toast', message: 'Comprando para ' . $clienteActivo->actual()->name, type: 'success');
    }

    public function quitarCliente(ClienteActivo $clienteActivo, Carrito $carrito): void
    {
        $clienteActivo->limpiar();
        $this->sincronizarContador($carrito);
        $this->buscarCliente = '';
        $this->resetPage();
    }

    public function seleccionar(string $codigo): void
    {
        $this->seleccionado = $codigo;
    }

    public function cambiarVista(string $vista): void
    {
        $this->vista = $vista === 'cuadricula' ? 'cuadricula' : 'lista';
    }

    public function agregar(string $codigo, CatalogoRepository $catalogo, Carrito $carrito, ClienteActivo $clienteActivo): void
    {
        if (! $clienteActivo->puedeOperar()) {
            $this->dispatch('show-toast', message: $clienteActivo->motivo(), type: 'error');

            return;
        }

        $producto = $catalogo->detalle($codigo);

        if (! $producto) {
            return;
        }

        $carrito->agregar($codigo, max(1, (int) ($this->cantidades[self::clave($codigo)] ?? 1)));

        $this->sincronizarContador($carrito);
        $this->seleccionado = $codigo;

        $this->dispatch('show-toast', message: "{$producto['codigo']} agregado al carrito.", type: 'success');
    }

    public function render(CatalogoRepository $catalogo, Margenes $margenes, RefrescoEnVivo $refresco)
    {
        $filtros = [
            'q' => $this->q,
            'marca' => $this->marca,
            'rubro' => $this->rubro,
            'oem' => $this->oem,
            'codigo' => $this->codigo,
            'tipo' => $this->tipo,
            'solo_ofertas' => $this->soloOfertas,
        ];

        $paginador = $catalogo->paginados($filtros, $this->porPagina);
        $ofertas = $catalogo->ofertas();

        // Lo que se ve en pantalla, recién leído de Odoo (precio, stock y si
        // sigue publicado). Si algo cambió, se vuelve a armar la página: un
        // producto despublicado hace un minuto ya no aparece.
        $enPantalla = [
            ...array_column($paginador->items(), 'codigo'),
            ...array_column($ofertas, 'codigo'),
            $this->seleccionado,
        ];

        if ($refresco->codigos($enPantalla)) {
            $paginador = $catalogo->paginados($filtros, $this->porPagina);
            $ofertas = $catalogo->ofertas();
        }

        // El precio de venta sale del margen que configura el cliente.
        $productos = collect($paginador->items())
            ->map(fn (array $p) => $margenes->aplicar($p))
            ->all();

        foreach ($productos as $producto) {
            $this->cantidades[self::clave($producto['codigo'])] ??= 1;
        }

        $this->seleccionado = $this->seleccionado ?: ($productos[0]['codigo'] ?? '');

        $detalle = $this->seleccionado !== '' ? $catalogo->detalle($this->seleccionado) : null;
        $detalle = $detalle ? $margenes->aplicar($detalle) : ($productos[0] ?? null);

        $clienteElegido = app(ClienteActivo::class)->actual();

        return view('livewire.vistas.productos.productos-page', [
            'productos' => $productos,
            'paginador' => $paginador,
            'detalle' => $detalle,
            'ofertas' => $ofertas,
            'clienteElegido' => $clienteElegido,
            'descuentoLista' => $margenes->descuento(),
            'clientes' => $this->buscarClientes(),
            'puedeElegirCliente' => app(ClienteActivo::class)->puedeElegir(),
            'totalCartera' => auth('sitio')->user()?->esVendedor() ? $this->cartera()->count() : 0,
            'puedeOperar' => app(ClienteActivo::class)->puedeOperar(),
            'motivoBloqueo' => app(ClienteActivo::class)->motivo(),
            'opciones' => $catalogo->filtros(),
        ]);
    }

    /**
     * Cartera del vendedor. Sin texto muestra la cartera completa: el vendedor
     * tiene que ver a sus clientes sin necesidad de adivinar cómo están escritos.
     */
    private function buscarClientes()
    {
        $usuario = auth('sitio')->user();

        if (! $usuario?->esVendedor()) {
            return collect();
        }

        return $this->cartera()
            ->buscar($this->buscarCliente)
            ->orderBy('name')
            ->get();
    }

    /** Consulta base de la cartera del vendedor logueado. */
    private function cartera()
    {
        return Customer::with('salesperson')
            ->where('active', true)
            ->where('salesperson_id', auth('sitio')->user()?->salesperson_id);
    }
}
