<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Metadata extends Model
{
    protected $table = 'metadata';

    protected $fillable = [
        'section',
        'keywords',
        'description',
    ];

    /** Secciones editables desde el admin (las que no dependen de Odoo). */
    public const SECTIONS = [
        'home'      => 'Inicio',
        'productos' => 'Productos',
        'contacto'  => 'Contacto',
    ];

    public static function getForSection(string $section): ?self
    {
        return static::where('section', $section)->first();
    }
}
