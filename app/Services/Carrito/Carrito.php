<?php

namespace App\Services\Carrito;

use App\Contracts\CatalogoRepository;
use App\Services\Odoo\OdooEnvios;
use Illuminate\Support\Facades\Session;

/**
 * Carrito guardado en sesión. Los productos se resuelven contra
 * CatalogoRepository; al confirmar, `EnviarPedido` lo convierte en un
 * pedido de Odoo (sale.order).
 */
class Carrito
{
    private const SESSION_KEY = 'carrito';

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

    /** Lo que entra en el pedido: sólo lo que tiene stock. */
    public function itemsConStock(): array
    {
        return array_values(array_filter($this->items(), fn (array $item) => $item['producto']['stock'] !== 'rojo'));
    }

    /** Lo que espera en el carrito hasta que ingrese stock. */
    public function itemsSinStock(): array
    {
        return array_values(array_filter($this->items(), fn (array $item) => $item['producto']['stock'] === 'rojo'));
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

    /** @param  array<int, string>  $codigos */
    public function quitarVarios(array $codigos): void
    {
        $lineas = $this->lineas();

        foreach ($codigos as $codigo) {
            unset($lineas[$codigo]);
        }

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

    /** Importe de lo que entra en el pedido: lo sin stock no se cobra ni se cuenta. */
    public function subtotalConStock(): float
    {
        return collect($this->itemsConStock())->sum('subtotal');
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
        return Session::get(self::SESSION_KEY, []);
    }

    private function guardar(array $lineas): void
    {
        Session::put(self::SESSION_KEY, $lineas);
    }
}
