@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn space-y-6">

    <div>
        <a href="{{ route('admin.reclamos.index') }}" class="text-xs font-medium text-slate-400 hover:text-slate-600">← Reclamos</a>
        <h2 class="mt-1 text-xl font-semibold text-slate-800">Reclamo {{ $reclamo->numero }}</h2>
        <p class="mt-1 text-sm text-slate-500">
            Enviado el {{ $reclamo->created_at->format('d/m/Y H:i') }}
            @if($reclamo->user?->esVendedor()) · lo cargó {{ $reclamo->user->name }} @endif
        </p>
    </div>

    @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_380px]">

        <div class="space-y-6">
            {{-- Datos --}}
            <div class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
                <span class="sec-label">Datos del reclamo</span>
                <dl class="mt-3 grid grid-cols-1 gap-x-8 text-sm sm:grid-cols-2">
                    <div class="flex justify-between gap-4 border-b border-slate-50 py-2">
                        <dt class="text-slate-500">Cliente</dt>
                        <dd class="text-right font-medium">
                            @if($reclamo->customer)
                                <a href="{{ route('admin.clientes.show', $reclamo->customer) }}" class="text-[#2563C9] hover:underline">{{ $reclamo->customer->name }}</a>
                            @else — @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-slate-50 py-2">
                        <dt class="text-slate-500">Vendedor</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $reclamo->customer?->salesperson?->name ?? 'Sin asignar' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-slate-50 py-2">
                        <dt class="text-slate-500">Factura</dt>
                        <dd class="text-right font-medium text-slate-800">
                            {{ $reclamo->factura_numero }}
                            @unless($reclamo->factura_odoo_id)
                                <span class="block text-xs font-normal text-amber-600">escrita a mano, no se validó en Odoo</span>
                            @endunless
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-slate-50 py-2">
                        <dt class="text-slate-500">Fecha factura</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $reclamo->factura_fecha?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Artículos --}}
            <div class="overflow-x-auto rounded-xl border border-slate-100 bg-white shadow-sm">
                <table class="w-full min-w-[560px] text-sm text-slate-700">
                    <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase text-slate-400">
                        <tr>
                            <th class="px-4 py-3 text-left">Artículo</th>
                            <th class="px-4 py-3 text-center">Cantidad</th>
                            <th class="px-4 py-3 text-left">Observación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($reclamo->items as $item)
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-[#2563C9]">{{ $item->codigo }}</span>
                                    @if($item->nombre)<span class="block text-xs text-slate-500">{{ $item->nombre }}</span>@endif
                                </td>
                                <td class="px-4 py-3 text-center">{{ $item->cantidad }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $item->observacion ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Fotos --}}
            @if($reclamo->fotos->isNotEmpty())
                <div class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
                    <span class="sec-label">Fotos ({{ $reclamo->fotos->count() }})</span>
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach($reclamo->fotos as $foto)
                            <a href="{{ route('admin.reclamos.foto', [$reclamo, $foto]) }}" target="_blank" rel="noopener"
                               class="block h-[110px] w-[110px] overflow-hidden rounded-lg border border-slate-200 transition hover:border-[#002B56]">
                                <img src="{{ route('admin.reclamos.foto', [$reclamo, $foto]) }}" alt="{{ $foto->archivo_original }}" class="h-full w-full object-cover" loading="lazy">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Gestión --}}
        <form method="POST" action="{{ route('admin.reclamos.update', $reclamo) }}"
              class="h-fit rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
            @csrf @method('PATCH')
            <span class="sec-label">Gestión</span>

            <div class="mt-4">
                <label class="f-label" for="estado">Estado</label>
                <select id="estado" name="estado" class="f-input">
                    @foreach($estados as $valor => $nombre)
                        <option value="{{ $valor }}" @selected(old('estado', $reclamo->estado) === $valor)>{{ $nombre }}</option>
                    @endforeach
                </select>
                @error('estado')<p class="mt-1 text-xs text-[#E11A22]">{{ $message }}</p>@enderror
            </div>

            <div class="mt-4">
                <label class="f-label" for="respuesta">Respuesta al cliente</label>
                <textarea id="respuesta" name="respuesta" rows="6" class="f-input h-auto py-2"
                          placeholder="La ve el cliente en «Ver reclamo» y le llega por mail.">{{ old('respuesta', $reclamo->respuesta) }}</textarea>
                @error('respuesta')<p class="mt-1 text-xs text-[#E11A22]">{{ $message }}</p>@enderror
                @if($reclamo->respondido_at)
                    <p class="mt-1 text-xs text-slate-400">Última respuesta: {{ $reclamo->respondido_at->format('d/m/Y H:i') }}</p>
                @endif
            </div>

            <p class="mt-3 text-xs text-slate-500">
                Si cambia el estado o hay una respuesta nueva, se le manda un mail a
                {{ $reclamo->customer?->email ?: 'el cliente (no tiene mail cargado en Odoo)' }}.
            </p>

            <button type="submit" class="btn btn-primary mt-4 w-full justify-center">Guardar</button>
        </form>
    </div>
</div>
@endsection
