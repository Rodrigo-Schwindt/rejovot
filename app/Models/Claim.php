<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Reclamo de un cliente sobre una compra. */
class Claim extends Model
{
    protected $table = 'claims';

    public const ESTADOS = [
        'enviado' => 'Enviado',
        'rechazado' => 'Rechazado',
        'cerrado' => 'Cerrado',
        'nota_credito' => 'Nota de crédito',
    ];

    protected $fillable = [
        'customer_id',
        'user_id',
        'fecha',
        'factura_numero',
        'factura_fecha',
        'factura_odoo_id',
        'estado',
        'respuesta',
        'respondido_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'factura_fecha' => 'date',
            'respondido_at' => 'datetime',
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

    public function items(): HasMany
    {
        return $this->hasMany(ClaimItem::class);
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(ClaimPhoto::class);
    }

    /** Número visible: el id con ceros a la izquierda. */
    public function getNumeroAttribute(): string
    {
        return str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getEstadoNombreAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    /** Acepta «255805» o «000123»: el número visible es el id con ceros. */
    public static function idDesdeNumero(string $numero): ?int
    {
        $limpio = ltrim(preg_replace('/\D/', '', $numero), '0');

        return $limpio === '' ? null : (int) $limpio;
    }
}
