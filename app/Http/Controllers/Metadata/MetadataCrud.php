<?php

namespace App\Http\Controllers\Metadata;

use App\Http\Controllers\Controller;
use App\Models\Metadata;
use Illuminate\Http\Request;

class MetadataCrud extends Controller
{
    public function index()
    {
        return view('livewire.metadata.crud', [
            'items' => Metadata::orderBy('section')->get(),
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'section'     => ['required', 'string', 'max:120'],
            'keywords'    => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'section.required' => 'Elegí la sección.',
        ]);

        Metadata::updateOrCreate(
            ['section' => $data['section']],
            ['keywords' => $data['keywords'] ?? null, 'description' => $data['description'] ?? null],
        );

        return back()->with('success', 'Metadata guardada correctamente.');
    }

    public function delete(Metadata $metadata)
    {
        $section = $metadata->section;
        $metadata->delete();

        return back()->with('success', "Se eliminó la metadata de «{$section}».");
    }
}
