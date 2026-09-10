<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Odoo no tiene la marca como dato: se deduce de la primera palabra del nombre
 * (VMG BOMBA AGUA…, CAUPLAS MANGUERA…). Con un mínimo de 5 productos por marca
 * se cubre el 99,9% del catálogo publicable.
 *
 * Es un comando aparte del sync para poder recalcularlo sin volver a bajar todo.
 */
class SyncMarcas extends Command
{
    protected $signature = 'catalogo:marcas
        {--min=5 : Mínimo de productos para considerar que un prefijo es una marca}
        {--listar : Sólo muestra lo que haría, sin guardar}';

    protected $description = 'Deduce las marcas de los productos y las asigna';

    public function handle(): int
    {
        $minimo = max(1, (int) $this->option('min'));

        $this->line('  Analizando nombres de productos…');

        $conteo = [];

        Product::where('active', true)->where('published', true)
            ->select('id', 'name')
            ->chunk(5000, function ($chunk) use (&$conteo) {
                foreach ($chunk as $producto) {
                    $marca = self::marcaDesdeNombre($producto->name);

                    if ($marca) {
                        $conteo[$marca] = ($conteo[$marca] ?? 0) + 1;
                    }
                }
            });

        arsort($conteo);
        $marcas = array_filter($conteo, fn ($n) => $n >= $minimo);

        $this->info(sprintf(
            '  %s prefijos, %s superan el mínimo de %s productos',
            count($conteo),
            count($marcas),
            $minimo
        ));

        if ($this->option('listar')) {
            $this->newLine();
            foreach (array_slice($marcas, 0, 40, true) as $marca => $n) {
                $this->line(sprintf('    %-20s %s', $marca, number_format($n, 0, ',', '.')));
            }

            return self::SUCCESS;
        }

        // Alta de marcas
        foreach (array_keys($marcas) as $nombre) {
            Brand::firstOrCreate(['slug' => Str::slug($nombre)], ['name' => $nombre]);
        }

        $idsPorSlug = Brand::pluck('id', 'slug')->all();

        // Asignación en lote
        $asignados = 0;
        $sinMarca = 0;

        Product::select('id', 'name', 'brand_id')->chunkById(2000, function ($chunk) use ($idsPorSlug, &$asignados, &$sinMarca) {
            $porMarca = [];

            foreach ($chunk as $producto) {
                $marca = self::marcaDesdeNombre($producto->name);
                $brandId = $marca ? ($idsPorSlug[Str::slug($marca)] ?? null) : null;

                if (! $brandId) {
                    $sinMarca++;

                    continue;
                }

                $porMarca[$brandId][] = $producto->id;
            }

            DB::transaction(function () use ($porMarca, &$asignados) {
                foreach ($porMarca as $brandId => $ids) {
                    Product::whereIn('id', $ids)->update(['brand_id' => $brandId]);
                    $asignados += count($ids);
                }
            });
        });

        $this->info('  Marcas creadas: ' . number_format(Brand::count(), 0, ',', '.'));
        $this->info('  Productos con marca: ' . number_format($asignados, 0, ',', '.'));
        $this->info('  Productos sin marca: ' . number_format($sinMarca, 0, ',', '.'));

        return self::SUCCESS;
    }

    /** Primera palabra del nombre, si parece una marca. */
    public static function marcaDesdeNombre(?string $nombre): ?string
    {
        // Varios nombres arrancan con relleno: "* BAIML …", "**HUTCHINSON …".
        $limpio = ltrim((string) $nombre, " 	*-.—_/");
        $palabra = mb_strtoupper(trim(explode(' ', trim($limpio))[0] ?? ''));
        $palabra = trim($palabra, '*-.,/');

        if (mb_strlen($palabra) < 2 || is_numeric($palabra)) {
            return null;
        }

        return $palabra;
    }
}
