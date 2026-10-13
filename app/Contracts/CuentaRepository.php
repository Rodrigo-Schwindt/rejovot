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

    /**
     * Comprobantes de la cuenta (pagados y pendientes), del más nuevo al más
     * viejo. $cuenta = 'info': los de la otra empresa (PRUEBA en Odoo).
     */
    public function movimientos(string $cuenta = '', int $limite = 50): array;

    /** Si el cliente tiene comprobantes pendientes en la cuenta «INFO». */
    public function tieneInfo(): bool;

    /** Objetivo de compra del mes, o null si el cliente no tiene escala. */
    public function objetivos(): ?array;
}
