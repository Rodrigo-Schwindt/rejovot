<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Listas de precios publicadas para descargar. El archivo lo sube el cliente
 * desde el admin: no viene de Odoo.
 */
class PriceList extends Model
{
    protected $table = 'price_lists';

    protected $fillable = [
        'descripcion',
        'formato',
        'archivo',
        'archivo_original',
        'tamano',
        'vigencia',
        'notas',
        'publicada',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'publicada' => 'boolean',
            'tamano' => 'integer',
        ];
    }

    public function scopePublicadas(Builder $query): Builder
    {
        return $query->where('publicada', true);
    }

    /** Formato tal cual se muestra en la tabla: PDF, EXCEL, CSV. */
    public function getFormatoNombreAttribute(): string
    {
        return strtoupper($this->formato);
    }

    public function getEsPdfAttribute(): bool
    {
        return $this->formato === 'pdf';
    }

    /** Peso legible: 1,2 MB / 340 KB. */
    public function getTamanoLegibleAttribute(): string
    {
        $bytes = (int) $this->tamano;

        if ($bytes <= 0) {
            return '—';
        }

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 0, ',', '.') . ' KB';
        }

        return number_format($bytes / 1024 / 1024, 1, ',', '.') . ' MB';
    }

    /** Deduce el formato a partir de la extensión del archivo subido. */
    public static function formatoDesdeExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf' => 'pdf',
            'xls', 'xlsx' => 'excel',
            default => 'csv',
        };
    }
}
