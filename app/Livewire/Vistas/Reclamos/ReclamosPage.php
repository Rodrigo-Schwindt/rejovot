<?php

namespace App\Livewire\Vistas\Reclamos;

use App\Models\Claim;
use App\Services\Sesion\ClienteActivo;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Reclamos del cliente activo. Cada cliente ve sólo los suyos. */
#[Layout('layouts.public')]
class ReclamosPage extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $numero = '';

    #[Url(except: '')]
    public string $articulo = '';

    #[Url(except: '')]
    public string $estado = '';

    public function updated($propiedad): void
    {
        if (in_array($propiedad, ['numero', 'articulo', 'estado'], true)) {
            $this->resetPage();
        }
    }

    public function render(ClienteActivo $clienteActivo)
    {
        $cliente = $clienteActivo->actual();

        return view('livewire.vistas.reclamos.reclamos-page', [
            'cliente' => $cliente,
            'reclamos' => $cliente ? $this->consulta($cliente->id)->paginate(15) : null,
            'estados' => Claim::ESTADOS,
            'motivo' => $cliente ? null : ($clienteActivo->puedeElegir()
                ? 'Elegí un cliente en Productos para ver sus reclamos.'
                : 'Ingresá con tu usuario para ver tus reclamos.'),
        ]);
    }

    private function consulta(int $customerId): Builder
    {
        $query = Claim::query()->where('customer_id', $customerId);

        if (trim($this->numero) !== '') {
            $query->where('id', Claim::idDesdeNumero($this->numero) ?? 0);
        }

        if (trim($this->articulo) !== '') {
            $texto = trim($this->articulo);
            $query->whereHas('items', fn (Builder $q) => $q->where('codigo', 'like', "%{$texto}%"));
        }

        if (array_key_exists($this->estado, Claim::ESTADOS)) {
            $query->where('estado', $this->estado);
        }

        return $query->latest('id');
    }
}
