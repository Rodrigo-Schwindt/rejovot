<?php

namespace App\Contracts;

/**
 * Historial de pedidos del cliente. Igual que el catálogo, hoy lo resuelve una
 * implementación demo y mañana la de Odoo (sale.order).
 */
interface PedidosRepository
{
    /** Pedidos del cliente, del más nuevo al más viejo. */
    public function pedidos(): array;

    /** Un pedido por número, con sus líneas. */
    public function pedido(string $numero): ?array;
}
