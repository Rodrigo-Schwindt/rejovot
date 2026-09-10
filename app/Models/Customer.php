<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cliente, espejo de res.partner. */
class Customer extends Model
{
    protected $fillable = [
        'odoo_id',
        'name',
        'vat',
        'email',
        'phone',
        'city',
        'salesperson_id',
        'pricelist_odoo_id',
        'price_discount',
        'price_margin',
        'credit',
        'credit_limit',
        'portal_login',
        'has_portal',
        'active',
        'odoo_write_date',
    ];

    protected function casts(): array
    {
        return [
            'price_discount' => 'decimal:2',
            'price_margin' => 'decimal:2',
            'credit' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'has_portal' => 'boolean',
            'active' => 'boolean',
            'odoo_write_date' => 'datetime',
        ];
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(Salesperson::class);
    }

    /** Búsqueda del selector: por nombre o CUIT. */
    public function scopeBuscar(Builder $query, string $texto): Builder
    {
        $texto = trim($texto);

        if ($texto === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($texto) {
            $q->where('name', 'like', '%' . $texto . '%')
                ->orWhere('vat', 'like', '%' . $texto . '%');
        });
    }

    /** Precio del cliente sobre la lista pública. */
    public function precioNeto(float $lista): float
    {
        return round($lista * (1 - (float) $this->price_discount / 100), 2);
    }
}
