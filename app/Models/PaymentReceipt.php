<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Comprobante de pago que envía el cliente desde Info de pagos.
 */
class PaymentReceipt extends Model
{
    protected $table = 'payment_receipts';

    protected $fillable = [
        'fecha',
        'importe',
        'banco',
        'sucursal',
        'facturas_canceladas',
        'observaciones',
        'archivo',
        'archivo_original',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'importe' => 'decimal:2',
        ];
    }

    public function getProcesadoAttribute(): bool
    {
        return $this->estado === 'procesado';
    }
}
