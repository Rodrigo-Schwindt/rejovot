{{--
    Botón «Descargar» con desplegable: CSV o Excel.
    El menú va con position fixed, calculado desde el botón: la tabla está en un
    contenedor con scroll y un absolute quedaría recortado. Por eso también se
    cierra al hacer scroll.
--}}
<div class="relative"
     x-data="{
        abierto: false,
        lugar: '',
        alternar() {
            if (this.abierto) {
                this.abierto = false;
                return;
            }

            const ancho = 240, alto = 124, margen = 6;
            const boton = this.$refs.boton.getBoundingClientRect();
            const entraAbajo = boton.bottom + margen + alto <= window.innerHeight;
            const arriba = entraAbajo ? boton.bottom + margen : boton.top - margen - alto;
            const izquierda = Math.max(8, boton.right - ancho);

            this.lugar = `top: ${arriba}px; left: ${izquierda}px; width: ${ancho}px`;
            this.abierto = true;
        },
     }"
     @click.outside="abierto = false"
     @keydown.escape.window="abierto = false"
     @scroll.window.capture="abierto = false"
     @resize.window="abierto = false">

    <button type="button" x-ref="boton" @click="alternar()"
            class="inline-flex h-[44px] cursor-pointer items-center gap-2 rounded-[4px] border border-[#002B56] px-6 text-[14px] font-bold uppercase tracking-wide text-[#002B56] transition hover:bg-[#002B56] hover:text-white"
            :class="abierto && 'bg-[#002B56] text-white'"
            aria-haspopup="menu" :aria-expanded="abierto">
        Descargar
        <svg class="h-4 w-4 transition-transform duration-200" :class="abierto && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="abierto" x-cloak :style="lugar"
         x-transition:enter="transition duration-200 ease-out"
         x-transition:enter-start="-translate-y-1 scale-[.98] opacity-0"
         x-transition:enter-end="translate-y-0 scale-100 opacity-100"
         x-transition:leave="transition duration-150 ease-in"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="-translate-y-1 opacity-0"
         class="fixed z-50 origin-top overflow-hidden rounded-[4px] border border-slate-200 bg-white py-1 text-left shadow-[0_12px_32px_rgba(13,43,94,.22)]"
         role="menu">

        <a href="{{ route('precios.descargar', $lista) }}" @click="abierto = false" role="menuitem"
           class="block px-4 py-2.5 transition hover:bg-slate-50">
            <span class="block text-[14px] font-semibold text-[#002B56]">CSV</span>
            <span class="block text-[12px] text-slate-500">{{ $lista->tamano_legible }}</span>
        </a>

        <a href="{{ route('precios.descargar', [$lista, 'formato' => 'excel']) }}" @click="abierto = false" role="menuitem"
           class="block px-4 py-2.5 transition hover:bg-slate-50">
            <span class="block text-[14px] font-semibold text-[#002B56]">Excel</span>
            <span class="block text-[12px] text-slate-500">{{ $lista->tamano_xlsx_legible }}</span>
        </a>
    </div>
</div>
