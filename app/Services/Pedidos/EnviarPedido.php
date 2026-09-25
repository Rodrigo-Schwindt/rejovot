<?php

namespace App\Services\Pedidos;

use App\Mail\PedidoRecibidoMail;
use App\Models\Product;
use App\Services\Carrito\Carrito;
use App\Services\Odoo\OdooCatalog;
use App\Services\Odoo\OdooPedidos;
use App\Services\Sesion\ClienteActivo;
use App\Support\Destinatarios;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        $this->avisar($pedido['name'], $cliente, $carrito, $envio, $observaciones, $quedan);

        return $pedido + ['enviados' => $enviados, 'quedan' => $quedan];
    }

    /**
     * Avisa del pedido al cliente, a su vendedor y a la casilla de Rejovot.
     * Si el mail falla, el pedido ya está en Odoo: sólo se registra el error.
     */
    private function avisar(
        string $numero,
        \App\Models\Customer $cliente,
        Carrito $carrito,
        ?array $envio,
        string $observaciones,
        array $quedan,
    ): void {
        $destinos = Destinatarios::delCliente($cliente);

        if (! $destinos) {
            return;
        }

        $datos = [
            'numero' => $numero,
            'cliente' => $cliente->name,
            'entrega' => $envio['nombre'] ?? 'Entrega a convenir',
            'observaciones' => trim($observaciones),
            'sin_stock' => $quedan,
            'totales' => $carrito->totales($envio),
            'lineas' => array_map(fn (array $item) => [
                'codigo' => $item['producto']['codigo'],
                'nombre' => $item['producto']['nombre'],
                'cantidad' => $item['cantidad'],
                'precio' => (float) $item['producto']['costo'],
                'subtotal' => (float) $item['subtotal'],
            ], $carrito->itemsConStock()),
        ];

        try {
            Mail::to($destinos)->send(new PedidoRecibidoMail($datos));
        } catch (\Throwable $e) {
            Log::warning('No se pudo avisar del pedido ' . $numero . ': ' . $e->getMessage());
        }
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
