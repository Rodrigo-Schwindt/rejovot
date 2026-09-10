<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cuentas bancarias que se muestran en Info de pagos.
 * Se cargan desde el admin: no vienen de Odoo.
 */
class BankAccount extends Model
{
    protected $table = 'bank_accounts';

    protected $fillable = [
        'titular',
        'banco',
        'tipo_cuenta',
        'numero',
        'cbu',
        'alias',
        'cuit',
        'sort_order',
    ];

    /** Etiqueta => campo, en el orden en que se muestran. */
    public const CAMPOS = [
        'titular' => 'Titular',
        'banco' => 'Banco',
        'tipo_cuenta' => 'Tipo de cuenta',
        'numero' => 'Número de cuenta',
        'cbu' => 'CBU',
        'alias' => 'Alias',
        'cuit' => 'CUIT',
    ];
}
