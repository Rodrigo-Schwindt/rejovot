<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comprobante de pago que envía el cliente desde Info de pagos.
 */
class PaymentReceipt extends Model
{
    protected $table = 'payment_receipts';

    protected $fillable = [
        'customer_id',
        'user_id',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getProcesadoAttribute(): bool
    {
        return $this->estado === 'procesado';
    }
}
