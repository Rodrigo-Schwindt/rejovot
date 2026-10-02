<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Usuario interno de Odoo. La tabla los tiene a todos (los clientes pueden
 * estar asignados a cualquiera), pero vendedores son sólo los que Rejovot
 * nombra así: GERENCIA y «VENDEDOR 1 (ema)», «VENDEDOR 2 (eze)»… Odoo no
 * tiene otro dato que los distinga. Uno nuevo, «VENDEDOR 9 (…)», entra solo.
 */
class Salesperson extends Model
{
    /** Nombres exactos que también son vendedores. */
    public const VENDEDORES_FIJOS = ['GERENCIA'];

    /** «VENDEDOR» y un número al principio del nombre. */
    public const PATRON_VENDEDOR = '/^VENDEDOR\s+\d+/i';

    protected $table = 'salespeople';

    protected $fillable = ['odoo_id', 'name', 'login', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public static function esVendedor(string $nombre): bool
    {
        $nombre = trim($nombre);

        return in_array(mb_strtoupper($nombre), self::VENDEDORES_FIJOS, true)
            || preg_match(self::PATRON_VENDEDOR, $nombre) === 1;
    }

    /** Sólo los vendedores de verdad (la misma regla que esVendedor). */
    public function scopeVendedores(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereIn(DB::raw('UPPER(TRIM(name))'), self::VENDEDORES_FIJOS)
            ->orWhereRaw("TRIM(name) REGEXP '^VENDEDOR[[:space:]]+[0-9]+'"));
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
