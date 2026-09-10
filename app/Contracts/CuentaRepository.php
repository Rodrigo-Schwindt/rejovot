<?php

namespace App\Contracts;

/**
 * Cuenta corriente del cliente. Hoy la resuelve una implementación demo y
 * mañana la de Odoo (account.move / account.move.line).
 */
interface CuentaRepository
{
    /** Saldos del encabezado: vencido y total. */
    public function saldos(): array;

    /** Movimientos de la cuenta, del más nuevo al más viejo. */
    public function movimientos(): array;
}
