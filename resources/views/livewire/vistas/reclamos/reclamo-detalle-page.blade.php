@php
    $colorEstado = match ($reclamo->estado) {
        'nota_credito', 'cerrado' => 'bg-green-100 text-green-800',
        'rechazado' => 'bg-red-100 text-[#B8141B]',
        default => 'bg-amber-100 text-amber-800',
    };
@endphp

<div>
    {{-- Detalle de un reclamo. --}}

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">

        <nav class="anim-entrada mb-6 text-[13px] text-slate-500" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="transition hover:text-[#002B56]">Inicio</a>
            <span class="mx-1.5 text-slate-400">&gt;</span>
            <a wire:navigate href="{{ route('reclamos') }}" class="transition hover:text-[#002B56]">Reclamos</a>
            <span class="mx-1.5 text-slate-400">&gt;</span>
            <span class="text-slate-700">{{ $reclamo->numero }}</span>
        </nav>

        <div class="anim-entrada mb-8 flex flex-wrap items-center justify-between gap-4" style="--retraso: 40">
            <h1 class="text-[34px] font-bold leading-[120%] text-slate-900 max-sm:text-[26px]">Reclamo {{ $reclamo->numero }}</h1>
            <span class="inline-flex rounded-full px-4 py-1.5 text-[15px] font-semibold {{ $colorEstado }}">{{ $reclamo->estado_nombre }}</span>
        </div>

        <dl class="anim-entrada mb-8 grid grid-cols-1 gap-x-8 gap-y-4 text-[16px] sm:grid-cols-3" style="--retraso: 80">
            <div>
                <dt class="text-slate-500">Fecha reclamo</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $reclamo->fecha->format('d/m/Y') }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">N° factura</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $reclamo->factura_numero }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Fecha factura</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $reclamo->factura_fecha?->format('d/m/Y') ?? '—' }}</dd>
            </div>
        </dl>

        {{-- Respuesta de Rejovot --}}
        @if($reclamo->respuesta)
            <div class="anim-entrada mb-8 rounded-[4px] border border-[#002B56]/25 bg-[#002B56]/[.04] px-5 py-4" style="--retraso: 110">
                <p class="text-[14px] font-semibold uppercase tracking-wide text-[#002B56]">Respuesta de Rejovot</p>
                <p class="mt-2 whitespace-pre-line text-[16px] leading-[150%] text-slate-800">{{ $reclamo->respuesta }}</p>
                @if($reclamo->respondido_at)
                    <p class="mt-2 text-[13px] text-slate-500">{{ $reclamo->respondido_at->format('d/m/Y H:i') }}</p>
                @endif
            </div>
        @endif

        {{-- Artículos --}}
        <div class="anim-entrada relative overflow-x-auto bg-white" style="--retraso: 140">
            <table class="w-full min-w-[640px] border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-[#F8F8F8] text-[16px] font-semibold text-slate-800">
                        <th class="px-6 py-4 text-left">Artículo</th>
                        <th class="px-6 py-4 text-center">Cantidad</th>
                        <th class="px-6 py-4 text-left">Observación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reclamo->items as $item)
                        <tr class="border-b border-slate-200 align-top">
                            <td class="px-6 py-5">
                                <span class="block text-[14px] font-semibold text-[#002B56]">{{ $item->codigo }}</span>
                                @if($item->nombre)
                                    <span class="mt-0.5 block text-[14px] uppercase text-slate-700">{{ $item->nombre }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-center text-[16px] text-slate-700">{{ $item->cantidad }}</td>
                            <td class="px-6 py-5 text-[15px] text-slate-700">{{ $item->observacion ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Fotos --}}
        @if($reclamo->fotos->isNotEmpty())
            <div class="anim-entrada mt-8" style="--retraso: 180">
                <p class="mb-3 text-[19px] font-semibold text-slate-900">Fotos</p>
                <div class="flex flex-wrap gap-3">
                    @foreach($reclamo->fotos as $foto)
                        <a href="{{ route('reclamos.foto', [$reclamo, $foto]) }}" target="_blank" rel="noopener"
                           class="block h-[120px] w-[120px] overflow-hidden rounded-[4px] border border-slate-200 bg-white transition hover:border-[#002B56]">
                            <img src="{{ route('reclamos.foto', [$reclamo, $foto]) }}" alt="{{ $foto->archivo_original }}" class="h-full w-full object-cover" loading="lazy">
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-10">
            <a wire:navigate href="{{ route('reclamos') }}"
               class="inline-flex h-[46px] items-center rounded-[4px] border border-[#002B56] px-5 text-[15px] font-bold uppercase tracking-wide text-[#002B56] transition hover:bg-slate-50">
                Volver a reclamos
            </a>
        </div>
    </div>
</div>
