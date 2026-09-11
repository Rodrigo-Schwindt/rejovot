<?php

namespace App\Livewire\Vistas\Vehiculos;

use App\Contracts\CatalogoRepository;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Navegación del catálogo en dos niveles: marca → productos de esa marca.
 *
 * Odoo no tiene datos de vehículos (marca, modelo, versión ni año), así que el
 * árbol se arma con las marcas de producto. La marca viaja en la URL para que
 * anden el back del navegador y compartir el link.
 */
#[Layout('layouts.public')]
class BusquedaVehiculoPage extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $marca = '';

    /** Filtra la lista de marcas: son cien y así no hay que recorrerlas a ojo. */
    public string $buscarMarca = '';

    /** Busca dentro de la marca elegida: código, descripción y código OEM. */
    #[Url(except: '')]
    public string $q = '';

    public int $porPagina = 50;

    public function elegirMarca(string $marca): void
    {
        $this->marca = $marca;
        $this->buscarMarca = '';
        $this->q = '';
        $this->resetPage();
    }

    public function volver(): void
    {
        $this->marca = '';
        $this->q = '';
        $this->resetPage();
    }

    public function updatedBuscarMarca(): void
    {
        $this->resetPage();
    }

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function render(CatalogoRepository $catalogo)
    {
        $marcas = $catalogo->filtros()['marcas'];

        // Una marca que no existe vuelve al primer nivel.
        if ($this->marca !== '' && ! in_array($this->marca, $marcas, true)) {
            $this->marca = '';
        }

        if ($this->marca === '' && trim($this->buscarMarca) !== '') {
            $texto = mb_strtolower(trim($this->buscarMarca));
            $marcas = array_values(array_filter(
                $marcas,
                fn (string $m) => str_contains(mb_strtolower($m), $texto),
            ));
        }

        return view('livewire.vistas.vehiculos.busqueda-vehiculo-page', [
            'marcas' => $marcas,
            'productos' => $this->marca !== ''
                ? $catalogo->paginados([
                    'marca' => $this->marca,
                    'q' => trim($this->q),
                ], $this->porPagina)
                : null,
        ]);
    }
}
