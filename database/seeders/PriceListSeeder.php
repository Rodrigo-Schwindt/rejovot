<?php

namespace Database\Seeders;

use App\Models\PriceList;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Deja dos listas de ejemplo (PDF y Excel) para que la pantalla se vea cargada.
 * En producción las sube el cliente desde el admin.
 */
class PriceListSeeder extends Seeder
{
    public function run(): void
    {
        if (PriceList::exists()) {
            return;
        }

        $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            . "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            . "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]>>endobj\n"
            . "trailer<</Root 1 0 R>>\n%%EOF\n";

        // Excel abre sin problemas una tabla HTML guardada como .xls.
        $xls = "<table><tr><th>Código</th><th>Producto</th><th>Precio de lista</th></tr>"
            . "<tr><td>BS009.0868</td><td>VMG BOMBA AGUA ELECTRICA AUXILIAR MERCEDES BENZ SPRINTER</td><td>76877,74</td></tr>"
            . "<tr><td>PO507.0868</td><td>VMG POLEA VISCOSA BLAZER 4.3 V6 C/ROSCA</td><td>85951,76</td></tr>"
            . "</table>";

        $archivos = [
            ['nombre' => 'lista-precios-agosto-2026.pdf', 'contenido' => $pdf, 'formato' => 'pdf'],
            ['nombre' => 'lista-precios-agosto-2026.xls', 'contenido' => $xls, 'formato' => 'excel'],
        ];

        foreach ($archivos as $orden => $archivo) {
            $ruta = 'listas-precios/' . $archivo['nombre'];
            Storage::disk('public')->put($ruta, $archivo['contenido']);

            PriceList::create([
                'descripcion' => 'Lista de precios - Agosto 2026',
                'formato' => $archivo['formato'],
                'archivo' => $ruta,
                'archivo_original' => $archivo['nombre'],
                'tamano' => Storage::disk('public')->size($ruta),
                'vigencia' => 'Agosto 2026',
                'notas' => 'Precios sin IVA, sujetos a modificación sin previo aviso.',
                'publicada' => true,
                'sort_order' => $orden,
            ]);
        }
    }
}
