<?php

namespace App\Contracts;

/**
 * Historial de pedidos del cliente. Lo resuelve `PedidosOdoo` leyendo sale.order;
 * `PedidosDemo` queda para trabajar sin conexión al ERP.
 */
interface PedidosRepository
{
    /** Pedidos del cliente, del más nuevo al más viejo. */
    public function pedidos(int $limite = 25): array;

    /** Un pedido por número, con sus líneas. */
    public function pedido(string $numero): ?array;
}
