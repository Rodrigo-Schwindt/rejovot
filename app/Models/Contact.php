<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Datos institucionales del sitio: NO vienen de Odoo, se cargan desde el admin.
 */
class Contact extends Model
{
    protected $table = 'contact';

    protected $fillable = [
        'direction_adm',
        'phone_amd',
        'mail_adm',
        'mail_comprobantes',
        'wssp',
        'maps_adm',
        'frame_adm',
        'facebook',
        'insta',
        'linkedin',
        'youtube',
        'icono_1',
        'icono_2',
        'icono_3',
    ];

    /**
     * Casilla que recibe los comprobantes de pago. Si no se cargó una propia,
     * van al mail de contacto general.
     */
    public static function mailComprobantes(): ?string
    {
        $contacto = static::first();

        return $contacto?->mail_comprobantes ?: $contacto?->mail_adm;
    }

    public function infoItems()
    {
        return $this->hasMany(ContactInfoItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
