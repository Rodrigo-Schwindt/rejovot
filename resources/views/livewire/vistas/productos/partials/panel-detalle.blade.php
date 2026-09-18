{{--
    Ficha del producto seleccionado.
    - Escritorio (lg+): columna fija a la derecha, siempre visible.
    - Mobile / tablet: panel inferior que se desliza al tocar una fila o card
      (evento `abrir-ficha`) y se cierra con la X, el fondo o Escape.
    El wrapper es `contents`, así el <aside> sigue siendo hijo directo de la grilla.
--}}
<div class="contents"
     x-data="{ abierto: false }"
     @abrir-ficha.window="abierto = true"
     @keydown.escape.window="abierto = false"
     x-on:livewire:navigating.document="abierto = false">

    {{-- Fondo oscuro, sólo mobile. Por encima del botón flotante de WhatsApp (z-9990) y debajo del visor (z-9999). --}}
    <div x-show="abierto" x-cloak
         x-transition:enter="transition duration-300 ease-out"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition duration-200 ease-in"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="abierto = false"
         class="fixed inset-0 z-[9991] touch-none bg-black/45 backdrop-blur-[2px] lg:hidden"
         aria-hidden="true"></div>

    <aside class="space-y-5 lg:pt-[38px]
                  max-lg:fixed max-lg:inset-x-0 max-lg:bottom-0 max-lg:z-[9992] max-lg:max-h-[86dvh] max-lg:overflow-y-auto max-lg:overscroll-contain
                  max-lg:rounded-t-[16px] max-lg:bg-white max-lg:px-4 max-lg:pb-[max(24px,env(safe-area-inset-bottom))] max-lg:pt-2
                  max-lg:shadow-[0_-12px_40px_rgba(13,43,94,.25)] max-lg:transition-transform max-lg:duration-300 max-lg:ease-[cubic-bezier(.22,1,.36,1)]"
           :class="abierto ? 'max-lg:translate-y-0' : 'max-lg:translate-y-full'"
           :aria-hidden="!abierto && window.innerWidth < 1024"
           aria-label="Ficha del producto">

        {{-- Cabecera del panel, sólo mobile: agarradera + cerrar --}}
        <div class="sticky top-0 z-10 -mx-4 -mt-2 flex items-center justify-between bg-white/95 px-4 pb-2 pt-2 backdrop-blur lg:hidden">
            <span class="absolute left-1/2 top-2 h-[4px] w-[42px] -translate-x-1/2 rounded-full bg-slate-300" aria-hidden="true"></span>
            <span class="pt-3 text-[13px] font-semibold uppercase tracking-wide text-slate-500">Ficha</span>
            <button type="button" @click="abierto = false"
                    class="-mr-2 mt-2 flex h-9 w-9 cursor-pointer items-center justify-center rounded-full text-slate-500"
                    aria-label="Cerrar ficha">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Mientras llega la ficha elegida (sólo mobile, donde el panel se abre antes de la respuesta) --}}
        <div wire:loading.flex wire:target="seleccionar"
             class="items-center gap-2 text-[13px] text-slate-500 lg:hidden!">
            <svg class="h-4 w-4 animate-spin text-[#002B56]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4z"/></svg>
            Cargando ficha…
        </div>

        @if($detalle)
            {{-- Código y descripción llevan al detalle; la imagen abre el visor. --}}
            @php $urlDetalle = route('producto', ['codigo' => $detalle['codigo']]); @endphp
            <div wire:key="ficha-{{ $detalle['codigo'] }}" class="anim-aparecer space-y-5 max-lg:flex max-lg:flex-col">
                {{-- Sólo mobile: acceso directo al detalle completo. Va primero en el DOM para no sumar margen en escritorio. --}}
                <a wire:navigate href="{{ $urlDetalle }}"
                   class="flex h-[46px] w-full items-center justify-center rounded-[4px] bg-[#002B56] text-[15px] font-semibold uppercase tracking-wide text-white max-lg:order-last max-lg:mt-5 lg:hidden">
                    Ver producto
                </a>

                <div>
                    <a wire:navigate href="{{ $urlDetalle }}" title="Ver detalle del producto"
                       class="flex h-[39px] w-full items-center justify-center rounded-t-[4px] border border-[#D9D9D9] bg-[#F8F8F8] text-center text-[20px] font-semibold leading-[140%] text-black transition hover:text-[#002B56]">
                        {{ $detalle['codigo'] }}
                    </a>
                    <div class="flex h-[181px] w-full items-center justify-center border border-t-0 border-[#D9D9D9] {{ ! empty($detalle['imagen']) ? 'cursor-zoom-in' : '' }}"
                         x-data @click="$dispatch('abrir-visor', { imagenes: @js($detalle['imagenes'] ?? array_filter([$detalle['imagen'] ?? null])), titulo: @js($detalle['codigo']) })">
                        @include('livewire.vistas.productos.partials.producto-imagen', ['class' => 'h-full w-full', 'src' => $detalle['imagen'] ?? null, 'alt' => $detalle['codigo']])
                    </div>
                    <a wire:navigate href="{{ $urlDetalle }}" title="Ver detalle del producto"
                       class="mt-[11px] block text-[16px] font-normal uppercase leading-[20px] text-black transition hover:text-[#002B56]">
                        {{ $detalle['descripcion'] }}
                    </a>
                </div>

                @if(! empty($detalle['aplicaciones']))
                    <div class="rounded-[4px] border border-slate-200 bg-white">
                        <h3 class="border-b border-slate-200 px-4 py-3 text-center text-[17px] font-bold text-slate-900">Aplicaciones</h3>
                        <ul class="divide-y divide-slate-100">
                            @foreach($detalle['aplicaciones'] as $aplicacion)
                                <li class="px-4 py-2.5 text-[13px] text-slate-600">{{ $aplicacion }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(! empty($detalle['atributos']))
                    <div class="rounded-[4px] border border-slate-200 bg-white">
                        <h3 class="border-b border-slate-200 px-4 py-3 text-center text-[17px] font-bold text-slate-900">Atributos</h3>
                        <dl class="divide-y divide-slate-100">
                            @foreach($detalle['atributos'] as $nombre => $valor)
                                <div class="flex items-center justify-between gap-4 px-4 py-2.5">
                                    <dt class="text-[13px] text-slate-600">{{ $nombre }}</dt>
                                    <dd class="text-[13px] font-medium text-slate-800">{{ $valor }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            </div>
        @else
            <div class="rounded-[4px] border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-[14px] text-slate-500 max-lg:hidden">
                Seleccioná un producto para ver su ficha.
            </div>
        @endif
    </aside>
</div>
