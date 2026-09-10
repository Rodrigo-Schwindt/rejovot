<?php

namespace App\Livewire\Vistas\Margenes;

use App\Contracts\CatalogoRepository;
use App\Services\Margenes\Margenes;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class MargenesPage extends Component
{
    public float $general = Margenes::POR_DEFECTO;

    /** Márgenes por marca y por familia, indexados por clave. */
    public array $marcas = [];
    public array $familias = [];

    public function mount(CatalogoRepository $catalogo, Margenes $margenes): void
    {
        $opciones = $catalogo->filtros();

        $this->general = $margenes->general();

        foreach ($opciones['marcas'] as $marca) {
            $clave = Margenes::clave($marca);
            $this->marcas[$clave] = $margenes->marcas()[$clave] ?? $this->general;
        }

        foreach ($opciones['rubros'] as $rubro) {
            $clave = Margenes::clave($rubro);
            $this->familias[$clave] = $margenes->familias()[$clave] ?? $this->general;
        }
    }

    public function updatedGeneral($value, Margenes $margenes): void
    {
        $margenes->guardarGeneral((float) $value);
        $this->general = $margenes->general();

        $this->avisar();
    }

    public function updatedMarcas($value, $key): void
    {
        $margenes = app(Margenes::class);
        $margenes->guardarMarca($key, (float) $value);
        $this->marcas[$key] = $margenes->marcas()[$key];

        $this->avisar();
    }

    public function updatedFamilias($value, $key): void
    {
        $margenes = app(Margenes::class);
        $margenes->guardarFamilia($key, (float) $value);
        $this->familias[$key] = $margenes->familias()[$key];

        $this->avisar();
    }

    public function render(CatalogoRepository $catalogo)
    {
        $opciones = $catalogo->filtros();

        return view('livewire.vistas.margenes.margenes-page', [
            'listaMarcas' => $opciones['marcas'],
            'listaFamilias' => $opciones['rubros'],
        ]);
    }

    private function avisar(): void
    {
        $this->dispatch('show-toast', message: 'Márgenes actualizados.', type: 'success');
    }
}
