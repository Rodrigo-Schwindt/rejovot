@php
    $campo = 'h-[46px] w-full rounded-[4px] border border-white/15 bg-white px-3 text-[14px] text-slate-600 outline-none focus:border-[#E11A22]';
@endphp

{{-- Panel de búsqueda y filtros --}}
<section class="rounded-[6px] bg-[#0D2B5E] p-5 lg:p-6">

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
        <div class="flex-1">
            <label for="buscador" class="sr-only">Buscar por descripción, código o equivalencia</label>
            <input id="buscador" type="text" wire:model.live.debounce.500ms="q"
                   placeholder="Marca / Equivalencias / Atributo / Código"
                   class="h-[46px] w-full rounded-[4px] border border-white/15 bg-white px-4 text-[14px] text-slate-700 outline-none placeholder:text-slate-400 focus:border-[#E11A22]">
        </div>

        <label class="flex cursor-pointer items-center gap-3 text-[15px] text-white lg:px-3">
            <span>Ofertas</span>
            <span class="relative inline-flex h-[22px] w-[42px] shrink-0 items-center rounded-full transition-colors {{ $soloOfertas ? 'bg-[#E11A22]' : 'bg-white/30' }}">
                <input type="checkbox" wire:model.live="soloOfertas" class="peer sr-only">
                <span class="absolute left-[3px] h-[16px] w-[16px] rounded-full bg-white transition-transform {{ $soloOfertas ? 'translate-x-[20px]' : '' }}"></span>
            </span>
        </label>

        <div class="flex gap-3">
            <button type="button" wire:click="buscar"
                    class="h-[46px] cursor-pointer rounded-[4px] bg-white px-7 text-[14px] font-bold uppercase tracking-wide text-[#0D2B5E] transition hover:bg-slate-100">
                Buscar
            </button>
            <button type="button" wire:click="limpiar"
                    class="h-[46px] cursor-pointer rounded-[4px] border border-white/60 px-7 text-[14px] font-bold uppercase tracking-wide text-white transition hover:bg-white/10">
                Limpiar
            </button>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-x-5 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">

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
</section>
