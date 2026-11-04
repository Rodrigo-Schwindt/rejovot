<?php

namespace App\Console\Commands;

use App\Models\PriceList;
use App\Models\Product;
use App\Support\PdfTabla;
use App\Support\Precio;
use App\Support\XlsxTabla;
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

    protected $description = 'Genera la lista de precios en CSV, Excel y PDF con los productos publicados';

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
        // Sin la extensión zip de PHP no hay Excel, pero la lista sale igual.
        $xlsx = class_exists(\ZipArchive::class) ? $base . '.xlsx' : null;

        if (! $xlsx) {
            $this->warn('  PHP no tiene la extensión zip: no se genera el Excel.');
        }

        $barra = $this->output->createProgressBar($total);
        $barra->start();

        [$filas, $paginas] = $this->escribir(
            Storage::disk('public')->path($csv),
            Storage::disk('public')->path($pdf),
            $xlsx ? Storage::disk('public')->path($xlsx) : null,
            $barra,
        );

        $barra->finish();
        $this->newLine(2);

        $lista = $this->publicar($csv, $pdf, $xlsx, $filas);

        $segundos = round(microtime(true) - $inicio, 1);
        $this->info("  {$filas} productos en {$segundos}s.");
        $this->info("  CSV para descargar: {$lista->tamano_legible}");
        if ($xlsx) {
            $this->info('  Excel para descargar: ' . $this->legible($lista->tamano_xlsx));
        }

        $this->info('  PDF para ver en pantalla: ' . $this->legible($lista->tamano_pdf) . " ({$paginas} páginas)");
        $this->info('  ' . ($lista->publicada ? 'Publicada' : 'Cargada sin publicar') . ' como «' . $lista->descripcion . '».');

        return self::SUCCESS;
    }

    /**
     * Escribe los archivos en la misma pasada, de a tandas: son decenas de
     * miles de filas y no entran en memoria.
     *
     * @return array{0:int, 1:int} filas y páginas del PDF
     */
    private function escribir(string $rutaCsv, string $rutaPdf, ?string $rutaXlsx, $barra): array
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
        ], self::SEPARADOR);

        $pdf = new PdfTabla($rutaPdf, 'Lista de precios · Rejovot Autopartes',
            'Actualizada el ' . now()->format('d/m/Y') . ' · Precios sin IVA', [
                // Suman 802: el ancho de la hoja apaisada menos los márgenes.
                ['titulo' => 'Código', 'ancho' => 88.0],
                ['titulo' => 'Descripción', 'ancho' => 384.0],
                ['titulo' => 'Marca', 'ancho' => 96.0],
                ['titulo' => 'Rubro', 'ancho' => 154.0],
                ['titulo' => 'Precio', 'ancho' => 80.0, 'alineacion' => 'derecha'],
            ]);

        $excel = $rutaXlsx ? new XlsxTabla($rutaXlsx, 'Lista de precios', [
            ['titulo' => 'Código', 'ancho' => 18, 'tipo' => 'texto'],
            ['titulo' => 'Descripción', 'ancho' => 70],
            ['titulo' => 'Marca', 'ancho' => 18],
            ['titulo' => 'Rubro', 'ancho' => 28],
            ['titulo' => 'Código OEM', 'ancho' => 24, 'tipo' => 'texto'],
            ['titulo' => 'Precio de lista (sin IVA)', 'ancho' => 24, 'tipo' => 'numero'],
        ]) : null;

        $filas = 0;

        Product::with(['brand', 'category'])
            ->publicables()
            ->orderBy('name')
            ->chunk(1000, function ($productos) use ($csv, $pdf, $excel, $barra, &$filas) {
                foreach ($productos as $producto) {
                    fwrite($csv, implode(self::SEPARADOR, [
                        $this->comoTexto($producto->code ?? ''),
                        $this->campo($producto->name),
                        $this->campo($producto->brand?->name ?? ''),
                        $this->campo($producto->category?->name ?? ''),
                        $this->comoTexto($producto->oem_codes ?? ''),
                        // Con coma decimal: es el separador que espera Excel acá.
                        number_format((float) $producto->list_price, 2, ',', ''),
                    ]) . "
");

                    $excel?->fila([
                        $producto->code ?? '',
                        $producto->name,
                        $producto->brand?->name ?? '',
                        $producto->category?->name ?? '',
                        $producto->oem_codes ?? '',
                        (float) $producto->list_price,
                    ]);

                    $pdf->fila([
                        $producto->code ?? '',
                        $producto->name,
                        $producto->brand?->name ?? '',
                        $producto->category?->name ?? '',
                        '$ ' . number_format((float) $producto->list_price, 2, ',', '.'),
                    ]);

                    $filas++;
                }

                $barra->advance($productos->count());
            });

        fclose($csv);
        $excel?->cerrar();

        return [$filas, $pdf->cerrar()];
    }

    /**
     * Excel en español toma «4321.0050» como un número con punto de miles y lo
     * muestra como 43.210.050. Escrito como fórmula de texto (="4321.0050") lo
     * deja tal cual. Va sin las comillas del CSV alrededor: así es como Excel
     * lo reconoce siempre. Lo que no se puede escribir así (más de 255
     * caracteres, el máximo de Excel en una fórmula, separadores o comillas)
     * va como un campo común: son listas de OEM, que no parecen números.
     */
    private function comoTexto(string $valor): string
    {
        if ($valor === '' || mb_strlen($valor) > 250 || preg_match('/[;"
]/', $valor)) {
            return $this->campo($valor);
        }

        return '="' . $valor . '"';
    }

    /** Un campo del CSV: entre comillas sólo si trae separador, comillas o saltos de línea. */
    private function campo(string $valor): string
    {
        return preg_match('/[;"
]/', $valor)
            ? '"' . str_replace('"', '""', $valor) . '"'
            : $valor;
    }

    private function legible(int $bytes): string
    {
        return $bytes < 1024 * 1024
            ? number_format($bytes / 1024, 0, ',', '.') . ' KB'
            : number_format($bytes / 1024 / 1024, 1, ',', '.') . ' MB';
    }

    /** Pisa la lista automática anterior, conservando su id y su link. */
    private function publicar(string $archivo, string $pdf, ?string $xlsx, int $filas): PriceList
    {
        $lista = PriceList::automatica()->first();
        $anteriores = array_filter([$lista?->archivo, $lista?->archivo_pdf, $lista?->archivo_xlsx]);

        $datos = [
            'descripcion' => 'Lista de precios completa',
            'formato' => 'csv',
            'origen' => PriceList::ORIGEN_ODOO,
            'archivo' => $archivo,
            'archivo_pdf' => $pdf,
            'archivo_xlsx' => $xlsx,
            'archivo_original' => 'lista-precios-rejovot-' . now()->format('Y-m-d') . '.csv',
            'tamano' => Storage::disk('public')->size($archivo),
            'tamano_pdf' => Storage::disk('public')->size($pdf),
            'tamano_xlsx' => $xlsx ? Storage::disk('public')->size($xlsx) : 0,
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
            if (! in_array($viejo, [$archivo, $pdf, $xlsx], true)) {
                Storage::disk('public')->delete($viejo);
            }
        }

        return $lista->refresh();
    }
}
