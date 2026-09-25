<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Márgenes y descuento que configuró un cliente desde la pantalla Márgenes. */
class MarginSetting extends Model
{
    protected $table = 'margin_settings';

    protected $fillable = ['customer_id', 'general', 'descuento', 'marcas', 'familias'];

    protected function casts(): array
    {
        return [
            'general' => 'float',
            'descuento' => 'float',
            'marcas' => 'array',
            'familias' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
