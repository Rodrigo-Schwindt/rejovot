<?php

namespace App\Http\Controllers\Sliders;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Banners administrables del carrusel de Productos. */
class SlidersController extends Controller
{
    private const CARPETA = 'sliders';

    private const COLOR = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];

    public function index()
    {
        return view('livewire.sliders.index', [
            'sliders' => Slider::ordenados()->get(),
        ]);
    }

    public function create()
    {
        return view('livewire.sliders.create', [
            'slider' => new Slider([
                'etiqueta_color' => '#FFFFFF',
                'etiqueta_fondo' => '#E11A22',
                'titulo_color' => '#FFFFFF',
                'descripcion_color' => '#FFFFFF',
                'fondo' => '#002B56',
                'sort_order' => (int) Slider::max('sort_order') + 1,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        Slider::create($datos + [
            'imagenes' => $this->guardarImagenes($request, []),
            'activo' => true,
        ]);

        return redirect()->route('admin.sliders.index')->with('success', 'Slider creado.');
    }

    public function edit(Slider $slider)
    {
        return view('livewire.sliders.edit', ['slider' => $slider]);
    }

    public function update(Request $request, Slider $slider)
    {
        $datos = $this->validar($request);

        $slider->update($datos + [
            'imagenes' => $this->guardarImagenes($request, $slider->imagenes ?? []),
        ]);

        return redirect()->route('admin.sliders.index')->with('success', 'Slider actualizado.');
    }

    public function toggle(Slider $slider)
    {
        $slider->update(['activo' => ! $slider->activo]);

        return back()->with('success', $slider->activo
            ? 'El slider se muestra en el sitio.'
            : 'El slider quedó oculto en el sitio.');
    }

    public function destroy(Slider $slider)
    {
        Storage::disk('public')->delete($slider->imagenes ?? []);

        $slider->delete();

        return back()->with('success', 'Slider eliminado.');
    }

    /** Los campos del slider: las imágenes se guardan aparte (guardarImagenes). */
    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'etiqueta' => ['nullable', 'string', 'max:40'],
            'etiqueta_color' => self::COLOR,
            'etiqueta_fondo' => self::COLOR,
            'titulo' => ['required', 'string', 'max:120'],
            'titulo_color' => self::COLOR,
            'descripcion' => ['nullable', 'string', 'max:400'],
            'descripcion_color' => self::COLOR,
            'fondo' => self::COLOR,
            'sort_order' => ['required', 'integer', 'min:0'],
            'imagenes' => ['array', 'max:' . Slider::MAXIMO_IMAGENES],
            'imagenes.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'titulo.required' => 'Poné un título.',
            'imagenes.*.image' => 'Las imágenes tienen que ser JPG, PNG o WEBP.',
            'imagenes.*.mimes' => 'Las imágenes tienen que ser JPG, PNG o WEBP.',
            'imagenes.*.max' => 'Cada imagen puede pesar hasta 4 MB.',
            'imagenes.*.uploaded' => 'Una imagen no se pudo subir: probá con una más liviana.',
        ]);

        unset($datos['imagenes']);

        return $datos;
    }

    /**
     * Cada lugar del formulario conserva su imagen, la reemplaza o la quita.
     *
     * @param  array<int, string>  $actuales
     * @return array<int, string>
     */
    private function guardarImagenes(Request $request, array $actuales): array
    {
        $imagenes = [];

        for ($i = 0; $i < Slider::MAXIMO_IMAGENES; $i++) {
            $actual = $actuales[$i] ?? null;
            $nueva = $request->file("imagenes.{$i}");

            if ($actual && ($nueva || $request->boolean("quitar.{$i}"))) {
                Storage::disk('public')->delete($actual);
                $actual = null;
            }

            $imagenes[] = $nueva ? $nueva->store(self::CARPETA, 'public') : $actual;
        }

        return array_values(array_filter($imagenes));
    }
}
