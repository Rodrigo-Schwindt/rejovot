<?php

namespace App\Services\Carrito;

use App\Contracts\CatalogoRepository;
use App\Services\Odoo\OdooEnvios;
use Illuminate\Support\Facades\Session;

/**
 * Carrito guardado en sesión. Los productos se resuelven contra
 * CatalogoRepository, así que cuando entre Odoo sólo cambia el repositorio;
 * más adelante este carrito pasa a ser el pedido (sale.order) de Odoo.
 */
class Carrito
{
    private const SESSION_KEY = 'carrito';

    private const SESSION_INIT = 'carrito_inicializado';

    /** Ítems de muestra para que la vista se vea cargada en la demo. */
    private const DEMO = ['BS009.0868' => 1, 'PO507.0868' => 1];

    public function __construct(private CatalogoRepository $catalogo)
    {
    }

    /** @return array<int, array{producto: array, cantidad: int, subtotal: float}> */
    public function items(): array
    {
        $items = [];

        foreach ($this->lineas() as $codigo => $cantidad) {
            $producto = $this->catalogo->detalle((string) $codigo);

            if (! $producto) {
                continue;
            }

            $items[] = [
                'producto' => $producto,
                'cantidad' => (int) $cantidad,
                'subtotal' => $producto['costo'] * (int) $cantidad,
            ];
        }

        return $items;
    }

    public function agregar(string $codigo, int $cantidad = 1): void
    {
        $lineas = $this->lineas();
        $lineas[$codigo] = ($lineas[$codigo] ?? 0) + max(1, $cantidad);

        $this->guardar($lineas);
    }

    public function actualizar(string $codigo, int $cantidad): void
    {
        $lineas = $this->lineas();

        if (! isset($lineas[$codigo])) {
            return;
        }

        $lineas[$codigo] = max(1, $cantidad);

        $this->guardar($lineas);
    }

    public function quitar(string $codigo): void
    {
        $lineas = $this->lineas();
        unset($lineas[$codigo]);

        $this->guardar($lineas);
    }

    public function vaciar(): void
    {
        $this->guardar([]);
    }

    public function cantidadTotal(): int
    {
        return (int) array_sum($this->lineas());
    }

    /** Importe total del carrito. */
    public function subtotal(): float
    {
        return collect($this->items())->sum('subtotal');
    }

    /** Importe de los ítems con stock: es el que define el envío bonificado. */
    public function subtotalConStock(): float
    {
        return collect($this->items())
            ->filter(fn (array $item) => $item['producto']['stock'] !== 'rojo')
            ->sum('subtotal');
    }

    /** El envío se bonifica según lo que diga la forma de entrega de Odoo. */
    public function envioBonificado(?array $envio): bool
    {
        return $envio
            && $envio['gratis_desde'] !== null
            && $this->subtotalConStock() >= $envio['gratis_desde'];
    }

    /** @param array|null $envio forma de entrega de delivery.carrier */
    public function totales(?array $envio): array
    {
        $subtotal = $this->subtotalConStock();
        $envio = app(OdooEnvios::class)->costo($envio, $subtotal);
        $iva = ($subtotal + $envio) * (float) config('carrito.iva') / 100;

        return [
            'subtotal' => $subtotal,
            'envio' => $envio,
            'iva' => $iva,
            'total' => $subtotal + $envio + $iva,
        ];
    }

    /** @return array<string, int> */
    private function lineas(): array
    {
        if (! Session::has(self::SESSION_INIT)) {
            Session::put(self::SESSION_INIT, true);
            Session::put(self::SESSION_KEY, self::DEMO);
        }

        return Session::get(self::SESSION_KEY, []);
    }

    private function guardar(array $lineas): void
    {
        Session::put(self::SESSION_INIT, true);
        Session::put(self::SESSION_KEY, $lineas);
    }
}
