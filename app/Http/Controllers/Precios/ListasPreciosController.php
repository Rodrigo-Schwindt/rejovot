<?php

namespace App\Http\Controllers\Precios;

use App\Http\Controllers\Controller;
use App\Models\PriceList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListasPreciosController extends Controller
{
    /* ---------------- Público ---------------- */

    /** Descarga la lista con su nombre original. */
    public function download(PriceList $lista)
    {
        abort_unless($lista->publicada, 404);
        abort_unless(Storage::disk('public')->exists($lista->archivo), 404);

        return Storage::disk('public')->download(
            $lista->archivo,
            $lista->archivo_original ?: basename($lista->archivo),
        );
    }

    /**
     * Abre la lista en el navegador. Si es un PDF subido se muestra ese; si es
     * la lista generada, su versión PDF (lo que se descarga sigue siendo el CSV).
     */
    public function show(PriceList $lista)
    {
        abort_unless($lista->publicada, 404);

        $archivo = $lista->archivo_para_ver;

        abort_unless($archivo && Storage::disk('public')->exists($archivo), 404);

        return response()->file(Storage::disk('public')->path($archivo), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $lista->descripcion . '.pdf"',
        ]);
    }

    /* ---------------- Admin ---------------- */

    public function index()
    {
        return view('livewire.precios.index', [
            'listas' => PriceList::orderBy('sort_order')->orderByDesc('id')->paginate(15),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'descripcion' => ['required', 'string', 'max:255'],
            'vigencia' => ['nullable', 'string', 'max:120'],
            'notas' => ['nullable', 'string', 'max:1000'],
            // El CSV llega como text/plain, por eso va también 'txt'.
            'archivo' => ['required', 'file', 'mimes:pdf,xls,xlsx,csv,txt', 'max:20480'],
        ], [
            'descripcion.required' => 'Poné una descripción.',
            'archivo.required' => 'Subí el archivo de la lista.',
            'archivo.mimes' => 'El archivo tiene que ser PDF, XLS, XLSX o CSV.',
            'archivo.max' => 'El archivo no puede superar los 20 MB.',
        ]);

        $archivo = $request->file('archivo');
        // storeAs conserva la extensión real: store() la adivina del mime (.csv -> .txt).
        $nombre = Str::random(40) . '.' . strtolower($archivo->getClientOriginalExtension());

        PriceList::create([
            'descripcion' => $datos['descripcion'],
            'formato' => PriceList::formatoDesdeExtension($archivo->getClientOriginalExtension()),
            'archivo' => $archivo->storeAs('listas-precios', $nombre, 'public'),
            'archivo_original' => $archivo->getClientOriginalName(),
            'tamano' => $archivo->getSize(),
            'vigencia' => $datos['vigencia'] ?? null,
            'notas' => $datos['notas'] ?? null,
            'publicada' => true,
            'sort_order' => (int) PriceList::max('sort_order') + 1,
        ]);

        return back()->with('success', 'Lista de precios publicada.');
    }

    public function toggle(PriceList $lista)
    {
        $lista->update(['publicada' => ! $lista->publicada]);

        return back()->with('success', $lista->publicada
            ? 'La lista se muestra en el sitio.'
            : 'La lista quedó oculta en el sitio.');
    }

    /** Rehace la lista automática con el catálogo de este momento. */
    public function regenerar()
    {
        // Son decenas de miles de filas: no entra en el tope de 30 segundos.
        set_time_limit(300);

        Artisan::call('precios:generar');

        $lista = PriceList::automatica()->first();

        return back()->with('success', $lista
            ? "Lista actualizada: {$lista->notas}"
            : 'No hay productos publicados para armar la lista.');
    }

    public function destroy(PriceList $lista)
    {
        if ($lista->archivo) {
            Storage::disk('public')->delete($lista->archivo);
        }

        $lista->delete();

        return back()->with('success', 'Lista eliminada.');
    }
}
