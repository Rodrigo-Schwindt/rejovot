<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Vendedor: usuario interno de Odoo con clientes a cargo. */
class Salesperson extends Model
{
    protected $table = 'salespeople';

    protected $fillable = ['odoo_id', 'name', 'login', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
