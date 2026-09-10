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

    public function infoItems()
    {
        return $this->hasMany(ContactInfoItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
