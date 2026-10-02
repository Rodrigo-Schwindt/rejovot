<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Metadata SEO por sección del sitio. Lo que se carga en el admin pisa el
 * texto por defecto de cada sección; vacío, se usa el de acá.
 */
class Metadata extends Model
{
    protected $table = 'metadata';

    protected $fillable = [
        'section',
        'title',
        'keywords',
        'description',
    ];

    /**
     * Secciones del sitio. `rutas`: qué páginas usan esa metadata.
     * `privada`: hace falta ingresar para verla; Google no la indexa.
     */
    public const SECTIONS = [
        'productos' => [
            'nombre' => 'Productos',
            'rutas' => ['productos', 'home'],
            'privada' => false,
            'title' => 'Catálogo mayorista de autopartes y repuestos',
            'description' => 'Catálogo mayorista de autopartes y repuestos de todas las marcas, con stock y precios actualizados. Buscá por código, OEM, marca o rubro.',
            'keywords' => 'autopartes, repuestos, repuestos mayorista, catálogo de autopartes, distribuidora de autopartes',
        ],
        'vehiculos' => [
            'nombre' => 'Búsqueda por marca',
            'rutas' => ['vehiculos'],
            'privada' => false,
            'title' => 'Repuestos por marca',
            'description' => 'Encontrá autopartes y repuestos por marca en el catálogo mayorista: todas las marcas que trabajamos, con stock actualizado.',
            'keywords' => 'repuestos por marca, autopartes por marca, marcas de repuestos',
        ],
        'precios' => [
            'nombre' => 'Lista de precios',
            'rutas' => ['precios'],
            'privada' => false,
            'title' => 'Lista de precios',
            'description' => 'Lista de precios mayorista de autopartes actualizada. Descargala en PDF o CSV.',
            'keywords' => 'lista de precios autopartes, precios repuestos mayorista',
        ],
        'margenes' => [
            'nombre' => 'Márgenes',
            'rutas' => ['margenes'],
            'privada' => true,
            'title' => 'Márgenes',
            'description' => 'Configurá tus márgenes de venta sobre la lista de precios.',
            'keywords' => '',
        ],
        'carrito' => [
            'nombre' => 'Carrito',
            'rutas' => ['carrito'],
            'privada' => true,
            'title' => 'Carrito',
            'description' => 'Tu pedido de autopartes.',
            'keywords' => '',
        ],
        'pedidos' => [
            'nombre' => 'Mis pedidos',
            'rutas' => ['pedidos'],
            'privada' => true,
            'title' => 'Mis pedidos',
            'description' => 'Historial y estado de tus pedidos.',
            'keywords' => '',
        ],
        'cuenta' => [
            'nombre' => 'Estado de la cuenta',
            'rutas' => ['cuenta'],
            'privada' => true,
            'title' => 'Estado de la cuenta',
            'description' => 'Saldo, vencimientos y comprobantes de tu cuenta corriente.',
            'keywords' => '',
        ],
        'pagos' => [
            'nombre' => 'Info de pagos',
            'rutas' => ['pagos'],
            'privada' => true,
            'title' => 'Info de pagos',
            'description' => 'Datos bancarios y aviso de pagos.',
            'keywords' => '',
        ],
        'reclamos' => [
            'nombre' => 'Reclamos',
            'rutas' => ['reclamos', 'reclamos.nuevo', 'reclamos.ver'],
            'privada' => true,
            'title' => 'Reclamos',
            'description' => 'Tus reclamos y su estado.',
            'keywords' => '',
        ],
    ];

    public static function getForSection(string $section): ?self
    {
        return static::where('section', $section)->first();
    }

    /** Sección a la que pertenece una ruta, o null. */
    public static function seccionDeRuta(?string $ruta): ?string
    {
        foreach (self::SECTIONS as $clave => $seccion) {
            if (in_array($ruta, $seccion['rutas'], true)) {
                return $clave;
            }
        }

        return null;
    }
}
