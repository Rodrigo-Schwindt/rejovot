<?php

namespace App\Http\Controllers\Metadata;

use App\Http\Controllers\Controller;
use App\Models\Metadata;
use App\Models\Product;
use App\Support\ProductoSeo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Metadata SEO de cada sección del sitio. Los productos tienen la suya,
 * automática; se edita uno por uno desde Productos (ProductosAdminController).
 */
class MetadataCrud extends Controller
{
    public function index()
    {
        // Un producto de muestra para que se vea cómo queda la automática.
        $ejemplo = Product::with(['brand', 'category'])->publicables()->whereNotNull('oem_codes')->inRandomOrder()->first();

        return view('livewire.metadata.crud', [
            'secciones' => Metadata::SECTIONS,
            'cargadas' => Metadata::all()->keyBy('section'),
            'ejemplo' => $ejemplo,
            'ejemploSeo' => $ejemplo ? ProductoSeo::para($ejemplo) : null,
            'productosManuales' => Product::where(fn ($q) => $q->whereNotNull('seo_title')
                ->orWhereNotNull('seo_description')
                ->orWhereNotNull('seo_keywords'))->count(),
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'section' => ['required', Rule::in(array_keys(Metadata::SECTIONS))],
            'title' => ['nullable', 'string', 'max:120'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'section.in' => 'Esa sección no existe.',
        ]);

        $valores = [
            'title' => trim($data['title'] ?? '') ?: null,
            'keywords' => trim($data['keywords'] ?? '') ?: null,
            'description' => trim($data['description'] ?? '') ?: null,
        ];

        $nombre = Metadata::SECTIONS[$data['section']]['nombre'];

        // Todo vacío: vuelve al texto por defecto de la sección.
        if (! array_filter($valores)) {
            Metadata::where('section', $data['section'])->delete();

            return back()->with('success', "«{$nombre}» vuelve a usar el texto por defecto.");
        }

        Metadata::updateOrCreate(['section' => $data['section']], $valores);

        return back()->with('success', "Metadata de «{$nombre}» guardada.");
    }

    public function delete(Metadata $metadata)
    {
        $metadata->delete();

        return back()->with('success', 'Se volvió al texto por defecto.');
    }
}
