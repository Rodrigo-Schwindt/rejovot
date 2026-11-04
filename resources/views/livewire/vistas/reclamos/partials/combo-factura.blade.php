{{--
    N° factura de la garantía: desplegable con buscador. Muestra las facturas del
    cliente (número y fecha), filtra mientras se escribe y deja usar un número
    escrito a mano si no está en la lista. Es opcional: «Sin factura» lo vacía.
--}}
@php
    $opcionesFactura = array_map(fn (array $f) => [
        'numero' => $f['numero'],
        'fecha' => $f['fecha'] ? \Carbon\Carbon::parse($f['fecha'])->format('d/m/Y') : '',
    ], $facturas);
@endphp

<div wire:key="combo-factura-{{ md5($facturaNumero . count($facturas)) }}"
     x-data="{
        abierto: false,
        busqueda: '',
        opciones: @js($opcionesFactura),
        get filtradas() {
            const q = this.busqueda.trim().toLowerCase();
            return q === '' ? this.opciones : this.opciones.filter(o => o.numero.toLowerCase().includes(q) || o.fecha.includes(q));
        },
        get escritaAMano() {
            const q = this.busqueda.trim();
            return q !== '' && ! this.opciones.some(o => o.numero.toLowerCase() === q.toLowerCase());
        },
        elegir(valor) {
            $wire.set('facturaNumero', valor);
            this.abierto = false;
            this.busqueda = '';
        },
     }"
     @click.outside="abierto = false"
     @keydown.escape.window="abierto = false"
     class="relative">

    <button type="button" id="factura" @click="abierto = !abierto"
            class="flex h-[46px] w-full cursor-pointer items-center justify-between gap-2 rounded-[4px] border border-slate-300 bg-white px-4 text-left text-[15px] outline-none focus:border-[#002B56]"
            :aria-expanded="abierto" aria-haspopup="listbox">
        <span class="truncate {{ $facturaNumero === '' ? 'text-slate-400' : 'text-slate-700' }}">
            @if($facturaNumero !== '')
                {{ $facturaNumero }}
            @else
                {{ $facturas ? 'Elegí una factura o escribila' : 'Escribí el número de factura' }}
            @endif
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
         x-effect="if (abierto && window.matchMedia('(hover: hover)').matches) $nextTick(() => $refs.buscarFactura?.focus())"
         class="absolute z-40 mt-1 w-full origin-top overflow-hidden rounded-[4px] border border-slate-200 bg-white shadow-[0_12px_32px_rgba(13,43,94,.22)]">

        <div class="border-b border-slate-100 p-2">
            <label class="sr-only" for="factura-buscar">Buscar o escribir el número de factura</label>
            <input id="factura-buscar" type="text" x-model="busqueda" x-ref="buscarFactura"
                   @keydown.enter.prevent="escritaAMano ? elegir(busqueda.trim()) : (filtradas[0] && elegir(filtradas[0].numero))"
                   placeholder="Buscar o escribir el número…" autocomplete="off"
                   class="h-[38px] w-full rounded border border-slate-200 px-3 text-[14px] text-slate-700 outline-none focus:border-[#002B56]">
        </div>

        <ul class="max-h-[300px] overflow-y-auto py-1" role="listbox">
            {{-- Número escrito que no está en la lista: se puede usar igual. --}}
            <li x-show="escritaAMano">
                <button type="button" @click="elegir(busqueda.trim())"
                        class="w-full cursor-pointer px-3 py-2 text-left text-[14px] text-[#002B56] transition hover:bg-slate-50">
                    Usar «<span class="font-semibold" x-text="busqueda.trim()"></span>»
                </button>
            </li>

            <li>
                <button type="button" @click="elegir('')"
                        class="w-full cursor-pointer px-3 py-2 text-left text-[14px] text-slate-500 transition hover:bg-slate-50">
                    Sin factura
                </button>
            </li>

            <template x-for="opcion in filtradas" :key="opcion.numero">
                <li>
                    <button type="button" @click="elegir(opcion.numero)"
                            class="flex w-full cursor-pointer items-center justify-between gap-3 px-3 py-2 text-left text-[14px] text-slate-700 transition hover:bg-slate-50"
                            :class="opcion.numero === @js($facturaNumero) && 'bg-slate-50 font-semibold text-[#002B56]'">
                        <span x-text="opcion.numero"></span>
                        <span class="shrink-0 text-[13px] text-slate-400" x-text="opcion.fecha"></span>
                    </button>
                </li>
            </template>

            <li x-show="opciones.length && filtradas.length === 0 && ! escritaAMano" class="px-3 py-3 text-[13px] text-slate-400">
                Sin resultados
            </li>
        </ul>

        @if($facturas)
            <p class="border-t border-slate-100 px-3 py-2 text-[12px] text-slate-400">
                Tus últimas {{ count($facturas) }} facturas. Si no está, escribí el número y elegí «Usar».
            </p>
        @endif
    </div>
</div>
