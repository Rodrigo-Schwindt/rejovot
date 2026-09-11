<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Listas de precios publicadas para descargar.
 *
 * Hay de dos orígenes: las que sube el cliente desde el admin (`manual`) y la
 * que arma `precios:generar` con el catálogo publicado de Odoo (`odoo`).
 */
class PriceList extends Model
{
    protected $table = 'price_lists';

    /** La genera el comando `precios:generar` con el catálogo publicado. */
    public const ORIGEN_ODOO = 'odoo';

    protected $fillable = [
        'descripcion',
        'formato',
        'origen',
        'archivo',
        'archivo_pdf',
        'archivo_original',
        'tamano',
        'tamano_pdf',
        'vigencia',
        'notas',
        'publicada',
        'sort_order',
        'generada_at',
    ];

    protected function casts(): array
    {
        return [
            'publicada' => 'boolean',
            'tamano' => 'integer',
            'tamano_pdf' => 'integer',
            'generada_at' => 'datetime',
        ];
    }

    public function scopePublicadas(Builder $query): Builder
    {
        return $query->where('publicada', true);
    }

    /** La que arma el sistema con el catálogo, no una subida a mano. */
    public function scopeAutomatica(Builder $query): Builder
    {
        return $query->where('origen', self::ORIGEN_ODOO);
    }

    public function getEsAutomaticaAttribute(): bool
    {
        return $this->origen === self::ORIGEN_ODOO;
    }

    /**
     * Formato tal cual se muestra en la tabla: PDF, EXCEL, CSV. La lista
     * generada se baja en CSV pero también se puede ver en PDF.
     */
    public function getFormatoNombreAttribute(): string
    {
        return strtoupper($this->formato) . ($this->archivo_pdf ? ' · PDF' : '');
    }

    public function getEsPdfAttribute(): bool
    {
        return $this->formato === 'pdf';
    }

    /** Se puede abrir en el navegador: o es un PDF, o tiene su versión PDF. */
    public function getPuedeVerseAttribute(): bool
    {
        return $this->es_pdf || (bool) $this->archivo_pdf;
    }

    /** El archivo que se muestra en pantalla, que no siempre es el que se baja. */
    public function getArchivoParaVerAttribute(): ?string
    {
        return $this->archivo_pdf ?: ($this->es_pdf ? $this->archivo : null);
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
