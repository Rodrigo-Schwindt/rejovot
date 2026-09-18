@php
    /**
     * Desplegable con buscador interno: la lista de rubros tiene cientos de
     * opciones y un select común no se puede recorrer a mano.
     */
    $id = $id ?? 'combo';
    $modelo = $modelo ?? '';
    $opciones = $opciones ?? [];
    $vacio = $vacio ?? 'Todos';
    $seleccionado = $seleccionado ?? '';
@endphp

<div x-data="{
        abierto: false,
        busqueda: '',
        opciones: @js($opciones),
        get filtradas() {
            const q = this.busqueda.trim().toLowerCase();
            return q === '' ? this.opciones : this.opciones.filter(o => o.toLowerCase().includes(q));
        },
        elegir(valor) {
            $wire.set('{{ $modelo }}', valor);
            this.abierto = false;
            this.busqueda = '';
        },
     }"
     @click.outside="abierto = false"
     @keydown.escape.window="abierto = false"
     class="relative">

    <button type="button" @click="abierto = !abierto"
            class="flex h-[46px] w-full cursor-pointer items-center justify-between gap-2 rounded-[4px] border border-white/15 bg-white px-3 text-left text-[14px] outline-none focus:border-[#E11A22]"
            :aria-expanded="abierto" aria-haspopup="listbox">
        <span class="truncate {{ $seleccionado === '' ? 'text-slate-400' : 'text-slate-700' }}">
            {{ $seleccionado === '' ? $vacio : $seleccionado }}
        </span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="abierto && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="abierto" x-cloak
         x-transition:enter="transition duration-200 ease-out"
         x-transition:enter-start="-translate-y-1 scale-[.98] opacity-0"
         x-transition:enter-end="translate-y-0 scale-100 opacity-100"
         x-transition:leave="transition duration-150 ease-in"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="-translate-y-1 opacity-0"
         x-effect="if (abierto && window.matchMedia('(hover: hover)').matches) $nextTick(() => $refs.buscador?.focus())"
         class="absolute z-40 mt-1 w-full origin-top overflow-hidden rounded-[4px] border border-slate-200 bg-white shadow-[0_12px_32px_rgba(13,43,94,.22)]">

        <div class="border-b border-slate-100 p-2">
            <label class="sr-only" for="{{ $id }}-buscar">Buscar</label>
            <input id="{{ $id }}-buscar" type="text" x-model="busqueda" x-ref="buscador"
                   placeholder="Buscar…" autocomplete="off"
                   class="h-[38px] w-full rounded border border-slate-200 px-3 text-[14px] text-slate-700 outline-none focus:border-[#002B56]">
        </div>

        <ul class="max-h-[280px] overflow-y-auto py-1" role="listbox">
            <li>
                <button type="button" @click="elegir('')"
                        class="w-full cursor-pointer px-3 py-2 text-left text-[14px] text-slate-500 transition hover:bg-slate-50">
                    {{ $vacio }}
                </button>
            </li>

            <template x-for="opcion in filtradas" :key="opcion">
                <li>
                    <button type="button" @click="elegir(opcion)"
                            class="w-full cursor-pointer px-3 py-2 text-left text-[14px] text-slate-700 transition hover:bg-slate-50"
                            :class="opcion === @js($seleccionado) && 'bg-slate-50 font-semibold text-[#002B56]'"
                            x-text="opcion"></button>
                </li>
            </template>

            <li x-show="filtradas.length === 0" class="px-3 py-3 text-[13px] text-slate-400">
                Sin resultados
            </li>
        </ul>

        <p class="border-t border-slate-100 px-3 py-2 text-[12px] text-slate-400">
            <template x-if="busqueda.trim() === ''">
                <span><span x-text="opciones.length"></span> opciones. Escribí para filtrar.</span>
            </template>
            <template x-if="busqueda.trim() !== ''">
                <span><span x-text="filtradas.length"></span> de <span x-text="opciones.length"></span> opciones.</span>
            </template>
        </p>
    </div>
</div>
