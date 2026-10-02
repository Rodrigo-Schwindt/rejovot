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
 *
 * Sólo los que tienen asignado uno de los vendedores de Rejovot (GERENCIA y
 * «VENDEDOR n», ver Salesperson): la base de Odoo trae decenas de miles de
 * contactos más que no son clientes a atender.
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

        $query = $this->clientes()->with('salesperson');

        if ($filtros['q'] !== '') {
            $query->buscar($filtros['q']);
        }

        if ($filtros['vendedor'] > 0) {
            $query->where('salesperson_id', $filtros['vendedor']);
        }

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
            'vendedores' => Salesperson::vendedores()->orderBy('name')->get(),
            'totales' => [
                'activos' => $this->clientes()->where('active', true)->count(),
                'inactivos' => $this->clientes()->where('active', false)->count(),
                'con_web' => $this->clientes()->where('active', true)->where('has_portal', true)->count(),
                'entraron' => $this->clientes()
                    ->whereIn('id', User::where('role', User::CLIENTE)->whereNotNull('customer_id')->select('customer_id'))
                    ->count(),
            ],
        ]);
    }

    /** Clientes de los vendedores de Rejovot. */
    private function clientes(): Builder
    {
        return Customer::query()->whereIn('salesperson_id', Salesperson::vendedores()->select('id'));
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
