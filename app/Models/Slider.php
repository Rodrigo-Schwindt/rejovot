<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Banner administrable del carrusel de Productos, con textos, colores y hasta
 * tres imágenes. Se suma a los banners de productos destacados, que no cambian.
 */
class Slider extends Model
{
    public const MAXIMO_IMAGENES = 3;

    protected $fillable = [
        'etiqueta',
        'etiqueta_color',
        'etiqueta_fondo',
        'titulo',
        'titulo_color',
        'descripcion',
        'descripcion_color',
        'fondo',
        'imagenes',
        'activo',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'imagenes' => 'array',
            'activo' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** Los que se ven en el sitio, en el orden que eligió el admin. */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true)->ordenados();
    }

    public function scopeOrdenados(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** @return array<int, string> */
    public function getImagenesUrlsAttribute(): array
    {
        return array_map(fn (string $ruta) => Storage::url($ruta), $this->imagenes ?? []);
    }
}
