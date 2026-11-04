{{--
    Buscador y filtros que acompañan al bajar, cuando el panel de arriba ya no
    se ve («fuera», del x-data de la página):
    - Escritorio (lg+): barra fija arriba con todos los campos en una fila.
    - Celular / tablet: botón flotante «Filtros» que abre una hoja inferior.
    Son los mismos wire:model que el panel, así que todo queda sincronizado.
    Al cambiar un filtro se vuelve al principio del listado.
--}}
@php
    $campoFlotante = 'h-[40px] w-full rounded-[4px] border border-white/15 bg-white px-3 text-[13px] text-slate-700 outline-none placeholder:text-slate-400 focus:border-[#E11A22]';
    $campoHoja = 'h-[46px] w-full rounded-[6px] border border-white/15 bg-white px-3 text-[14px] text-slate-700 outline-none placeholder:text-slate-400 focus:border-[#E11A22]';

    $filtrosAplicados = collect([$q, $marca, $rubro, $codigo, $oem])
        ->filter(fn ($valor) => trim((string) $valor) !== '')
        ->count() + ($soloOfertas ? 1 : 0);
@endphp

<div x-data="{
        hoja: false,
        arrastre: 0,
        inicioY: null,
        subir() {
            const catalogo = document.getElementById('catalogo');
            if (! catalogo) return;
            const top = catalogo.getBoundingClientRect().top + window.scrollY - (window.innerWidth >= 1024 ? 80 : 16);
            if (window.scrollY > top) window.scrollTo({ top, behavior: 'smooth' });
        },
        abrir() { this.arrastre = 0; this.hoja = true; },
        verResultados() { this.hoja = false; this.$nextTick(() => this.subir()); },
        empezarArrastre(e) { this.inicioY = e.touches[0].clientY; },
        arrastrar(e) {
            if (this.inicioY === null) return;
            this.arrastre = Math.max(0, e.touches[0].clientY - this.inicioY);
        },
        soltar() {
            if (this.arrastre > 110) this.hoja = false;
            else this.arrastre = 0;
            this.inicioY = null;
        },
     }"
     x-effect="document.documentElement.classList.toggle('overflow-hidden', hoja)"
     x-on:livewire:navigating.document="hoja = false; document.documentElement.classList.remove('overflow-hidden')"
     @keydown.escape.window="hoja = false">

    {{-- ===================== Escritorio: barra fija ===================== --}}
    <div x-show="fuera" x-cloak
         x-transition:enter="transition duration-300 ease-[cubic-bezier(.22,1,.36,1)]"
         x-transition:enter-start="-translate-y-full opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition duration-200 ease-in"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="-translate-y-full opacity-0"
         @filtro-elegido="subir()"
         class="fixed inset-x-0 top-0 z-[90] max-lg:hidden">

        <div class="relative border-b border-white/10 bg-[#002B56]/95 shadow-[0_12px_32px_rgba(13,43,94,.30)] backdrop-blur-md">
            <div class="mx-auto grid w-full max-w-[1300px] grid-cols-[minmax(0,1.7fr)_repeat(4,minmax(0,1fr))_auto_auto] items-center gap-3 px-4 py-3 xl:px-6">

                <label class="relative block">
                    <span class="sr-only">Buscar por descripción, código o equivalencia</span>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/>
                    </svg>
                    <input type="text" wire:model.live.debounce.500ms="q" @input.debounce.600ms="subir()"
                           placeholder="Marca / Equivalencias / Atributo / Código"
                           class="{{ $campoFlotante }} pl-9">
                </label>

                @include('livewire.vistas.productos.partials.combo-buscador', [
                    'id' => 'flotante-marca',
                    'modelo' => 'marca',
                    'opciones' => $opciones['marcas'],
                    'vacio' => 'Todas las marcas',
                    'seleccionado' => $marca,
                    'alto' => 'h-[40px]',
                ])

                @include('livewire.vistas.productos.partials.combo-buscador', [
                    'id' => 'flotante-rubro',
                    'modelo' => 'rubro',
                    'opciones' => $opciones['rubros'],
                    'vacio' => 'Todos los rubros',
                    'seleccionado' => $rubro,
                    'alto' => 'h-[40px]',
                ])

                <label class="block">
                    <span class="sr-only">Código del producto</span>
                    <input type="text" wire:model.live.debounce.500ms="codigo" @input.debounce.600ms="subir()"
                           placeholder="Código" class="{{ $campoFlotante }}">
                </label>

                <label class="block">
                    <span class="sr-only">Código OEM</span>
                    <input type="text" wire:model.live.debounce.500ms="oem" @input.debounce.600ms="subir()"
                           placeholder="Código OEM" class="{{ $campoFlotante }}">
                </label>

                <label class="flex cursor-pointer items-center gap-2 text-[14px] text-white">
                    <span>Ofertas</span>
                    <span class="relative inline-flex h-[22px] w-[42px] shrink-0 items-center rounded-full bg-white">
                        <input type="checkbox" wire:model.live="soloOfertas" @change="subir()" class="peer sr-only">
                        <span class="absolute left-[3px] h-[16px] w-[16px] rounded-full transition-all duration-300 ease-out {{ $soloOfertas ? 'translate-x-[20px] bg-[#E11A22]' : 'bg-[#002B56]' }}"></span>
                    </span>
                </label>

                <button type="button" wire:click="limpiar" @click="subir()"
                        title="Limpiar búsqueda y filtros"
                        class="inline-flex h-[40px] cursor-pointer items-center gap-1.5 rounded-[4px] border border-white/50 px-3 text-[13px] font-semibold uppercase tracking-wide text-white transition hover:bg-white/10 disabled:cursor-default disabled:opacity-40"
                        @disabled($filtrosAplicados === 0)>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                    Limpiar
                </button>
            </div>

            {{-- Mientras filtra: una línea roja que recorre el borde inferior. --}}
            <div wire:loading.block wire:target="{{ $objetivosCarga }}"
                 class="absolute inset-x-0 bottom-0 h-[3px] overflow-hidden" style="display: none">
                <div class="rj-progreso h-full w-2/5 rounded-full bg-gradient-to-r from-transparent via-[#E11A22] to-transparent"></div>
            </div>
        </div>
    </div>

    {{-- ===================== Celular: botón flotante ===================== --}}
    {{-- z-[90]: por debajo del menú del celular (z-98) y de la ficha (z-9991). --}}
    <div x-show="fuera && ! hoja" x-cloak
         x-transition:enter="transition duration-300 ease-[cubic-bezier(.22,1,.36,1)]"
         x-transition:enter-start="translate-y-8 opacity-0 scale-90"
         x-transition:enter-end="translate-y-0 opacity-100 scale-100"
         x-transition:leave="transition duration-200 ease-in"
         x-transition:leave-start="translate-y-0 opacity-100 scale-100"
         x-transition:leave-end="translate-y-8 opacity-0 scale-90"
         class="pointer-events-none fixed inset-x-0 bottom-[max(28px,env(safe-area-inset-bottom))] z-[90] flex justify-center lg:hidden">
        <button type="button" @click="abrir()"
                class="pointer-events-auto inline-flex h-[52px] cursor-pointer items-center gap-2.5 rounded-full bg-[#002B56] pl-5 pr-4 text-[15px] font-semibold text-white shadow-[0_10px_28px_rgba(13,43,94,.45)] ring-1 ring-white/15 transition duration-200 hover:bg-[#0A2249] active:scale-95"
                aria-haspopup="dialog" :aria-expanded="hoja">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-width="2" d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0"/>
                <circle cx="16" cy="6" r="2" stroke-width="2"/>
                <circle cx="10" cy="12" r="2" stroke-width="2"/>
                <circle cx="18" cy="18" r="2" stroke-width="2"/>
            </svg>
            Filtros
            @if($filtrosAplicados > 0)
                <span class="inline-flex h-[22px] min-w-[22px] items-center justify-center rounded-full bg-[#E11A22] px-1.5 text-[12px] font-bold leading-none">
                    {{ $filtrosAplicados }}
                </span>
            @endif
        </button>
    </div>

    {{-- ===================== Celular: hoja de filtros ===================== --}}
    <div x-show="hoja" x-cloak
         x-transition:enter="transition duration-300 ease-out"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition duration-200 ease-in"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="hoja = false"
         class="fixed inset-0 z-[9991] touch-none bg-black/50 backdrop-blur-[3px] lg:hidden"
         aria-hidden="true"></div>

    {{-- El contenedor hace la entrada/salida; la hoja de adentro sigue al dedo. --}}
    <div x-show="hoja" x-cloak
         x-transition:enter="transition duration-[380ms] ease-[cubic-bezier(.22,1,.36,1)]"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition duration-200 ease-in"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="fixed inset-x-0 bottom-0 z-[9992] lg:hidden">
    <section :style="`transform: translateY(${arrastre}px); transition: ${inicioY === null ? 'transform .3s cubic-bezier(.22,1,.36,1)' : 'none'}`"
             class="flex max-h-[88dvh] flex-col rounded-t-[20px] bg-[#002B56] shadow-[0_-16px_48px_rgba(0,0,0,.35)]"
             role="dialog" aria-modal="true" aria-label="Filtros de productos">

        {{-- Cabecera: agarradera (se puede bajar con el dedo para cerrar) --}}
        <header class="shrink-0 touch-none px-5 pb-3 pt-3"
                @touchstart.passive="empezarArrastre($event)"
                @touchmove.passive="arrastrar($event)"
                @touchend="soltar()">
            <span class="mx-auto block h-[5px] w-[44px] rounded-full bg-white/30" aria-hidden="true"></span>
            <div class="mt-3 flex items-center justify-between">
                <div>
                    <h2 class="text-[19px] font-bold text-white">Filtros</h2>
                    <p class="text-[13px] text-white/60">
                        {{ $filtrosAplicados === 0 ? 'Sin filtros aplicados' : ($filtrosAplicados === 1 ? '1 filtro aplicado' : "{$filtrosAplicados} filtros aplicados") }}
                    </p>
                </div>
                <button type="button" @click="hoja = false"
                        class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20 active:scale-90"
                        aria-label="Cerrar filtros">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </header>

        <div class="min-h-0 flex-1 space-y-5 overflow-y-auto overscroll-contain border-t border-white/10 px-5 py-5">

            <div class="anim-entrada" style="--retraso: 60">
                <label for="hoja-buscador" class="mb-2 block text-[15px] font-semibold text-white">Buscar</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/>
                    </svg>
                    <input id="hoja-buscador" type="text" wire:model.live.debounce.500ms="q"
                           placeholder="Marca / Equivalencias / Atributo / Código"
                           class="{{ $campoHoja }} pl-9">
                </div>
            </div>

            <label class="anim-entrada flex cursor-pointer items-center justify-between rounded-[8px] bg-white/[.06] px-4 py-3 text-[15px] font-semibold text-white" style="--retraso: 100">
                <span>Sólo ofertas</span>
                <span class="relative inline-flex h-[24px] w-[46px] shrink-0 items-center rounded-full bg-white">
                    <input type="checkbox" wire:model.live="soloOfertas" class="peer sr-only">
                    <span class="absolute left-[3px] h-[18px] w-[18px] rounded-full transition-all duration-300 ease-out {{ $soloOfertas ? 'translate-x-[22px] bg-[#E11A22]' : 'bg-[#002B56]' }}"></span>
                </span>
            </label>

            <div class="anim-entrada" style="--retraso: 140">
                <p class="mb-2 text-[15px] font-semibold text-white">Marca</p>
                @include('livewire.vistas.productos.partials.combo-buscador', [
                    'id' => 'hoja-marca',
                    'modelo' => 'marca',
                    'opciones' => $opciones['marcas'],
                    'vacio' => 'Todas las marcas',
                    'seleccionado' => $marca,
                ])
            </div>

            <div class="anim-entrada" style="--retraso: 180">
                <p class="mb-2 text-[15px] font-semibold text-white">Rubro</p>
                @include('livewire.vistas.productos.partials.combo-buscador', [
                    'id' => 'hoja-rubro',
                    'modelo' => 'rubro',
                    'opciones' => $opciones['rubros'],
                    'vacio' => 'Todos los rubros',
                    'seleccionado' => $rubro,
                ])
            </div>

            <div class="anim-entrada grid grid-cols-2 gap-3" style="--retraso: 220">
                <div>
                    <label for="hoja-codigo" class="mb-2 block text-[15px] font-semibold text-white">Código</label>
                    <input id="hoja-codigo" type="text" wire:model.live.debounce.500ms="codigo"
                           placeholder="Del producto" class="{{ $campoHoja }}">
                </div>
                <div>
                    <label for="hoja-oem" class="mb-2 block text-[15px] font-semibold text-white">Código OEM</label>
                    <input id="hoja-oem" type="text" wire:model.live.debounce.500ms="oem"
                           placeholder="Original" class="{{ $campoHoja }}">
                </div>
            </div>
        </div>

        {{-- Pie fijo: limpiar y ver los resultados --}}
        <footer class="flex shrink-0 gap-3 border-t border-white/10 bg-[#0A2249]/60 px-5 pb-[max(16px,env(safe-area-inset-bottom))] pt-4">
            <button type="button" wire:click="limpiar"
                    class="h-[50px] cursor-pointer rounded-[8px] border border-white/40 px-5 text-[15px] font-semibold text-white transition hover:bg-white/10 active:scale-95 disabled:cursor-default disabled:opacity-40"
                    @disabled($filtrosAplicados === 0)>
                Limpiar
            </button>
            <button type="button" @click="verResultados()"
                    class="relative flex h-[50px] flex-1 cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-[8px] bg-white text-[15px] font-bold text-[#002B56] shadow-[0_6px_18px_rgba(0,0,0,.25)] transition active:scale-[.98]">
                <svg wire:loading wire:target="{{ $objetivosCarga }}" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"/>
                </svg>
                Ver productos
            </button>
        </footer>
    </section>
    </div>
</div>
