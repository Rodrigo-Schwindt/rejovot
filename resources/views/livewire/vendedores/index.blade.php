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
        </p>
    </div>

    {{-- Resumen --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @php
            $tarjetas = [
                ['Activos', $totales['activos'], 'estado=activos'],
                ['Con cartera asignada', $totales['con_cartera'], 'cartera=con'],
                ['Ya entraron al sitio', $totales['entraron'], 'acceso=entraron'],
            ];
        @endphp
        @foreach($tarjetas as [$titulo, $valor, $filtro])
            <a href="{{ route('admin.vendedores.index') . '?' . $filtro }}"
               class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm transition hover:border-slate-300">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $titulo }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-800">{{ number_format($valor, 0, ',', '.') }}</p>
            </a>
        @endforeach
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('admin.vendedores.index') }}" class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="f-label" for="q">Buscar</label>
                <input type="text" id="q" name="q" value="{{ $filtros['q'] }}" class="f-input" placeholder="Nombre o usuario de Odoo">
            </div>

            <div>
                <label class="f-label" for="estado">Estado en Odoo</label>
                <select id="estado" name="estado" class="f-input">
                    <option value="activos" @selected($filtros['estado'] === 'activos')>Activos</option>
                    <option value="inactivos" @selected($filtros['estado'] === 'inactivos')>Inactivos</option>
                    <option value="todos" @selected($filtros['estado'] === 'todos')>Todos</option>
                </select>
            </div>

            <div>
                <label class="f-label" for="cartera">Cartera</label>
                <select id="cartera" name="cartera" class="f-input">
                    <option value="">Todos</option>
                    <option value="con" @selected($filtros['cartera'] === 'con')>Con clientes a cargo</option>
                    <option value="sin" @selected($filtros['cartera'] === 'sin')>Sin clientes a cargo</option>
                </select>
            </div>

            <div>
                <label class="f-label" for="acceso">Acceso al sitio</label>
                <select id="acceso" name="acceso" class="f-input">
                    <option value="">Todos</option>
                    <option value="entraron" @selected($filtros['acceso'] === 'entraron')>Ya entraron</option>
                    <option value="no_entraron" @selected($filtros['acceso'] === 'no_entraron')>Nunca entraron</option>
                </select>
            </div>

            <div class="flex items-end gap-2 lg:col-span-4">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('admin.vendedores.index') }}" class="btn btn-ghost">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-slate-100 bg-white shadow-sm">
        <table class="admin-mobile-table w-full min-w-[720px] text-sm text-slate-700">
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
                @forelse($vendedores as $vendedor)
                    @php $acceso = $accesos[$vendedor->id] ?? null; @endphp
                    <tr class="transition hover:bg-slate-50/60">
                        <td data-label="Vendedor" class="px-4 py-3">
                            <a href="{{ route('admin.vendedores.show', $vendedor) }}" class="font-medium text-[#2563C9] hover:underline">{{ $vendedor->name }}</a>
                        </td>
                        <td data-label="Usuario en Odoo" class="px-4 py-3 text-xs text-slate-500">{{ $vendedor->login ?: '—' }}</td>
                        <td data-label="Clientes a cargo" class="px-4 py-3 text-right">
                            @if($vendedor->clientes_count > 0)
                                <span class="font-semibold text-slate-800">{{ number_format($vendedor->clientes_count, 0, ',', '.') }}</span>
                            @else
                                <span class="text-xs text-slate-400">Ninguno</span>
                            @endif
                        </td>
                        <td data-label="Último ingreso" class="px-4 py-3 text-xs text-slate-500">
                            {{ $acceso?->last_login_at?->format('d/m/Y H:i') ?? 'Nunca entró' }}
                        </td>
                        <td data-label="Estado" class="px-4 py-3 text-center">
                            <span class="inline-flex rounded px-2 py-0.5 text-xs {{ $vendedor->active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $vendedor->active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">Ningún vendedor coincide con esos filtros</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
