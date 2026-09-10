<?php

namespace App\Http\Controllers\Catalogo;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Listado del catálogo para el admin.
 *
 * El catálogo se sincroniza desde Odoo: acá no se editan productos, se decide
 * qué se muestra en el sitio (ocultar) y qué va al banner (destacar).
 */
class ProductosAdminController extends Controller
{
    public function index(Request $request)
    {
        // Ojo: `ConvertEmptyStringsToNull` transforma los campos vacíos del
        // formulario en null, así que hay que normalizarlos a string.
        $texto = fn (string $campo) => trim((string) $request->get($campo));

        $filtros = [
            'q' => $texto('q'),
            'marca' => $texto('marca'),
            'rubro' => $texto('rubro'),
            // Por defecto sólo lo que se ve en el sitio; ver todo es opcional.
            'estado' => $texto('estado') ?: 'publicados',
            'stock' => $texto('stock'),
            'oferta' => $request->boolean('oferta'),
            'destacado' => $request->boolean('destacado'),
        ];

        $productos = $this->consulta($filtros)
            ->with(['brand', 'category'])
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('livewire.catalogo.index', [
            'productos' => $productos,
            'filtros' => $filtros,
            'marcas' => Brand::orderBy('name')->pluck('name'),
            'rubros' => Category::whereHas('products')->orderBy('name')->pluck('name'),
            'totales' => [
                'total' => Product::count(),
                'publicados' => Product::publicables()->count(),
                'ocultos' => Product::where('oculto', true)->count(),
                'destacados' => Product::where('destacado', true)->count(),
                'ofertas' => Product::publicables()->enOferta()->count(),
            ],
        ]);
    }

    public function ocultar(Product $producto)
    {
        $producto->update(['oculto' => ! $producto->oculto]);

        return back()->with('success', $producto->oculto
            ? "«{$producto->code}» ya no se muestra en el sitio."
            : "«{$producto->code}» vuelve a mostrarse en el sitio.");
    }

    public function destacar(Product $producto)
    {
        $producto->update(['destacado' => ! $producto->destacado]);

        return back()->with('success', $producto->destacado
            ? "«{$producto->code}» se agregó al banner."
            : "«{$producto->code}» se quitó del banner.");
    }

    /** Quita todos los destacados de una sola vez. */
    public function limpiarDestacados()
    {
        $cuantos = Product::where('destacado', true)->count();
        Product::where('destacado', true)->update(['destacado' => false]);

        return back()->with('success', "Se quitaron {$cuantos} productos del banner.");
    }

    protected function consulta(array $filtros): Builder
    {
        $query = Product::query();

        if ($filtros['q'] !== '') {
            $texto = $filtros['q'];
            $query->where(function (Builder $q) use ($texto) {
                $q->where('name', 'like', "%{$texto}%")
                    ->orWhere('code', 'like', "%{$texto}%")
                    ->orWhere('oem_codes', 'like', "%{$texto}%");
            });
        }

        if ($filtros['marca'] !== '') {
            $query->whereHas('brand', fn (Builder $q) => $q->where('name', $filtros['marca']));
        }

        if ($filtros['rubro'] !== '') {
            $query->whereHas('category', fn (Builder $q) => $q->where('name', $filtros['rubro']));
        }

        match ($filtros['estado']) {
            'publicados' => $query->publicables(),
            'ocultos' => $query->where('oculto', true),
            'sin_web' => $query->where('active', true)->where('published', false),
            'archivados' => $query->where('active', false),
            default => null,   // 'todos': el catálogo completo, incluso lo archivado
        };

        match ($filtros['stock']) {
            'con' => $query->where('stock', '>', 0),
            'sin' => $query->where('stock', '<=', 0),
            default => null,
        };

        if ($filtros['oferta']) {
            $query->enOferta();
        }

        if ($filtros['destacado']) {
            $query->where('destacado', true);
        }

        return $query;
    }
}
