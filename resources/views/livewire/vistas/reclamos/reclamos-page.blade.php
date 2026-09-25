@php
    $campo = 'h-[46px] w-full rounded-[4px] border border-slate-300 bg-white px-4 text-[15px] text-slate-700 outline-none placeholder:text-slate-400 focus:border-[#002B56]';
@endphp

<div>
    {{-- Reclamos del cliente activo. --}}

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">

        <nav class="anim-entrada mb-6 text-[13px] text-slate-500" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="transition hover:text-[#002B56]">Inicio</a>
            <span class="mx-1.5 text-slate-400">&gt;</span>
            <span class="text-slate-700">Reclamos</span>
        </nav>

        <div class="anim-entrada mb-8 flex flex-wrap items-center justify-between gap-4" style="--retraso: 40">
            <h1 class="text-[34px] font-bold leading-[120%] text-slate-900 max-sm:text-[26px]">Reclamos</h1>

            @if($cliente)
                <a wire:navigate href="{{ route('reclamos.nuevo') }}"
                   class="inline-flex h-[46px] items-center rounded-[4px] bg-[#002B56] px-6 text-[15px] font-bold uppercase tracking-wide text-white transition hover:bg-[#0A2249]">
                    Nuevo reclamo
                </a>
            @endif
        </div>

        @if($cliente)
            <p class="anim-entrada -mt-4 mb-6 text-[14px] text-slate-500" style="--retraso: 60">
                Reclamos de <span class="font-semibold text-slate-700">{{ $cliente->name }}</span>
            </p>

            {{-- Filtros --}}
            <div class="anim-entrada mb-8 grid grid-cols-1 gap-x-8 gap-y-5 md:grid-cols-3" style="--retraso: 90">
                <div>
                    <label for="f-numero" class="mb-2 block text-[19px] font-semibold text-slate-900">N° reclamo</label>
                    <input id="f-numero" type="text" wire:model.live.debounce.400ms="numero" placeholder="Ingrese número" class="{{ $campo }}">
                </div>
                <div>
                    <label for="f-articulo" class="mb-2 block text-[19px] font-semibold text-slate-900">Artículo</label>
                    <input id="f-articulo" type="text" wire:model.live.debounce.400ms="articulo" placeholder="Ingrese código de artículo" class="{{ $campo }}">
                </div>
                <div>
                    <label for="f-estado" class="mb-2 block text-[19px] font-semibold text-slate-900">Estado</label>
                    <select id="f-estado" wire:model.live="estado" class="{{ $campo }} cursor-pointer">
                        <option value="">Todos</option>
                        @foreach($estados as $valor => $nombre)
                            <option value="{{ $valor }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        <div class="anim-entrada relative overflow-x-auto bg-white" style="--retraso: 140"
             wire:loading.class="opacity-60" wire:target="numero, articulo, estado, gotoPage, nextPage, previousPage">
            <table class="w-full min-w-[640px] border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-[#F8F8F8] text-[16px] font-semibold text-slate-800">
                        <th class="px-6 py-5 text-left">N° reclamo</th>
                        <th class="px-6 py-5 text-left">Fecha envío</th>
                        <th class="px-6 py-5 text-left">Estado</th>
                        <th class="px-6 py-5 text-right"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reclamos ?? [] as $reclamo)
                        <tr wire:key="reclamo-{{ $reclamo->id }}" class="border-b border-slate-200 align-middle">
                            <td class="px-6 py-6 text-[16px] text-slate-700">{{ $reclamo->numero }}</td>
                            <td class="px-6 py-6 text-[16px] text-slate-700">{{ $reclamo->created_at->format('d/m/Y') }}</td>
                            <td class="px-6 py-6 text-[16px] text-slate-700">{{ $reclamo->estado_nombre }}</td>
                            <td class="px-6 py-6 text-right">
                                <a wire:navigate href="{{ route('reclamos.ver', $reclamo) }}"
                                   class="inline-flex h-[46px] items-center rounded-[4px] border border-[#002B56] px-4 text-[15px] font-bold uppercase tracking-wide text-[#002B56] transition hover:bg-[#002B56] hover:text-white">
                                    Ver reclamo
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-14 text-center text-[15px] text-slate-500">
                                @if($motivo)
                                    {{ $motivo }}
                                @elseif($numero !== '' || $articulo !== '' || $estado !== '')
                                    Ningún reclamo coincide con esos filtros.
                                @else
                                    Todavía no hiciste reclamos.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reclamos?->hasPages())
            <div class="mt-6">{{ $reclamos->links('vendor.pagination.publico') }}</div>
        @endif
    </div>
</div>
