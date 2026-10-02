<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Metadata;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

/**
 * sitemap.xml: las secciones públicas y todos los productos que se ven en el
 * sitio, para que Google los encuentre. Son ~40.000 direcciones, así que se
 * arma a un archivo y se rehace como mucho una vez por hora.
 */
class SitemapController extends Controller
{
    private const ARCHIVO = 'sitemap.xml';

    private const MINUTOS = 60;

    public function __invoke()
    {
        $disco = Storage::disk('local');

        if (! $disco->exists(self::ARCHIVO) || $disco->lastModified(self::ARCHIVO) < now()->subMinutes(self::MINUTOS)->timestamp) {
            $this->generar($disco->path(self::ARCHIVO));
        }

        return response()->file($disco->path(self::ARCHIVO), [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }

    private function generar(string $destino): void
    {
        $temporal = $destino . '.tmp';
        $xml = fopen($temporal, 'w');

        fwrite($xml, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($xml, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n");

        foreach (Metadata::SECTIONS as $seccion) {
            if (! $seccion['privada']) {
                fwrite($xml, $this->url(route($seccion['rutas'][0]), null, 'daily', '0.8'));
            }
        }

        Product::publicables()
            ->whereNotNull('code')
            ->select(['id', 'code', 'updated_at'])
            ->chunkById(2000, function ($productos) use ($xml) {
                foreach ($productos as $producto) {
                    fwrite($xml, $this->url(route('producto', $producto->code), $producto->updated_at?->toAtomString(), 'weekly', '0.6'));
                }
            });

        fwrite($xml, "</urlset>\n");
        fclose($xml);

        // Se reemplaza de una: quien lo pida mientras se arma recibe el anterior.
        rename($temporal, $destino);
    }

    private function url(string $loc, ?string $lastmod, string $frecuencia, string $prioridad): string
    {
        return '<url><loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>'
            . ($lastmod ? "<lastmod>{$lastmod}</lastmod>" : '')
            . "<changefreq>{$frecuencia}</changefreq><priority>{$prioridad}</priority></url>\n";
    }
}
