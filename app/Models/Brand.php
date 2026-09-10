<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Marca del producto. Odoo no tiene un campo limpio para esto: se arma en la
 * web (por ahora, del prefijo del nombre) hasta que el cliente defina el origen.
 */
class Brand extends Model
{
    protected $fillable = ['name', 'slug'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
