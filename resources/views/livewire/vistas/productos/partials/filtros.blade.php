@php
    $campo = 'h-[46px] w-full rounded-[4px] border border-white/15 bg-white px-3 text-[14px] text-slate-600 outline-none focus:border-[#E11A22]';

    // En mobile los combos van plegados: el contador avisa si hay alguno aplicado.
    $filtrosActivos = collect([$marca, $rubro, $codigo, $oem])
        ->filter(fn ($valor) => trim((string) $valor) !== '')
        ->count();
@endphp

{{-- Panel de búsqueda y filtros --}}
<section class="rounded-[6px] bg-[#002B56] p-5 h-[201px] max-lg:h-auto lg:p-6"
         x-data="{ filtrosAbiertos: {{ $filtrosActivos > 0 ? 'true' : 'false' }} }">

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
        <div class="flex-1">
            <label for="buscador" class="sr-only">Buscar por descripción, código o equivalencia</label>
            <input id="buscador" type="text" wire:model.live.debounce.500ms="q"
                   placeholder="Marca / Equivalencias / Atributo / Código"
                   class="h-[46px] w-full rounded-[4px] border border-white/15 bg-white px-4 text-[14px] text-slate-700 outline-none placeholder:text-slate-400 focus:border-[#E11A22]">
        </div>

        {{-- En mobile, Ofertas y los botones comparten fila; en escritorio el wrapper desaparece (contents). --}}
        <div class="flex flex-wrap items-center justify-between gap-3 lg:contents">
            <label class="flex cursor-pointer items-center gap-3 text-[15px] text-white lg:px-3">
                <span>Ofertas</span>
                <span class="relative inline-flex h-[22px] w-[42px] shrink-0 items-center rounded-full bg-white">
                    <input type="checkbox" wire:model.live="soloOfertas" class="peer sr-only">
                    <span class="absolute left-[3px] h-[16px] w-[16px] rounded-full transition-all duration-300 ease-out {{ $soloOfertas ? 'translate-x-[20px] bg-[#E11A22]' : 'bg-[#002B56]' }}"></span>
                </span>
            </label>

            <div class="flex gap-3 max-sm:w-full">
                <button type="button" wire:click="buscar"
                        class="h-[45px] cursor-pointer rounded-[4px] bg-white px-3 text-[16px] font-semibold uppercase tracking-wide text-[#002B56] transition hover:bg-slate-100 max-sm:flex-1">
                    Buscar
                </button>
                <button type="button" wire:click="limpiar" @click="filtrosAbiertos = false"
                        class="h-[45px] cursor-pointer rounded-[4px] border border-white/60 px-3 text-[16px] font-semibold uppercase tracking-wide text-white transition hover:bg-white/10 max-sm:flex-1">
                    Limpiar
                </button>
            </div>
        </div>
    </div>

    {{-- Sólo mobile: pliega/despliega los combos --}}
    <button type="button" @click="filtrosAbiertos = !filtrosAbiertos"
            class="mt-4 flex h-[42px] w-full cursor-pointer items-center justify-between rounded-[4px] border border-white/25 px-3 text-[14px] font-semibold text-white lg:hidden"
            :aria-expanded="filtrosAbiertos" aria-controls="filtros-avanzados">
        <span class="flex items-center gap-2">
            Más filtros
            @if($filtrosActivos > 0)
                <span class="inline-flex h-[20px] min-w-[20px] items-center justify-center rounded-full bg-[#E11A22] px-1.5 text-[11px] font-bold leading-none">
                    {{ $filtrosActivos }}
                </span>
            @endif
        </span>
        <svg class="h-4 w-4 shrink-0 transition-transform duration-300" :class="filtrosAbiertos && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
        </svg>
    </button>

    {{--
        Acordeón sin JS extra: la fila de la grilla pasa de 0fr a 1fr.
        En escritorio siempre está desplegado y sin overflow, para que los combos se vean.
    --}}
    <div id="filtros-avanzados"
         class="grid transition-[grid-template-rows,opacity] duration-300 ease-out lg:grid-rows-[1fr] lg:opacity-100"
         :class="filtrosAbiertos ? 'max-lg:grid-rows-[1fr] max-lg:opacity-100' : 'max-lg:grid-rows-[0fr] max-lg:overflow-hidden max-lg:opacity-0'">
        <div class="min-h-0">
            <div class="mt-6 grid grid-cols-1 gap-x-[24px] gap-y-4 sm:grid-cols-2 lg:grid-cols-4 max-lg:pb-1">

                {{-- Marca --}}
                <div>
                    <p class="mb-2 text-[19px] font-semibold text-white">Marca</p>
                    @include('livewire.vistas.productos.partials.combo-buscador', [
                        'id' => 'filtro-marca',
                        'modelo' => 'marca',
                        'opciones' => $opciones['marcas'],
                        'vacio' => 'Todas las marcas',
                        'seleccionado' => $marca,
                    ])
                </div>

                {{-- Rubro --}}
                <div>
                    <p class="mb-2 text-[19px] font-semibold text-white">Rubro</p>
                    @include('livewire.vistas.productos.partials.combo-buscador', [
                        'id' => 'filtro-rubro',
                        'modelo' => 'rubro',
                        'opciones' => $opciones['rubros'],
                        'vacio' => 'Todos los rubros',
                        'seleccionado' => $rubro,
                    ])
                </div>

                {{-- Código del producto: la referencia interna, no la equivalencia. --}}
                <div>
                    <p class="mb-2 text-[19px] font-semibold text-white">Código</p>
                    <label class="sr-only" for="filtro-codigo">Código del producto</label>
                    <input id="filtro-codigo" type="text" wire:model.live.debounce.500ms="codigo"
                           placeholder="Código del producto" class="{{ $campo }}">
                </div>

                {{-- Código OEM --}}
                <div>
                    <p class="mb-2 text-[19px] font-semibold text-white">Código OEM</p>
                    <label class="sr-only" for="filtro-oem">Código OEM</label>
                    <input id="filtro-oem" type="text" wire:model.live.debounce.500ms="oem"
                           placeholder="Código original" class="{{ $campo }}">
                </div>
            </div>
        </div>
    </div>
</section>
