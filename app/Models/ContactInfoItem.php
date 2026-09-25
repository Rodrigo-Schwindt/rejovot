<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactInfoItem extends Model
{
    protected $table = 'contact_info_items';

    protected $fillable = [
        'contact_id',
        'type',
        'value',
        'is_fixed',
        'sort_order',
    ];

    protected $casts = [
        'is_fixed' => 'boolean',
    ];

    public const TYPES = [
        'direccion'         => 'Dirección',
        'whatsapp'          => 'WhatsApp',
        'whatsapp_flotante' => 'WhatsApp flotante',
        'telefono'          => 'Teléfono',
        'email'             => 'Email',
    ];

    /**
     * Hay uno solo de cada uno y no se pueden borrar: el WhatsApp flotante es
     * el botón del sitio y al email llegan los avisos de las ventas.
     */
    public const TIPOS_FIJOS = ['whatsapp_flotante', 'email'];

    /** Los que se pueden sumar como dato nuevo. */
    public static function tiposAgregables(): array
    {
        return array_diff_key(self::TYPES, array_flip(self::TIPOS_FIJOS));
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getHrefAttribute(): string
    {
        $value = $this->value ?? '';

        return match ($this->type) {
            'direccion' => 'https://www.google.com/maps/search/?api=1&query=' . urlencode($value),
            'whatsapp', 'whatsapp_flotante' => 'https://wa.me/' . preg_replace('/\D/', '', $value),
            'telefono'  => 'tel:' . preg_replace('/\s+/', '', $value),
            'email'     => 'mailto:' . $value,
            default     => '#',
        };
    }
}
