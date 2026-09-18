@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn space-y-6">

    <div>
        <h2 class="text-xl font-semibold text-slate-800">
            Vendedores
            <span class="text-base font-normal text-slate-400">({{ $vendedores->count() }})</span>
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Vienen de Odoo con su cartera de clientes. Un vendedor sólo puede comprar para clientes de su cartera.
            @if($sinVendedor > 0)
                Hay <a href="{{ route('admin.clientes.index', ['vendedor' => -1]) }}" class="font-semibold text-[#0D2B5E] underline">{{ number_format($sinVendedor, 0, ',', '.') }} clientes activos sin vendedor</a>.
            @endif
        </p>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-100 bg-white shadow-sm">
        <table class="w-full min-w-[720px] text-sm text-slate-700">
            <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase text-slate-400">
                <tr>
                    <th class="px-4 py-3 text-left">Vendedor</th>
                    <th class="px-4 py-3 text-left">Usuario en Odoo</th>
                    <th class="px-4 py-3 text-right">Clientes a cargo</th>
                    <th class="px-4 py-3 text-left">Último ingreso al sitio</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($vendedores as $vendedor)
                    @php $acceso = $accesos[$vendedor->id] ?? null; @endphp
                    <tr class="transition hover:bg-slate-50/60">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.vendedores.show', $vendedor) }}" class="font-medium text-[#2563C9] hover:underline">{{ $vendedor->name }}</a>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $vendedor->login ?: '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($vendedor->clientes_count > 0)
                                <span class="font-semibold text-slate-800">{{ number_format($vendedor->clientes_count, 0, ',', '.') }}</span>
                            @else
                                <span class="text-xs text-slate-400">Ninguno</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            {{ $acceso?->last_login_at?->format('d/m/Y H:i') ?? 'Nunca entró' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded px-2 py-0.5 text-xs {{ $vendedor->active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $vendedor->active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
