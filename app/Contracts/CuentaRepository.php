<?php

namespace App\Contracts;

/**
 * Cuenta corriente del cliente. La resuelve `CuentaOdoo` leyendo los apuntes
 * por cobrar; `CuentaDemo` queda para trabajar sin conexión al ERP.
 */
interface CuentaRepository
{
    /** Saldos del encabezado: vencido y total. */
    public function saldos(): array;

    /** Movimientos de la cuenta, del más nuevo al más viejo. */
    public function movimientos(): array;

    /** Objetivo de compra del mes, o null si el cliente no tiene escala. */
    public function objetivos(): ?array;
}
