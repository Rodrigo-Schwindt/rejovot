@extends('layouts.admin')

@section('content')
@php
    $color = fn (string $estado) => match ($estado) {
        'nota_credito', 'cerrado' => 'bg-green-100 text-green-700',
        'rechazado' => 'bg-red-50 text-[#E11A22]',
        default => 'bg-amber-100 text-amber-700',
    };
@endphp
<div class="animate-fadeIn space-y-6">

    <div>
        <h2 class="text-xl font-semibold text-slate-800">
            Reclamos <span class="text-base font-normal text-slate-400">({{ $reclamos->total() }})</span>
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Los que cargan los clientes desde el sitio. Al cambiar el estado o responder, el cliente recibe un mail.
        </p>
    </div>

    @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

    {{-- Resumen por estado: cada tarjeta filtra --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach($estados as $valor => $nombre)
            <a href="{{ route('admin.reclamos.index', ['estado' => $valor]) }}"
               class="rounded-xl border bg-white p-4 shadow-sm transition hover:border-slate-300 {{ $filtros['estado'] === $valor ? 'border-[#002B56]' : 'border-slate-100' }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $nombre }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-800">{{ number_format($porEstado[$valor] ?? 0, 0, ',', '.') }}</p>
            </a>
        @endforeach
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('admin.reclamos.index') }}" class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-[1fr_220px_auto]">
            <div>
                <label class="f-label" for="q">Buscar</label>
                <input type="text" id="q" name="q" value="{{ $filtros['q'] }}" class="f-input"
                       placeholder="N° de reclamo, cliente, factura o código de artículo">
            </div>
            <div>
                <label class="f-label" for="estado">Estado</label>
                <select id="estado" name="estado" class="f-input">
                    <option value="">Todos</option>
                    @foreach($estados as $valor => $nombre)
                        <option value="{{ $valor }}" @selected($filtros['estado'] === $valor)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('admin.reclamos.index') }}" class="btn btn-ghost">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-slate-100 bg-white shadow-sm">
        <table class="w-full min-w-[820px] text-sm text-slate-700">
            <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase text-slate-400">
                <tr>
                    <th class="px-4 py-3 text-left">N°</th>
                    <th class="px-4 py-3 text-left">Cliente</th>
                    <th class="px-4 py-3 text-left">Factura</th>
                    <th class="px-4 py-3 text-center">Artículos</th>
                    <th class="px-4 py-3 text-left">Enviado</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-center"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($reclamos as $reclamo)
                    <tr class="transition hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $reclamo->numero }}</td>
                        <td class="px-4 py-3">
                            <span class="font-medium text-slate-800">{{ $reclamo->customer?->name ?? '—' }}</span>
                            @if($reclamo->customer?->salesperson)
                                <span class="block text-xs text-slate-400">Vendedor: {{ $reclamo->customer->salesperson->name }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $reclamo->factura_numero }}</td>
                        <td class="px-4 py-3 text-center">{{ $reclamo->items_count }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $reclamo->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium {{ $color($reclamo->estado) }}">{{ $reclamo->estado_nombre }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.reclamos.show', $reclamo) }}" class="btn btn-ghost btn-sm">Gestionar</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">
                        {{ $filtros['q'] !== '' || $filtros['estado'] !== '' ? 'Ningún reclamo coincide con esos filtros' : 'Todavía no llegó ningún reclamo' }}
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $reclamos->links() }}</div>
</div>
@endsection
