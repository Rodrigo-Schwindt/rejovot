<?php

namespace App\Services\Pedidos;

use App\Contracts\CatalogoRepository;
use App\Contracts\PedidosRepository;

/**
 * Pedidos hardcodeados. Las líneas referencian códigos del catálogo, así que
 * "Recomprar" arma el carrito con los productos reales.
 */
class PedidosDemo implements PedidosRepository
{
    public function __construct(private CatalogoRepository $catalogo)
    {
    }

    public function pedidos(int $limite = 25): array
    {
        return array_map(
            fn (array $pedido) => $this->conLineas($pedido),
            array_slice($this->dataset(), 0, $limite),
        );
    }

    public function pedido(string $numero): ?array
    {
        foreach ($this->dataset() as $pedido) {
            if ($pedido['numero'] === $numero) {
                return $this->conLineas($pedido);
            }
        }

        return null;
    }

    /** Resuelve cada línea contra el catálogo. */
    private function conLineas(array $pedido): array
    {
        $lineas = [];

        foreach ($pedido['items'] as $codigo => $cantidad) {
            $producto = $this->catalogo->detalle((string) $codigo);

            if (! $producto) {
                continue;
            }

            $lineas[] = [
                'producto' => $producto,
                'cantidad' => (int) $cantidad,
                'subtotal' => $producto['costo'] * (int) $cantidad,
            ];
        }

        $pedido['lineas'] = $lineas;

        return $pedido;
    }

    /** @return array<int, array<string, mixed>> */
    private function dataset(): array
    {
        return [
            [
                'numero' => '00001203',
                'fecha' => '22/08/2026',
                'importe' => 250924.83,
                'estado' => 'pendiente',
                'entrega' => 'Envío por logística',
                'items' => ['BS009.0868' => 2, 'PO507.0868' => 1, '60079.0347' => 3],
            ],
            [
                'numero' => '00001202',
                'fecha' => '15/08/2026',
                'importe' => 180382.22,
                'estado' => 'pendiente',
                'entrega' => 'Retiro por mostrador',
                'items' => ['BA564.0868' => 2, 'VDV0010.0562' => 1],
            ],
            [
                'numero' => '00001201',
                'fecha' => '22/08/2026',
                'importe' => 250924.83,
                'estado' => 'pendiente',
                'entrega' => 'Envío por logística',
                'items' => ['3363.0562' => 4, '9518.1050' => 6],
            ],
            [
                'numero' => '00001200',
                'fecha' => '15/08/2026',
                'importe' => 180382.22,
                'estado' => 'entregado',
                'entrega' => 'Retiro por mostrador',
                'items' => ['1017C.0090' => 10, '1017LC.0090' => 10],
            ],
        ];
    }
}
