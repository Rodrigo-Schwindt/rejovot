<?php

namespace App\Http\Controllers\Clientes;

use App\Http\Controllers\Controller;
use App\Models\Salesperson;
use App\Models\User;
use Illuminate\Http\Request;

/** Vendedores de Odoo (sólo lectura) y la cartera de clientes de cada uno. */
class VendedoresAdminController extends Controller
{
    public function index(Request $request)
    {
        $filtros = [
            'q' => trim((string) $request->get('q')),
            'estado' => trim((string) $request->get('estado')) ?: 'activos',
            'cartera' => trim((string) $request->get('cartera')),
            'acceso' => trim((string) $request->get('acceso')),
        ];

        $accesos = User::delSitio()->where('role', User::VENDEDOR)
            ->whereNotNull('salesperson_id')
            ->get()
            ->keyBy('salesperson_id');

        $query = Salesperson::vendedores()->withCount([
            'customers as clientes_count' => fn ($q) => $q->where('active', true),
        ]);

        if ($filtros['q'] !== '') {
            $texto = $filtros['q'];
            $query->where(fn ($q) => $q->where('name', 'like', "%{$texto}%")->orWhere('login', 'like', "%{$texto}%"));
        }

        match ($filtros['estado']) {
            'activos' => $query->where('active', true),
            'inactivos' => $query->where('active', false),
            default => null,
        };

        match ($filtros['cartera']) {
            'con' => $query->having('clientes_count', '>', 0),
            'sin' => $query->having('clientes_count', '=', 0),
            default => null,
        };

        // Quiénes ya usaron el sitio: los que tienen un usuario con ingreso.
        $conAcceso = $accesos->keys()->all();

        match ($filtros['acceso']) {
            'entraron' => $query->whereIn('id', $conAcceso ?: [0]),
            'no_entraron' => $query->whereNotIn('id', $conAcceso ?: [0]),
            default => null,
        };

        return view('livewire.vendedores.index', [
            'vendedores' => $query->orderByDesc('active')->orderBy('name')->get(),
            'accesos' => $accesos,
            'filtros' => $filtros,
            'totales' => [
                'activos' => Salesperson::vendedores()->where('active', true)->count(),
                'con_cartera' => Salesperson::vendedores()->whereHas('customers', fn ($q) => $q->where('active', true))->count(),
                'entraron' => $accesos->count(),
            ],
        ]);
    }

    public function show(Request $request, Salesperson $vendedor)
    {
        $buscar = trim((string) $request->get('q'));

        $clientes = $vendedor->customers()
            ->where('active', true)
            ->when($buscar !== '', fn ($q) => $q->buscar($buscar))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('livewire.vendedores.show', [
            'vendedor' => $vendedor,
            'clientes' => $clientes,
            'buscar' => $buscar,
            'totalCartera' => $vendedor->customers()->where('active', true)->count(),
            'acceso' => User::where('salesperson_id', $vendedor->id)->first(),
        ]);
    }
}
