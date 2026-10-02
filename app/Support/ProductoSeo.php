<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Str;

/**
 * Metadata de cada producto, generada sola con los datos de Odoo: nombre,
 * marca, rubro, código y códigos OEM. Si en el admin se cargó algo a mano en
 * el producto (seo_title, seo_description, seo_keywords), eso tiene prioridad.
 *
 * No lleva precios: dependen del cliente y se ven sólo dentro del sitio.
 */
class ProductoSeo
{
    /** Largo que Google muestra antes de cortar (algo más lo indexa igual). */
    private const LARGO_TITULO = 70;

    private const LARGO_DESCRIPCION = 160;

    /** El nombre en la descripción se acorta para que entren código y OEM. */
    private const LARGO_NOMBRE_EN_DESCRIPCION = 80;

    /** Palabras chicas que van en minúscula en el título. */
    private const MINUSCULAS = ['de', 'del', 'la', 'las', 'el', 'los', 'y', 'e', 'o', 'con', 'sin', 'para', 'por', 'a', 'en', 'al', 'x'];

    /** Lo que se usa en la página: lo cargado a mano o, si no, lo automático. */
    public static function para(Product $producto): array
    {
        $auto = self::automatico($producto);

        return [
            'title' => $producto->seo_title ?: $auto['title'],
            'description' => $producto->seo_description ?: $auto['description'],
            'keywords' => $producto->seo_keywords ?: $auto['keywords'],
        ];
    }

    /** Sólo lo generado con los datos de Odoo, sin lo cargado a mano. */
    public static function automatico(Product $producto): array
    {
        $nombre = self::nombreLindo($producto);
        $marca = trim((string) $producto->brand?->name);
        $rubro = self::capitalizar(trim((string) $producto->category?->name));
        $codigo = trim((string) $producto->code);
        $oem = self::oem($producto);

        return [
            'title' => self::titulo($nombre, $codigo),
            'description' => self::descripcion($nombre, $marca, $rubro, $codigo, $oem),
            'keywords' => self::keywords($producto, $marca, $rubro, $codigo, $oem),
        ];
    }

    /** Datos estructurados (schema.org/Product) para los buscadores. */
    public static function schema(Product $producto, array $seo): array
    {
        $oem = self::oem($producto);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => self::nombreLindo($producto),
            'sku' => $producto->code ?: null,
            'mpn' => $oem[0] ?? null,
            'description' => $seo['description'],
            'image' => $producto->imagenes,
            'url' => route('producto', $producto->code),
            'category' => $producto->category?->complete_name ?: $producto->category?->name,
            'brand' => $producto->brand ? ['@type' => 'Brand', 'name' => $producto->brand->name] : null,
        ]);
    }

    /** «* BAIML FARO TRASERO TIPO MB…» → «Baiml Faro Trasero Tipo MB…» (la marca, tal cual). */
    public static function nombreLindo(Product $producto): string
    {
        $nombre = preg_replace('/\s+/u', ' ', trim(ltrim((string) $producto->name, "* \t")));
        $marca = mb_strtoupper(trim((string) $producto->brand?->name));

        return self::capitalizar($nombre, $marca);
    }

    private static function titulo(string $nombre, string $codigo): string
    {
        $sufijo = ($codigo !== '' ? " - {$codigo}" : '') . ' | ' . Seo::brand();
        $lugar = max(20, self::LARGO_TITULO - mb_strlen($sufijo));

        return self::cortar($nombre, $lugar) . $sufijo;
    }

    private static function descripcion(string $nombre, string $marca, string $rubro, string $codigo, array $oem): string
    {
        $partes = array_filter([
            $nombre . '.',
            $marca !== '' ? "Marca {$marca}." : null,
            $rubro !== '' ? "Rubro {$rubro}." : null,
            $codigo !== '' ? "Código {$codigo}." : null,
            $oem ? 'OEM ' . implode(', ', array_slice($oem, 0, 3)) . '.' : null,
            'Autopartes al por mayor en ' . Seo::brand() . '.',
        ]);

        // El nombre siempre; después, todo lo que entre en orden.
        $texto = self::cortar(rtrim(array_shift($partes), '.'), self::LARGO_NOMBRE_EN_DESCRIPCION);
        $texto = str_ends_with($texto, '…') ? $texto : $texto . '.';

        foreach ($partes as $parte) {
            if (mb_strlen($texto . ' ' . $parte) <= self::LARGO_DESCRIPCION) {
                $texto .= ' ' . $parte;
            }
        }

        return $texto;
    }

    private static function keywords(Product $producto, string $marca, string $rubro, string $codigo, array $oem): string
    {
        // Las palabras con contenido del nombre: «faro», «trasero», «ranger»…
        $palabras = collect(preg_split('/[\s,.\/()*-]+/u', mb_strtolower((string) $producto->name)))
            ->filter(fn ($p) => mb_strlen($p) >= 4 && ! preg_match('/\d/', $p))
            ->take(6);

        // Los códigos van tal cual; el resto, en minúscula.
        $textos = collect([$marca, $rubro, ...$palabras, 'autopartes', 'repuestos'])
            ->map(fn ($k) => mb_strtolower(trim((string) $k)));

        return collect([$codigo, ...array_slice($oem, 0, 5)])
            ->map(fn ($k) => trim((string) $k))
            ->merge($textos)
            ->filter()
            ->unique()
            ->implode(', ');
    }

    /** @return array<int, string> códigos OEM (vienen separados por espacios, comas o barras) */
    private static function oem(Product $producto): array
    {
        return array_values(array_filter(preg_split('/[\s,;|]+/', trim((string) $producto->oem_codes))));
    }

    /**
     * Mayúscula inicial por palabra, sin romper lo técnico: palabras con
     * números o barras («12V», «S/VEN»), siglas cortas («MB», «BMW») y la
     * marca quedan como vienen.
     */
    private static function capitalizar(string $texto, string $marca = ''): string
    {
        return preg_replace_callback('/\S+/u', function ($m) use ($marca) {
            $palabra = $m[0];
            $minuscula = mb_strtolower($palabra);

            return match (true) {
                $marca !== '' && mb_strtoupper($palabra) === $marca => $palabra,
                (bool) preg_match('/[\d\/]/', $palabra) => $palabra,
                in_array($minuscula, self::MINUSCULAS, true) => $minuscula,
                mb_strlen($palabra) <= 3 => mb_strtoupper($palabra),
                default => mb_convert_case($minuscula, MB_CASE_TITLE),
            };
        }, $texto) ?? $texto;
    }

    /** Corta en el último espacio que entre, no a mitad de palabra. */
    private static function cortar(string $texto, int $largo): string
    {
        if (mb_strlen($texto) <= $largo) {
            return $texto;
        }

        $corte = mb_substr($texto, 0, $largo - 1);
        $espacio = mb_strrpos($corte, ' ');

        if ($espacio !== false && $espacio > $largo / 2) {
            $corte = mb_substr($corte, 0, $espacio);
        }

        // Que no termine en «de», «y», «e»… antes de los puntos suspensivos.
        $corte = preg_replace('/\s+(' . implode('|', self::MINUSCULAS) . ')$/u', '', rtrim($corte, ' ,.-/'));

        return $corte . '…';
    }
}
