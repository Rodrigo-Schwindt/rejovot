<?php

namespace App\Services\Pedidos;

use App\Models\Product;
use App\Services\Carrito\Carrito;
use App\Services\Odoo\OdooCatalog;
use App\Services\Odoo\OdooPedidos;
use App\Services\Sesion\ClienteActivo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Convierte el carrito en un pedido de Odoo a nombre del cliente activo.
 *
 * Sólo viaja lo que tiene stock. Lo que no tiene queda esperando en el
 * carrito: el día que ingrese, el cliente lo compra.
 */
class EnviarPedido
{
    public function __construct(
        private OdooPedidos $odoo,
        private OdooCatalog $catalogo,
        private ClienteActivo $clienteActivo,
    ) {
    }

    /**
     * @param  array|null  $envio  forma de entrega elegida (delivery.carrier)
     * @return array{id:int, name:string, enviados:array<int,string>, quedan:array<int,string>}
     *
     * @throws RuntimeException si falta el cliente o no hay nada con stock
     */
    public function desdeCarrito(Carrito $carrito, ?array $envio, string $observaciones = ''): array
    {
        $cliente = $this->clienteActivo->actual();

        if (! $cliente) {
            throw new RuntimeException($this->clienteActivo->motivo() ?? 'Elegí un cliente.');
        }

        $productos = $this->productosDelCarrito($carrito);

        if ($productos->isEmpty()) {
            throw new RuntimeException('El carrito está vacío.');
        }

        // El espejo local se actualiza cada media hora: para vender se mira Odoo.
        $this->refrescarStock($productos);

        $lineas = [];
        $enviados = [];
        $quedan = [];

        foreach ($carrito->items() as $item) {
            $producto = $productos->get($item['producto']['codigo']);

            if ((float) $producto->stock <= 0) {
                $quedan[] = $producto->code;

                continue;
            }

            $lineas[] = ['product_id' => $producto->odoo_id, 'cantidad' => $item['cantidad']];
            $enviados[] = $producto->code;
        }

        if (! $lineas) {
            throw new RuntimeException('Ninguno de los productos del carrito tiene stock: quedan guardados para cuando ingresen.');
        }

        $usuario = Auth::guard('sitio')->user();

        $pedido = $this->odoo->crear(
            $cliente->odoo_id,
            $lineas,
            $envio ? [
                'id' => $envio['id'],
                'producto_id' => $envio['producto_id'],
                'importe' => $carrito->totales($envio)['envio'],
            ] : null,
            // Sólo el vendedor se lleva la venta; el usuario de un cliente es de portal.
            $usuario?->esVendedor() ? $usuario->odoo_uid : null,
            $observaciones,
        );

        return $pedido + ['enviados' => $enviados, 'quedan' => $quedan];
    }

    /**
     * Los productos del carrito, indexados por código. Uno que ya no está
     * publicado no puede venderse.
     *
     * @return Collection<string, Product>
     */
    private function productosDelCarrito(Carrito $carrito): Collection
    {
        $codigos = array_map(fn (array $item) => $item['producto']['codigo'], $carrito->items());

        $productos = Product::publicables()->whereIn('code', $codigos)->get()->keyBy('code');

        foreach ($codigos as $codigo) {
            if (! $productos->has($codigo)) {
                throw new RuntimeException("El producto {$codigo} ya no está disponible.");
            }
        }

        return $productos;
    }

    /** Trae el stock real de Odoo y lo deja también en el espejo local. */
    private function refrescarStock(Collection $productos): void
    {
        $stock = $this->catalogo->stock($productos->pluck('odoo_id')->all());

        foreach ($productos as $producto) {
            if (! isset($stock[$producto->odoo_id])) {
                continue;
            }

            $producto->stock = (float) $stock[$producto->odoo_id]['qty_available'];
            $producto->saveQuietly();
        }
    }
}
