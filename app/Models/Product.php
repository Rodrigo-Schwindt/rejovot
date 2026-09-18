<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Espejo local de product.product de Odoo. */
class Product extends Model
{
    protected $fillable = [
        'odoo_id',
        'odoo_tmpl_id',
        'code',
        'name',
        'oem_codes',
        'type',
        'extra_image_ids',
        'category_id',
        'brand_id',
        'list_price',
        'stock',
        'active',
        'published',
        'oculto',
        'destacado',
        'discount_percent',
        'discount_from',
        'discount_to',
        'odoo_write_date',
    ];

    protected function casts(): array
    {
        return [
            'list_price' => 'decimal:2',
            'stock' => 'decimal:2',
            'active' => 'boolean',
            'published' => 'boolean',
            'oculto' => 'boolean',
            'destacado' => 'boolean',
            'discount_percent' => 'decimal:2',
            'extra_image_ids' => 'array',
            'discount_from' => 'datetime',
            'discount_to' => 'datetime',
            'odoo_write_date' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Alternativos y accesorios que carga Odoo en la pestaña Ventas. */
    public function relacionados(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_related', 'product_id', 'related_id')
            ->withPivot('tipo');
    }

    public function alternativos(): BelongsToMany
    {
        return $this->relacionados()->wherePivot('tipo', 'alternativo');
    }

    public function accesorios(): BelongsToMany
    {
        return $this->relacionados()->wherePivot('tipo', 'accesorio');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** Lo que se puede mostrar en el sitio. */
    public function scopeVisibles(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /** Tipo de producto de Odoo, con el nombre que se ve en pantalla. */
    public const TIPOS = [
        'product' => 'Almacenable',
        'consu' => 'Consumible',
        'service' => 'Servicio',
    ];

    public function getTipoNombreAttribute(): string
    {
        return self::TIPOS[$this->type] ?? '';
    }

    /** Semáforo de stock: sin stock, una unidad, dos o más. */
    public function getSemaforoAttribute(): string
    {
        $stock = (float) $this->stock;

        return match (true) {
            $stock <= 0 => 'rojo',
            $stock <= config('odoo.stock_bajo', 1) => 'amarillo',
            default => 'verde',
        };
    }

    /**
     * Lo que se publica en el sitio: activo en Odoo, marcado para web y no
     * ocultado a mano desde el admin.
     */
    public function scopePublicables(Builder $query): Builder
    {
        return $query->where('active', true)
            ->where('published', true)
            ->where('oculto', false);
    }

    /** Con una oferta vigente hoy. */
    public function scopeEnOferta(Builder $query): Builder
    {
        return $query->whereNotNull('discount_percent')
            ->where('discount_percent', '>', 0)
            ->where(fn (Builder $q) => $q->whereNull('discount_from')->orWhere('discount_from', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('discount_to')->orWhere('discount_to', '>=', now()));
    }

    public function getEnOfertaAttribute(): bool
    {
        if (! $this->discount_percent || $this->discount_percent <= 0) {
            return false;
        }

        $desde = $this->discount_from;
        $hasta = $this->discount_to;

        return (! $desde || $desde->isPast()) && (! $hasta || $hasta->isFuture());
    }

    /** La imagen la sirve Odoo. */
    public function getImagenUrlAttribute(): string
    {
        return rtrim((string) config('odoo.url'), '/') . "/web/image/product.template/{$this->odoo_tmpl_id}/image_512";
    }

    /**
     * Todas las imágenes: la principal primero y después las adicionales.
     * Sólo el 1,4% del catálogo tiene más de una.
     *
     * @return array<int, string>
     */
    public function getImagenesAttribute(): array
    {
        $base = rtrim((string) config('odoo.url'), '/');

        $extras = array_map(
            fn ($id) => "{$base}/web/image/product.image/{$id}/image_512",
            $this->extra_image_ids ?? [],
        );

        return [$this->imagen_url, ...$extras];
    }
}
