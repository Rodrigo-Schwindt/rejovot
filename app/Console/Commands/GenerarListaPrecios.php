<?php

namespace App\Console\Commands;

use App\Models\PriceList;
use App\Models\Product;
use App\Support\PdfTabla;
use App\Support\Precio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Arma la lista de precios con el catálogo que está publicado en el sitio.
 *
 * Reemplaza siempre la misma fila, así el link que ya tiene el cliente sigue
 * funcionando y no se acumulan listas viejas.
 */
class GenerarListaPrecios extends Command
{
    protected $signature = 'precios:generar
        {--no-publicar : La deja cargada pero oculta en el sitio}';

    protected $description = 'Genera la lista de precios en CSV con los productos publicados';

    /** Excel en español separa con punto y coma, no con coma. */
    private const SEPARADOR = ';';

    private const CARPETA = 'listas-precios';

    public function handle(): int
    {
        $inicio = microtime(true);

        $total = Product::publicables()->count();

        if ($total === 0) {
            $this->warn('  No hay productos publicados: no se genera nada.');

            return self::SUCCESS;
        }

        $this->line('  Productos publicados: ' . number_format($total, 0, ',', '.'));

        Storage::disk('public')->makeDirectory(self::CARPETA);

        $base = self::CARPETA . '/' . Str::random(40);
        $csv = $base . '.csv';
        $pdf = $base . '.pdf';

        $barra = $this->output->createProgressBar($total);
        $barra->start();

        [$filas, $paginas] = $this->escribir(
            Storage::disk('public')->path($csv),
            Storage::disk('public')->path($pdf),
            $barra,
        );

        $barra->finish();
        $this->newLine(2);

        $lista = $this->publicar($csv, $pdf, $filas);

        $segundos = round(microtime(true) - $inicio, 1);
        $this->info("  {$filas} productos en {$segundos}s.");
        $this->info("  CSV para descargar: {$lista->tamano_legible}");
        $this->info('  PDF para ver en pantalla: ' . $this->legible($lista->tamano_pdf) . " ({$paginas} páginas)");
        $this->info('  ' . ($lista->publicada ? 'Publicada' : 'Cargada sin publicar') . ' como «' . $lista->descripcion . '».');

        return self::SUCCESS;
    }

    /**
     * Escribe los dos archivos en la misma pasada, de a tandas: son decenas de
     * miles de filas y no entran en memoria.
     *
     * @return array{0:int, 1:int} filas y páginas del PDF
     */
    private function escribir(string $rutaCsv, string $rutaPdf, $barra): array
    {
        $csv = fopen($rutaCsv, 'w');

        // Sin BOM, Excel abre los acentos mal.
        fwrite($csv, "\xEF\xBB\xBF");

        fputcsv($csv, [
            'Código',
            'Descripción',
            'Marca',
            'Rubro',
            'Código OEM',
            'Precio de lista (sin IVA)',
            'Stock',
            'Oferta %',
            'Oferta hasta',
        ], self::SEPARADOR);

        $pdf = new PdfTabla($rutaPdf, 'Lista de precios · Rejovot Autopartes',
            'Actualizada el ' . now()->format('d/m/Y') . ' · Precios sin IVA', [
                ['titulo' => 'Código', 'ancho' => 78.0],
                ['titulo' => 'Descripción', 'ancho' => 352.0],
                ['titulo' => 'Marca', 'ancho' => 88.0],
                ['titulo' => 'Rubro', 'ancho' => 132.0],
                ['titulo' => 'Precio', 'ancho' => 74.0, 'alineacion' => 'derecha'],
                ['titulo' => 'Stock', 'ancho' => 40.0, 'alineacion' => 'derecha'],
                ['titulo' => 'Oferta', 'ancho' => 38.0, 'alineacion' => 'derecha'],
            ]);

        $filas = 0;

        Product::with(['brand', 'category'])
            ->publicables()
            ->orderBy('name')
            ->chunk(1000, function ($productos) use ($csv, $pdf, $barra, &$filas) {
                foreach ($productos as $producto) {
                    fputcsv($csv, [
                        $producto->code ?? '',
                        $producto->name,
                        $producto->brand?->name ?? '',
                        $producto->category?->name ?? '',
                        $producto->oem_codes ?? '',
                        // Con coma decimal: es el separador que espera Excel acá.
                        number_format((float) $producto->list_price, 2, ',', ''),
                        (int) $producto->stock,
                        $producto->en_oferta ? number_format((float) $producto->discount_percent, 2, ',', '') : '',
                        $producto->en_oferta ? $producto->discount_to?->format('d/m/Y') : '',
                    ], self::SEPARADOR);

                    $pdf->fila([
                        $producto->code ?? '',
                        $producto->name,
                        $producto->brand?->name ?? '',
                        $producto->category?->name ?? '',
                        '$ ' . number_format((float) $producto->list_price, 2, ',', '.'),
                        (string) (int) $producto->stock,
                        $producto->en_oferta ? rtrim(rtrim(number_format((float) $producto->discount_percent, 2, ',', ''), '0'), ',') . '%' : '',
                    ]);

                    $filas++;
                }

                $barra->advance($productos->count());
            });

        fclose($csv);

        return [$filas, $pdf->cerrar()];
    }

    private function legible(int $bytes): string
    {
        return $bytes < 1024 * 1024
            ? number_format($bytes / 1024, 0, ',', '.') . ' KB'
            : number_format($bytes / 1024 / 1024, 1, ',', '.') . ' MB';
    }

    /** Pisa la lista automática anterior, conservando su id y su link. */
    private function publicar(string $archivo, string $pdf, int $filas): PriceList
    {
        $lista = PriceList::automatica()->first();
        $anteriores = array_filter([$lista?->archivo, $lista?->archivo_pdf]);

        $datos = [
            'descripcion' => 'Lista de precios completa',
            'formato' => 'csv',
            'origen' => PriceList::ORIGEN_ODOO,
            'archivo' => $archivo,
            'archivo_pdf' => $pdf,
            'archivo_original' => 'lista-precios-rejovot-' . now()->format('Y-m-d') . '.csv',
            'tamano' => Storage::disk('public')->size($archivo),
            'tamano_pdf' => Storage::disk('public')->size($pdf),
            'vigencia' => 'Actualizada el ' . now()->format('d/m/Y'),
            'notas' => number_format($filas, 0, ',', '.') . ' productos publicados. Precios sin IVA.',
            'generada_at' => now(),
        ];

        if ($lista) {
            $lista->update($datos);
        } else {
            $lista = PriceList::create($datos + [
                'publicada' => ! $this->option('no-publicar'),
                // Va primera: es la única que está siempre al día.
                'sort_order' => 0,
            ]);
        }

        // Los archivos viejos ya no los referencia nadie.
        foreach ($anteriores as $viejo) {
            if ($viejo !== $archivo && $viejo !== $pdf) {
                Storage::disk('public')->delete($viejo);
            }
        }

        return $lista->refresh();
    }
}
