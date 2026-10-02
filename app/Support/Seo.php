<?php

namespace App\Support;

use App\Models\Metadata;
use App\Models\Product;

/**
 * Metadata de cada página: título, descripción, keywords, robots, canonical,
 * imagen para compartir y datos estructurados.
 *
 * - Secciones: lo cargado en Admin → Metadata SEO, o el texto por defecto
 *   de la sección (Metadata::SECTIONS).
 * - Detalle de producto: automática con los datos de Odoo (ProductoSeo), o
 *   lo cargado a mano en ese producto.
 * - Admin, ingreso y secciones privadas: no se indexan.
 */
class Seo
{
    private const DESCRIPCION = 'Autopartes y repuestos para todas las marcas. Catálogo mayorista Rejovot.';

    private const KEYWORDS = 'rejovot, autopartes, repuestos, mayorista';

    public static function forCurrentRoute(array $overrides = []): array
    {
        $ruta = optional(request()->route())->getName();

        $seo = match (true) {
            request()->is('admin*') || in_array($ruta, ['login', 'ingresar'], true) => self::admin(),
            $ruta === 'producto' => self::producto((string) request()->route('codigo')) ?? self::seccion('productos'),
            default => self::seccion(Metadata::seccionDeRuta($ruta)),
        };

        $seo = array_merge([
            'canonical' => url()->current(),
            'image' => asset('og-image.png'),
            'image_propia' => false,
            'type' => 'website',
            'schema' => null,
        ], $seo);

        return [
            'title' => $overrides['title'] ?? $seo['title'],
            'description' => $overrides['description'] ?? self::clean($seo['description']),
            'keywords' => $overrides['keywords'] ?? self::clean($seo['keywords']),
            'robots' => $overrides['robots'] ?? $seo['robots'],
            'canonical' => $overrides['canonical'] ?? $seo['canonical'],
            'image' => $overrides['image'] ?? $seo['image'],
            // La imagen por defecto mide 1200×630; la de un producto, no se sabe.
            'image_propia' => $seo['image_propia'],
            'type' => $overrides['type'] ?? $seo['type'],
            'schema' => $seo['schema'],
            'site_name' => self::brand(),
        ];
    }

    private static function seccion(?string $clave): array
    {
        $base = Metadata::SECTIONS[$clave] ?? null;
        $cargada = $clave ? Metadata::getForSection($clave) : null;

        $titulo = $cargada?->title ?: ($base['title'] ?? null);
        $seo = [
            'title' => self::title($titulo),
            'description' => $cargada?->description ?: ($base['description'] ?? self::DESCRIPCION),
            'keywords' => $cargada?->keywords ?: (($base['keywords'] ?? '') ?: self::KEYWORDS),
            'robots' => ($base['privada'] ?? false) ? 'noindex, nofollow' : 'index, follow',
        ];

        return self::filtros($clave, $seo);
    }

    /**
     * Listados filtrados por marca o rubro: título propio («Repuestos FERODO»),
     * que es como se buscan. El resto de los filtros apuntan al listado general.
     */
    private static function filtros(?string $clave, array $seo): array
    {
        if (! in_array($clave, ['productos', 'vehiculos'], true)) {
            return $seo;
        }

        $marca = trim((string) request()->query('marca'));
        $rubro = trim((string) request()->query('rubro'));

        if ($marca !== '') {
            $seo['title'] = self::title("Repuestos {$marca}");
            $seo['description'] = "Autopartes y repuestos {$marca} al por mayor, con stock y precios actualizados. " . $seo['description'];
            $seo['canonical'] = url()->current() . '?' . http_build_query(['marca' => $marca]);
        } elseif ($rubro !== '' && $clave === 'productos') {
            $seo['title'] = self::title("{$rubro} - autopartes");
            $seo['canonical'] = url()->current() . '?' . http_build_query(['rubro' => $rubro]);
        }

        return $seo;
    }

    private static function producto(string $codigo): ?array
    {
        $producto = Product::with(['brand', 'category'])->publicables()->where('code', $codigo)->first();

        if (! $producto) {
            return null;
        }

        $seo = ProductoSeo::para($producto);

        return [
            // El título del producto ya trae « | Rejovot».
            'title' => $seo['title'],
            'description' => $seo['description'],
            'keywords' => $seo['keywords'],
            'robots' => 'index, follow',
            'canonical' => route('producto', $producto->code),
            'image' => $producto->imagen_url,
            'image_propia' => true,
            'type' => 'product',
            'schema' => ProductoSeo::schema($producto, $seo),
        ];
    }

    private static function admin(): array
    {
        return [
            'title' => self::title('Panel administrativo'),
            'description' => 'Panel administrativo Rejovot.',
            'keywords' => 'rejovot, admin',
            'robots' => 'noindex, nofollow',
        ];
    }

    public static function brand(): string
    {
        $name = config('app.name');

        return $name && $name !== 'Laravel' ? $name : 'Rejovot';
    }

    public static function title(?string $page = null): string
    {
        return filled($page) ? trim($page) . ' | ' . self::brand() : self::brand();
    }

    private static function clean($value): string
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text ?? '');
    }
}
