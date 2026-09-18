@extends('layouts.admin')

@section('content')
@php use App\Support\Precio; @endphp
<div class="animate-fadeIn space-y-6">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('admin.clientes.index') }}" class="text-xs font-medium text-slate-400 hover:text-slate-600">← Clientes</a>
            <h2 class="mt-1 text-xl font-semibold text-slate-800">{{ $cliente->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $cliente->vat ? 'CUIT ' . $cliente->vat : 'Sin CUIT' }}
                @unless($cliente->active) · <span class="text-[#E11A22]">Archivado en Odoo</span> @endunless
            </p>
        </div>
        <a href="{{ rtrim(config('odoo.url'), '/') }}/web#model=res.partner&id={{ $cliente->odoo_id }}&view_type=form"
           target="_blank" rel="noopener" class="btn btn-ghost btn-sm">Abrir en Odoo</a>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        <div class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
            <span class="sec-label">Datos de contacto</span>
            <dl class="mt-3 divide-y divide-slate-50 text-sm">
                @foreach([
                    'Email' => $cliente->email,
                    'Teléfono' => $cliente->phone,
                    'Ciudad' => $cliente->city,
                ] as $titulo => $valor)
                    <div class="flex justify-between gap-4 py-2">
                        <dt class="text-slate-500">{{ $titulo }}</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $valor ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
            <span class="sec-label">Comercial</span>
            <dl class="mt-3 divide-y divide-slate-50 text-sm">
                <div class="flex justify-between gap-4 py-2">
                    <dt class="text-slate-500">Vendedor asignado</dt>
                    <dd class="text-right font-medium text-slate-800">
                        @if($cliente->salesperson)
                            <a href="{{ route('admin.vendedores.show', $cliente->salesperson) }}" class="text-[#2563C9] hover:underline">{{ $cliente->salesperson->name }}</a>
                        @else
                            <span class="text-slate-400">Sin asignar</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4 py-2">
                    <dt class="text-slate-500">Descuento propio</dt>
                    <dd class="text-right font-medium text-slate-800">
                        {{ $cliente->price_discount > 0 ? rtrim(rtrim(number_format((float) $cliente->price_discount, 2, ',', '.'), '0'), ',') . '%' : 'Ninguno' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4 py-2">
                    <dt class="text-slate-500">Deuda actual</dt>
                    <dd class="text-right font-medium {{ $cliente->credit_limit > 0 && $cliente->credit > $cliente->credit_limit ? 'text-[#E11A22]' : 'text-slate-800' }}">
                        {{ Precio::ar($cliente->credit) }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4 py-2">
                    <dt class="text-slate-500">Límite de crédito</dt>
                    <dd class="text-right font-medium text-slate-800">{{ $cliente->credit_limit > 0 ? Precio::ar($cliente->credit_limit) : 'Sin límite' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm lg:col-span-2">
            <span class="sec-label">Acceso al sitio</span>
            <dl class="mt-3 grid grid-cols-1 gap-x-8 text-sm sm:grid-cols-2">
                <div class="flex justify-between gap-4 py-2">
                    <dt class="text-slate-500">Usuario de portal en Odoo</dt>
                    <dd class="text-right font-medium text-slate-800">
                        {{ $cliente->has_portal ? ($cliente->portal_login ?: 'Sí') : 'No tiene: no puede entrar al sitio' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4 py-2">
                    <dt class="text-slate-500">Último ingreso al sitio</dt>
                    <dd class="text-right font-medium text-slate-800">
                        {{ $acceso?->last_login_at?->format('d/m/Y H:i') ?? 'Nunca entró' }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <p class="text-xs text-slate-400">Sincronizado de Odoo el {{ $cliente->updated_at?->format('d/m/Y H:i') }}.</p>
</div>
@endsection
