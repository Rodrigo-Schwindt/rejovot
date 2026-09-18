@php
    use App\Support\Precio;

    $mensajeWssp = rawurlencode("Hola, quiero consultar por {$producto['codigo']} - {$producto['nombre']}");
@endphp

<div>
    {{-- Ficha del producto. --}}

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">

        <nav class="anim-entrada mb-6 text-[13px] text-slate-500 max-lg:truncate" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="font-semibold text-slate-700 transition hover:text-[#002B56]">Inicio</a>
            <span class="mx-1.5 text-slate-400">&gt;</span>
            <a wire:navigate href="{{ route('productos') }}" class="transition hover:text-[#002B56]">Producto</a>
            <span class="mx-1.5 text-slate-400">&gt;</span>
            <span class="text-slate-600">{{ $producto['descripcion'] }}</span>
        </nav>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-12">

            {{-- Galería: la principal y, si Odoo tiene, las adicionales. --}}
            @php
                $imagenes = array_values(array_filter($producto['imagenes'] ?? [$producto['imagen'] ?? null]));
                $maxThumbs = 4;
            @endphp

            <div class="anim-entrada flex gap-6 max-lg:flex-col-reverse max-lg:gap-4" style="--retraso: 60"
                 x-data="{
                    actual: 0,
                    abierto: false,
                    total: {{ count($imagenes) }},
                    max: {{ $maxThumbs }},
                    // Con más de cuatro, el cuarto thumb muestra «+N»: es informativo, no despliega el resto.
                    visible(i) { return this.total <= this.max || i < this.max; },
                    resume(i) { return this.total > this.max && i === this.max - 1; },
                    activo(i) { return this.actual === i || (this.resume(i) && this.actual >= i); },
                    ir(i) { this.actual = (i + this.total) % this.total; },
                 }"
                 x-effect="document.body.style.overflow = abierto ? 'hidden' : ''"
                 @keydown.escape.window="abierto = false"
                 @keydown.arrow-right.window="abierto && ir(actual + 1)"
                 @keydown.arrow-left.window="abierto && ir(actual - 1)">

                <div class="flex flex-col gap-6 max-lg:flex-row max-lg:gap-3 max-lg:overflow-x-auto max-lg:pb-1">
                    @foreach($imagenes as $i => $src)
                        <button type="button" x-show="visible({{ $i }})" x-cloak
                                @click="actual = {{ $i }}"
                                :class="activo({{ $i }}) ? 'border-[#002B56]' : 'border-[#D9D9D9] hover:border-slate-400'"
                                class="relative flex h-[80px] w-[80px] shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-[4px] border bg-white p-2 transition"
                                aria-label="Imagen {{ $i + 1 }}">
                            @include('livewire.vistas.productos.partials.producto-imagen', ['class' => 'h-full w-full', 'src' => $src, 'alt' => $producto['codigo'] . ' ' . ($i + 1)])

                            {{-- «+N»: las que quedan afuera, contando esta misma. --}}
                            <span x-show="resume({{ $i }})" x-cloak
                                  class="absolute inset-0 flex items-center justify-center bg-[#002B56]/75 text-[18px] font-bold text-white">
                                +<span x-text="total - max + 1"></span>
                            </span>
                        </button>
                    @endforeach
                </div>

                <div class="flex h-[492px] w-full max-w-[520px] items-center justify-center rounded-[4px] border border-[#D9D9D9] bg-white p-6 max-lg:h-[400px] max-lg:max-w-none max-sm:h-[300px] max-sm:p-4 {{ $imagenes ? 'cursor-zoom-in' : '' }}"
                     @if($imagenes) @click="abierto = true" role="button" tabindex="0" @keydown.enter="abierto = true" aria-label="Ampliar imagen" @endif>
                    @foreach($imagenes as $i => $src)
                        <div x-show="actual === {{ $i }}" x-cloak
                             x-transition:enter="transition duration-300 ease-out"
                             x-transition:enter-start="scale-[.97] opacity-0"
                             x-transition:enter-end="scale-100 opacity-100"
                             class="flex h-full w-full items-center justify-center">
                            @include('livewire.vistas.productos.partials.producto-imagen', ['class' => 'h-full w-full', 'src' => $src, 'alt' => $producto['codigo'] . ' ' . ($i + 1)])
                        </div>
                    @endforeach

                    @if(! $imagenes)
                        @include('livewire.vistas.productos.partials.producto-imagen', ['class' => 'h-full w-full', 'src' => null, 'alt' => $producto['codigo']])
                    @endif
                </div>

                {{-- Visor a pantalla completa: fondo oscuro, la imagen al centro y flechas para pasar. --}}
                @if($imagenes)
                    <template x-teleport="body">
                        <div x-show="abierto" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/85 backdrop-blur-sm"
                             role="dialog" aria-modal="true" aria-label="Imágenes de {{ $producto['codigo'] }}"
                             @click.self="abierto = false">

                            <button type="button" @click="abierto = false"
                                    class="absolute right-4 top-4 flex h-11 w-11 cursor-pointer items-center justify-center rounded-full text-white/80 transition hover:bg-white/10 hover:text-white"
                                    aria-label="Cerrar">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>

                            @if(count($imagenes) > 1)
                                <button type="button" @click.stop="ir(actual - 1)"
                                        class="absolute left-3 top-1/2 flex h-12 w-12 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full text-white/80 transition hover:bg-white/10 hover:text-white md:left-6"
                                        aria-label="Imagen anterior">
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7"/></svg>
                                </button>
                                <button type="button" @click.stop="ir(actual + 1)"
                                        class="absolute right-3 top-1/2 flex h-12 w-12 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full text-white/80 transition hover:bg-white/10 hover:text-white md:right-6"
                                        aria-label="Imagen siguiente">
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
                                </button>
                            @endif

                            <div class="relative h-[80vh] w-[min(90vw,1100px)]" @click.self="abierto = false">
                                @foreach($imagenes as $i => $src)
                                    <img x-show="actual === {{ $i }}" x-cloak
                                         x-transition:enter="transition ease-out duration-300"
                                         x-transition:enter-start="opacity-0 scale-[.98]"
                                         x-transition:enter-end="opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-150"
                                         x-transition:leave-start="opacity-100"
                                         x-transition:leave-end="opacity-0"
                                         src="{{ $src }}" alt="{{ $producto['codigo'] }} {{ $i + 1 }}" draggable="false"
                                         class="absolute inset-0 m-auto max-h-full max-w-full select-none object-contain">
                                @endforeach
                            </div>

                            @if(count($imagenes) > 1)
                                <p class="absolute bottom-5 left-1/2 -translate-x-1/2 text-[14px] tracking-wide text-white/70">
                                    <span x-text="actual + 1"></span> / {{ count($imagenes) }}
                                </p>
                            @endif
                        </div>
                    </template>
                @endif
            </div>

            {{-- Datos y compra: precios y botones al pie, a la altura del final de la imagen. --}}
            <div class="anim-entrada flex flex-col" style="--retraso: 140">
                <p class="flex flex-wrap items-center gap-3 text-[16px] font-bold text-[#002B56]">
                    {{ $producto['codigo'] }}
                    @include('livewire.vistas.productos.partials.badge-oferta', ['producto' => $producto])
                </p>
                <hr class="my-[10px] border-[#D9D9D9]">

                <h1 class="text-[32px] font-bold uppercase leading-[125%] text-[#111] max-sm:text-[24px]">
                    {{ $producto['descripcion'] }}
                </h1>

                <dl class="mt-auto space-y-3 pt-10 text-[16px] max-lg:pt-6">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="font-semibold text-[#111]">Precio Lista</dt>
                        <dd class="font-semibold text-[#111]">{{ Precio::ar($producto['lista']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="font-semibold text-[#111]">Tu precio</dt>
                        <dd class="font-semibold text-[#111]">{{ Precio::ar($producto['costo']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="font-semibold text-[#111]">Precio Venta</dt>
                        <dd class="font-bold text-[#111]">{{ Precio::ar($producto['precio_venta']) }}</dd>
                    </div>
                </dl>

                <div class="mt-8 flex flex-wrap items-center justify-between gap-4">
                    @if($wssp)
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $wssp) }}?text={{ $mensajeWssp }}"
                           target="_blank" rel="noopener"
                           class="inline-flex h-[45px] items-center rounded-[4px] bg-[#002B56] px-3 text-[16px] font-semibold uppercase tracking-wide text-white transition hover:bg-[#0A2249] max-sm:w-full max-sm:justify-center">
                            Consultar
                        </a>
                    @endif

                    <div class="flex items-center gap-3 max-sm:w-full max-sm:justify-end">
                        <label class="sr-only" for="cantidad-detalle">Cantidad</label>
                        {{-- Flechas propias: las del navegador sólo aparecen al pasar el mouse. --}}
                        <div class="flex h-[44px] w-[60px] items-center rounded-[4px] border border-slate-300 bg-white focus-within:border-[#002B56]"
                             x-data="{ paso(n) { const i = $refs.cant; i.stepUp(n); i.dispatchEvent(new Event('input', { bubbles: true })) } }">
                            <input id="cantidad-detalle" type="number" min="1" step="1" x-ref="cant" wire:model.live="cantidad"
                                   class="cantidad h-full w-full min-w-0 rounded-l-[4px] bg-transparent pl-2 text-center text-[15px] text-slate-800 outline-none">
                            <div class="flex h-full shrink-0 flex-col justify-center gap-[3px] pr-1.5">
                                <button type="button" @click="paso(1)" class="cursor-pointer" aria-label="Sumar uno">
                                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M5 0 9.33 5.25H.67L5 0Z" fill="black"/></svg>
                                </button>
                                <button type="button" @click="paso(-1)" class="cursor-pointer" aria-label="Restar uno">
                                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M5 6 .67.75h8.66L5 6Z" fill="black"/></svg>
                                </button>
                            </div>
                        </div>

                        <button type="button" wire:click="agregar('{{ $producto['codigo'] }}')"
                                class="inline-flex h-[44px] w-[48px] cursor-pointer items-center justify-center gap-0.5 rounded-[4px] border border-[#002B56] text-[#002B56] transition hover:bg-[#002B56] hover:text-white"
                                title="Agregar al carrito" aria-label="Agregar {{ $producto['codigo'] }} al carrito">
                            <span class="text-[15px] font-bold leading-none">+</span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l3-7H5.4M7 13 5.4 5M7 13l-1.3 2.6A1 1 0 0 0 6.6 17H19"/>
                                <circle cx="9" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Aplicaciones y atributos --}}
        {{-- <div class="mt-14 grid grid-cols-1 gap-10 lg:grid-cols-2 lg:gap-12">
            <section>
                <h2 class="mb-5 text-[28px] font-bold text-slate-900">Aplicaciones</h2>
                <ul>
                    @forelse($producto['aplicaciones'] as $aplicacion)
                        <li class="px-4 py-3 text-[15px] text-slate-700 {{ $loop->even ? 'bg-white' : 'bg-slate-50' }}">{{ $aplicacion }}</li>
                    @empty
                        <li class="px-4 py-3 text-[15px] text-slate-500">Sin aplicaciones cargadas.</li>
                    @endforelse
                </ul>
            </section>

            <section>
                <h2 class="mb-5 text-[28px] font-bold text-slate-900">Atributos</h2>
                <dl>
                    @forelse($producto['atributos'] as $nombre => $valor)
                        <div class="flex items-center justify-between gap-4 px-4 py-3 text-[15px] {{ $loop->even ? 'bg-white' : 'bg-slate-50' }}">
                            <dt class="text-slate-700">{{ $nombre }}</dt>
                            <dd class="text-slate-800">{{ $valor }}</dd>
                        </div>
                    @empty
                        <p class="px-4 py-3 text-[15px] text-slate-500">Sin atributos cargados.</p>
                    @endforelse
                </dl>
            </section>
        </div> --}}

        {{-- Visor para las imágenes de las cards de relacionados --}}
        @include('livewire.vistas.productos.partials.visor-imagenes')

        {{-- Relacionados --}}
        @if($relacionados)
            <section class="anim-entrada mt-14 max-lg:mt-10" style="--retraso: 220">
                <h2 class="mb-6 text-[28px] font-bold text-slate-900 max-sm:text-[22px]">Productos relacionados</h2>

                {{-- Misma card que el catálogo en cuadrícula: cuatro columnas con 24px de gap. --}}
                {{-- En celulares pasa a carrusel horizontal con snap. --}}
                {{-- En celular pasa solo cada 4s; si el usuario arrastra, espera y sigue. --}}
                <div x-data="{
                        timer: null,
                        esperarHasta: 0,
                        get enCelular() { return window.matchMedia('(max-width: 639px)').matches },
                        iniciar() {
                            if (this.$el.children.length < 2) return;
                            this.timer = setInterval(() => this.avanzar(), 4000);
                        },
                        avanzar() {
                            // Quieto si no es celular, si la pestaña está oculta o si lo están tocando.
                            if (! this.enCelular || document.hidden || Date.now() < this.esperarHasta) return;

                            const carrusel = this.$el;
                            const paso = (carrusel.firstElementChild?.offsetWidth ?? carrusel.clientWidth) + 16;
                            const ultimo = carrusel.scrollWidth - carrusel.clientWidth - 8;

                            carrusel.scrollTo({
                                left: carrusel.scrollLeft >= ultimo ? 0 : carrusel.scrollLeft + paso,
                                behavior: 'smooth',
                            });
                        },
                        pausar() { this.esperarHasta = Date.now() + 6000 },
                        destroy() { clearInterval(this.timer) },
                     }"
                     x-init="iniciar()"
                     @pointerdown="pausar()" @touchstart.passive="pausar()" @wheel.passive="pausar()"
                     class="grid grid-cols-1 items-stretch gap-6 sm:grid-cols-2 lg:grid-cols-4 max-sm:-mx-4 max-sm:flex max-sm:snap-x max-sm:snap-mandatory max-sm:gap-4 max-sm:overflow-x-auto max-sm:px-4 max-sm:pb-3 max-sm:[scrollbar-width:none] max-sm:[&::-webkit-scrollbar]:hidden">
                    @foreach($relacionados as $item)
                        <div wire:key="rel-{{ $item['codigo'] }}" class="max-sm:w-[82%] max-sm:shrink-0 max-sm:snap-start">
                            @include('livewire.vistas.productos.partials.producto-card', ['producto' => $item])
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
