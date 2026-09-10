<?php

namespace App\Livewire\Vistas\Productos;

use App\Contracts\CatalogoRepository;
use App\Models\Customer;
use App\Services\Carrito\Carrito;
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

    /** Ítems en el carrito de sesión. */
    public int $itemsCarrito = 0;

    /** Múltiplo de 3: así la cuadrícula no deja filas incompletas. */
    public int $porPagina = 21;

    public function mount(Carrito $carrito): void
    {
        $this->itemsCarrito = $carrito->cantidadTotal();
    }

    /** Los códigos traen puntos y wire:model los interpreta como anidado. */
    public static function clave(string $codigo): string
    {
        return str_replace(['.', '-', ' ', '/'], '_', $codigo);
    }

    public function buscar(): void
    {
        $this->resetPage();
    }

    public function limpiar(): void
    {
        $this->reset(['q', 'soloOfertas', 'marca', 'rubro', 'oem', 'codigo', 'tipo']);
        $this->resetPage();
    }

    /** Cualquier filtro que cambie vuelve a la primera página. */
    public function updated($property): void
    {
        if (in_array($property, ['q', 'marca', 'rubro', 'oem', 'codigo', 'tipo', 'soloOfertas', 'porPagina'], true)) {
            $this->resetPage();
        }
    }

    /** El vendedor elige un cliente de su cartera. */
    public function elegirCliente(int $id, ClienteActivo $clienteActivo, Carrito $carrito): void
    {
        if (! $clienteActivo->elegir($id)) {
            $this->dispatch('show-toast', message: 'Ese cliente no está en tu cartera.', type: 'error');

            return;
        }

        // El carrito es de un cliente: al cambiar, se arranca de cero.
        $carrito->vaciar();
        $this->itemsCarrito = 0;
        $this->buscarCliente = '';
        $this->resetPage();

        $this->dispatch('show-toast', message: 'Comprando para ' . $clienteActivo->actual()->name, type: 'success');
    }

    public function quitarCliente(ClienteActivo $clienteActivo, Carrito $carrito): void
    {
        $clienteActivo->limpiar();
        $carrito->vaciar();
        $this->itemsCarrito = 0;
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

        $this->itemsCarrito = $carrito->cantidadTotal();
        $this->seleccionado = $codigo;

        $this->dispatch('show-toast', message: "{$producto['codigo']} agregado al carrito.", type: 'success');
    }

    public function render(CatalogoRepository $catalogo, Margenes $margenes)
    {
        $paginador = $catalogo->paginados([
            'q' => $this->q,
            'marca' => $this->marca,
            'rubro' => $this->rubro,
            'oem' => $this->oem,
            'codigo' => $this->codigo,
            'tipo' => $this->tipo,
            'solo_ofertas' => $this->soloOfertas,
        ], $this->porPagina);

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

        // Con cliente elegido, "Tu precio" es la lista menos su descuento.
        if ($clienteElegido) {
            $productos = array_map(fn (array $p) => $this->conDescuento($p, $clienteElegido, $margenes), $productos);
            $detalle = $detalle ? $this->conDescuento($detalle, $clienteElegido, $margenes) : null;
        }

        return view('livewire.vistas.productos.productos-page', [
            'productos' => $productos,
            'paginador' => $paginador,
            'detalle' => $detalle,
            'ofertas' => $catalogo->ofertas(),
            'clienteElegido' => $clienteElegido,
            'clientes' => $this->buscarClientes(),
            'puedeElegirCliente' => app(ClienteActivo::class)->puedeElegir(),
            'totalCartera' => auth('sitio')->user()?->esVendedor() ? $this->cartera()->count() : 0,
            'puedeOperar' => app(ClienteActivo::class)->puedeOperar(),
            'motivoBloqueo' => app(ClienteActivo::class)->motivo(),
            'opciones' => $catalogo->filtros(),
        ]);
    }

    /**
     * Cartera del vendedor. Sin texto muestra los primeros: el vendedor tiene
     * que ver a sus clientes sin necesidad de adivinar cómo están escritos.
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
            ->limit(15)
            ->get();
    }

    /** Consulta base de la cartera del vendedor logueado. */
    private function cartera()
    {
        return Customer::with('salesperson')
            ->where('active', true)
            ->where('salesperson_id', auth('sitio')->user()?->salesperson_id);
    }

    /** Aplica el descuento del cliente y recalcula su precio de venta. */
    private function conDescuento(array $producto, Customer $cliente, Margenes $margenes): array
    {
        $producto['costo'] = $cliente->precioNeto((float) $producto['lista']);

        return $margenes->aplicar($producto);
    }
}
