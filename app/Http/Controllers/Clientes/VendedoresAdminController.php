<?php

namespace App\Http\Controllers\Clientes;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Salesperson;
use App\Models\User;
use Illuminate\Http\Request;

/** Vendedores de Odoo (sólo lectura) y la cartera de clientes de cada uno. */
class VendedoresAdminController extends Controller
{
    public function index()
    {
        $accesos = User::delSitio()->where('role', User::VENDEDOR)
            ->whereNotNull('salesperson_id')
            ->get()
            ->keyBy('salesperson_id');

        return view('livewire.vendedores.index', [
            'vendedores' => Salesperson::withCount([
                'customers as clientes_count' => fn ($q) => $q->where('active', true),
            ])->orderByDesc('active')->orderBy('name')->get(),
            'accesos' => $accesos,
            'sinVendedor' => Customer::where('active', true)->whereNull('salesperson_id')->count(),
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
