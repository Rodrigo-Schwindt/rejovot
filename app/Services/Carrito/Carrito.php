<?php

namespace App\Services\Carrito;

use App\Contracts\CatalogoRepository;
use App\Services\Margenes\Margenes;
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

    public function __construct(
        private CatalogoRepository $catalogo,
        private Margenes $margenes,
    ) {
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

            // El precio del carrito es el mismo del catálogo: lista menos el
            // descuento que el cliente configuró en Márgenes.
            $producto = $this->margenes->aplicar($producto);

            $items[] = [
                'producto' => $producto,
                'cantidad' => (int) $cantidad,
                'subtotal' => round($producto['costo'] * (int) $cantidad, 2),
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

    /** Lo mismo pero a precio de lista, para poder mostrar cuánto se descontó. */
    public function subtotalListaConStock(): float
    {
        return round(collect($this->itemsConStock())
            ->sum(fn (array $item) => (float) $item['producto']['lista'] * $item['cantidad']), 2);
    }

    /** El envío se bonifica según lo que diga la forma de entrega de Odoo. */
    public function envioBonificado(?array $envio): bool
    {
        return $envio
            && $envio['gratis_desde'] !== null
            && $this->subtotalConStock() >= $envio['gratis_desde'];
    }

    /** @param array|null $envio forma de entrega de delivery.carrier */
    /**
     * IVA de lo que entra en el pedido, calculado línea por línea: cada
     * producto tiene el suyo en Odoo («Impuestos cliente»).
     *
     * @return array{monto:float, tasas:array<int, float>}
     */
    public function ivaConStock(): array
    {
        $monto = 0.0;
        $tasas = [];

        foreach ($this->itemsConStock() as $item) {
            $tasa = (float) ($item['producto']['iva'] ?? config('carrito.iva'));
            $monto += $item['subtotal'] * $tasa / 100;
            $tasas[(string) $tasa] = $tasa;
        }

        return ['monto' => round($monto, 2), 'tasas' => array_values($tasas)];
    }

    public function totales(?array $envio): array
    {
        $lista = $this->subtotalListaConStock();
        $subtotal = $this->subtotalConStock();
        $envio = app(OdooEnvios::class)->costo($envio, $subtotal);

        $ivaProductos = $this->ivaConStock();
        // El envío va al 21%: es el impuesto del producto de entrega en Odoo.
        $iva = round($ivaProductos['monto'] + $envio * (float) config('carrito.iva') / 100, 2);

        return [
            // A precio de lista, antes del descuento del cliente.
            'lista' => $lista,
            'descuento' => round($lista - $subtotal, 2),
            'descuento_porcentaje' => $this->margenes->descuento(),
            'subtotal' => $subtotal,
            'envio' => $envio,
            'iva' => $iva,
            // Si todo el carrito comparte tasa, se muestra el porcentaje.
            'iva_tasa' => count($ivaProductos['tasas']) === 1 && $envio <= 0
                ? $ivaProductos['tasas'][0]
                : null,
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
