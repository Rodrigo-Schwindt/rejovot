@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn space-y-6">

    <div>
        <h2 class="text-xl font-semibold text-slate-800">Clientes</h2>
        <p class="mt-1 text-sm text-slate-500">
            Vienen de Odoo y se actualizan solos cada hora. Acá se consultan; los cambios se hacen en Odoo.
        </p>
    </div>

    {{-- Resumen --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @php
            $tarjetas = [
                ['Activos', $totales['activos'], 'estado=activos'],
                ['Con vendedor asignado', $totales['con_vendedor'], 'estado=activos&vendedor=0'],
                ['Pueden entrar al sitio', $totales['con_web'], 'acceso=puede_entrar'],
                ['Ya entraron al sitio', $totales['entraron'], 'acceso=entraron'],
            ];
        @endphp
        @foreach($tarjetas as [$titulo, $valor, $filtro])
            <a href="{{ route('admin.clientes.index') . ($filtro ? '?' . $filtro : '') }}"
               class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm transition hover:border-slate-300">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $titulo }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-800">{{ number_format($valor, 0, ',', '.') }}</p>
            </a>
        @endforeach
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('admin.clientes.index') }}" class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label class="f-label" for="q">Buscar</label>
                <input type="text" id="q" name="q" value="{{ $filtros['q'] }}" class="f-input" placeholder="Nombre o CUIT">
            </div>

            <div>
                <label class="f-label" for="vendedor">Vendedor</label>
                <select id="vendedor" name="vendedor" class="f-input">
                    <option value="0">Todos</option>
                    <option value="-1" @selected($filtros['vendedor'] === -1)>Sin vendedor</option>
                    @foreach($vendedores as $v)
                        <option value="{{ $v->id }}" @selected($filtros['vendedor'] === $v->id)>{{ $v->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="f-label" for="estado">Estado en Odoo</label>
                <select id="estado" name="estado" class="f-input">
                    <option value="activos" @selected($filtros['estado'] === 'activos')>Activos</option>
                    <option value="inactivos" @selected($filtros['estado'] === 'inactivos')>Archivados</option>
                    <option value="todos" @selected($filtros['estado'] === 'todos')>Todos</option>
                </select>
            </div>

            <div>
                <label class="f-label" for="acceso">Acceso al sitio</label>
                <select id="acceso" name="acceso" class="f-input">
                    <option value="">Todos</option>
                    <option value="puede_entrar" @selected($filtros['acceso'] === 'puede_entrar')>Pueden entrar (tienen usuario)</option>
                    <option value="sin_usuario" @selected($filtros['acceso'] === 'sin_usuario')>Sin usuario</option>
                    <option value="entraron" @selected($filtros['acceso'] === 'entraron')>Ya entraron alguna vez</option>
                    <option value="no_entraron" @selected($filtros['acceso'] === 'no_entraron')>Tienen usuario pero nunca entraron</option>
                </select>
            </div>

            <div class="flex items-end gap-2 lg:col-span-3">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('admin.clientes.index') }}" class="btn btn-ghost">Limpiar</a>
            </div>
        </div>
    </form>

    {{-- Listado --}}
    <div class="overflow-x-auto rounded-xl border border-slate-100 bg-white shadow-sm">
        <table class="w-full min-w-[900px] text-sm text-slate-700">
            <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase text-slate-400">
                <tr>
                    <th class="px-4 py-3 text-left">Cliente</th>
                    <th class="px-4 py-3 text-left">Contacto</th>
                    <th class="px-4 py-3 text-left">Vendedor</th>
                    <th class="px-4 py-3 text-right">Descuento</th>
                    <th class="px-4 py-3 text-right">Deuda / Límite</th>
                    <th class="px-4 py-3 text-center" title="Si tiene usuario de portal en Odoo, que es con el que entra al sitio">Acceso al sitio</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($clientes as $cliente)
                    <tr class="transition hover:bg-slate-50/60">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.clientes.show', $cliente) }}" class="font-medium text-[#2563C9] hover:underline">{{ $cliente->name ?: '(sin nombre en Odoo)' }}</a>
                            <span class="block text-xs text-slate-400">
                                {{ $cliente->vat ? 'CUIT ' . $cliente->vat : 'Sin CUIT' }}
                                @unless($cliente->active) · <span class="text-slate-500">Archivado</span> @endunless
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            {{ $cliente->email ?: '—' }}
                            <span class="block">{{ $cliente->phone ?: '' }}{{ $cliente->city ? ' · ' . $cliente->city : '' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($cliente->salesperson)
                                <a href="{{ route('admin.vendedores.show', $cliente->salesperson) }}" class="text-slate-700 hover:underline">{{ $cliente->salesperson->name }}</a>
                            @else
                                <span class="text-xs text-slate-400">Sin asignar</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ $cliente->price_discount > 0 ? rtrim(rtrim(number_format((float) $cliente->price_discount, 2, ',', '.'), '0'), ',') . '%' : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <span class="{{ $cliente->credit_limit > 0 && $cliente->credit > $cliente->credit_limit ? 'font-semibold text-[#E11A22]' : 'text-slate-800' }}">
                                {{ \App\Support\Precio::ar($cliente->credit) }}
                            </span>
                            <span class="block text-xs text-slate-400">de {{ $cliente->credit_limit > 0 ? \App\Support\Precio::ar($cliente->credit_limit) : 'sin límite' }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($cliente->has_portal)
                                <span class="inline-flex rounded bg-green-100 px-2 py-0.5 text-xs text-green-700" title="Tiene usuario de portal en Odoo">Puede entrar</span>
                            @else
                                <span class="inline-flex rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-500" title="Sin usuario de portal en Odoo: hay que darle acceso desde la ficha del contacto">Sin usuario</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">Ningún cliente coincide con esos filtros</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $clientes->links() }}</div>
</div>
@endsection
