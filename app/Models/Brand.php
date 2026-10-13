<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categoría de ecommerce de Odoo (product.public.category): casi todas son
 * marcas; algunas, como NOVEDADES, agrupan productos de varias marcas
 * (es_marca = false). La sincroniza catalogo:marcas.
 *
 * Un producto puede estar en varias (productos()); su marca (Product::brand)
 * es la primera que sea marca.
 */
class Brand extends Model
{
    protected $fillable = ['odoo_id', 'name', 'slug', 'sort', 'es_marca'];

    protected function casts(): array
    {
        return ['es_marca' => 'boolean'];
    }

    /** Todos los productos de la categoría, sea o no su marca principal. */
    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * Marca de un producto según sus categorías de ecommerce: la primera que
     * sea marca (NOVEDADES no lo es). Si tiene varias, gana la primera en el
     * orden de Odoo.
     *
     * @param  array<int, int>  $categorias  ids de product.public.category
     * @param  array<int, int>  $mapa  odoo_id => id local, en el orden de Odoo
     */
    public static function deCategorias(array $categorias, array $mapa): ?int
    {
        foreach ($mapa as $odooId => $id) {
            if (in_array($odooId, $categorias, true)) {
                return $id;
            }
        }

        return null;
    }

    /**
     * @param  bool  $soloMarcas  sin las categorías que no son marca (NOVEDADES)
     * @return array<int, int> odoo_id => id local, en el orden de Odoo
     */
    public static function mapa(bool $soloMarcas = true): array
    {
        return static::whereNotNull('odoo_id')
            ->when($soloMarcas, fn ($q) => $q->where('es_marca', true))
            ->orderBy('sort')
            ->pluck('id', 'odoo_id')
            ->all();
    }

    /** @return array<int, int> ids locales de las categorías del producto */
    public static function categoriasDe(array $categorias, array $mapaCompleto): array
    {
        return array_values(array_intersect_key($mapaCompleto, array_flip($categorias)));
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
