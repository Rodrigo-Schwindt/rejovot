<?php

namespace App\Http\Controllers\Clientes;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Salesperson;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Clientes, tal cual vienen de Odoo (sólo lectura). Acá se consulta quién es
 * cada uno, qué vendedor tiene asignado y si ya entró al sitio.
 */
class ClientesAdminController extends Controller
{
    public function index(Request $request)
    {
        $filtros = [
            'q' => trim((string) $request->get('q')),
            'vendedor' => (int) $request->get('vendedor'),
            'estado' => trim((string) $request->get('estado')) ?: 'activos',
            'acceso' => trim((string) $request->get('acceso')),
        ];

        $query = Customer::query()->with('salesperson');

        if ($filtros['q'] !== '') {
            $query->buscar($filtros['q']);
        }

        match ($filtros['vendedor']) {
            0 => null,
            -1 => $query->whereNull('salesperson_id'),
            default => $query->where('salesperson_id', $filtros['vendedor']),
        };

        match ($filtros['estado']) {
            'activos' => $query->where('active', true),
            'inactivos' => $query->where('active', false),
            default => null,
        };

        // Acceso al sitio: si Odoo le dio usuario de portal y si ya lo usó.
        match ($filtros['acceso']) {
            'puede_entrar' => $query->where('has_portal', true),
            'sin_usuario' => $query->where('has_portal', false),
            'entraron' => $query->whereIn('id', User::where('role', User::CLIENTE)->whereNotNull('customer_id')->select('customer_id')),
            'no_entraron' => $query->where('has_portal', true)
                ->whereNotIn('id', User::where('role', User::CLIENTE)->whereNotNull('customer_id')->select('customer_id')),
            default => null,
        };

        return view('livewire.clientes.index', [
            // Los pocos sin nombre (contactos vacíos de Odoo) van al final.
            'clientes' => $query->orderByRaw("(name IS NULL OR name = '') ASC")->orderBy('name')->paginate(25)->withQueryString(),
            'filtros' => $filtros,
            'vendedores' => Salesperson::orderBy('name')->get(),
            'totales' => [
                'activos' => Customer::where('active', true)->count(),
                'con_vendedor' => Customer::where('active', true)->whereNotNull('salesperson_id')->count(),
                'con_web' => Customer::where('active', true)->where('has_portal', true)->count(),
                'entraron' => User::delSitio()->where('role', User::CLIENTE)->count(),
            ],
        ]);
    }

    public function show(Customer $cliente)
    {
        return view('livewire.clientes.show', [
            'cliente' => $cliente->load('salesperson'),
            // La cuenta con la que entra al sitio, si ya entró alguna vez.
            'acceso' => User::where('customer_id', $cliente->id)->first(),
        ]);
    }
}
