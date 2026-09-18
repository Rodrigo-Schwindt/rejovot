@extends('layouts.admin')

@section('content')
@php use App\Support\Precio; @endphp
<div class="animate-fadeIn space-y-6">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('admin.vendedores.index') }}" class="text-xs font-medium text-slate-400 hover:text-slate-600">← Vendedores</a>
            <h2 class="mt-1 text-xl font-semibold text-slate-800">{{ $vendedor->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $vendedor->login ? 'Usuario de Odoo: ' . $vendedor->login : 'Sin usuario de Odoo' }}
                · {{ $vendedor->active ? 'Activo' : 'Inactivo' }}
                · Último ingreso al sitio: {{ $acceso?->last_login_at?->format('d/m/Y H:i') ?? 'nunca' }}
            </p>
        </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <h3 class="text-base font-semibold text-slate-800">
            Cartera
            <span class="font-normal text-slate-400">({{ number_format($totalCartera, 0, ',', '.') }} clientes activos)</span>
        </h3>

        <form method="GET" action="{{ route('admin.vendedores.show', $vendedor) }}" class="flex gap-2">
            <input type="text" name="q" value="{{ $buscar }}" class="f-input" placeholder="Nombre o CUIT">
            <button type="submit" class="btn btn-primary">Buscar</button>
            @if($buscar !== '')
                <a href="{{ route('admin.vendedores.show', $vendedor) }}" class="btn btn-ghost">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-100 bg-white shadow-sm">
        <table class="w-full min-w-[760px] text-sm text-slate-700">
            <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase text-slate-400">
                <tr>
                    <th class="px-4 py-3 text-left">Cliente</th>
                    <th class="px-4 py-3 text-left">Contacto</th>
                    <th class="px-4 py-3 text-right">Descuento</th>
                    <th class="px-4 py-3 text-right">Deuda</th>
                    <th class="px-4 py-3 text-center" title="Si tiene usuario de portal en Odoo, que es con el que entra al sitio">Acceso al sitio</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($clientes as $cliente)
                    <tr class="transition hover:bg-slate-50/60">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.clientes.show', $cliente) }}" class="font-medium text-[#2563C9] hover:underline">{{ $cliente->name ?: '(sin nombre en Odoo)' }}</a>
                            <span class="block text-xs text-slate-400">{{ $cliente->vat ? 'CUIT ' . $cliente->vat : 'Sin CUIT' }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            {{ $cliente->email ?: '—' }}
                            <span class="block">{{ $cliente->phone ?: '' }}{{ $cliente->city ? ' · ' . $cliente->city : '' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ $cliente->price_discount > 0 ? rtrim(rtrim(number_format((float) $cliente->price_discount, 2, ',', '.'), '0'), ',') . '%' : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap {{ $cliente->credit_limit > 0 && $cliente->credit > $cliente->credit_limit ? 'font-semibold text-[#E11A22]' : '' }}">
                            {{ Precio::ar($cliente->credit) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded px-2 py-0.5 text-xs {{ $cliente->has_portal ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $cliente->has_portal ? 'Puede entrar' : 'Sin usuario' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                            {{ $buscar !== '' ? 'Ningún cliente de la cartera coincide con «' . $buscar . '»' : 'Este vendedor no tiene clientes activos asignados en Odoo' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $clientes->links() }}</div>
</div>
@endsection
