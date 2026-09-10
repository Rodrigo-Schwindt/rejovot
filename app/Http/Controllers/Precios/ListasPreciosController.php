<?php

namespace App\Http\Controllers\Precios;

use App\Http\Controllers\Controller;
use App\Models\PriceList;
use Illuminate\Http\Request;
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

    /** Abre el PDF en el navegador. */
    public function show(PriceList $lista)
    {
        abort_unless($lista->publicada && $lista->es_pdf, 404);
        abort_unless(Storage::disk('public')->exists($lista->archivo), 404);

        return response()->file(Storage::disk('public')->path($lista->archivo));
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

    public function destroy(PriceList $lista)
    {
        if ($lista->archivo) {
            Storage::disk('public')->delete($lista->archivo);
        }

        $lista->delete();

        return back()->with('success', 'Lista eliminada.');
    }
}
