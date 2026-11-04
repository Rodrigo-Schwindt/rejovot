@php use App\Support\Precio; @endphp

@php
    // Primero los banners de Admin > Sliders, después los productos destacados.
    $cantidadSliders = count($sliders);
    $totalSlides = $cantidadSliders + count($ofertas);

    // Colores de cada slide. Los destacados (null) quedan con los de siempre.
    $temas = [
        ...$sliders->map(fn ($slider) => [
            'fondo' => $slider->fondo,
            'descripcion' => $slider->descripcion_color,
            'franja' => $slider->etiqueta_fondo,
        ])->all(),
        ...array_fill(0, count($ofertas), null),
    ];
@endphp

{{-- Banner superior: breadcrumb + banners administrables y productos destacados desde el admin --}}
<section x-data="{
            slide: 0,
            total: {{ $totalSlides }},
            temas: @js($temas),
            timer: null,
            x0: 0,
            get tema() { return this.temas[this.slide] ?? null },
            // Migas de pan y puntos toman el color de la descripción del banner.
            get colorTexto() { return this.tema ? `color: ${this.tema.descripcion}` : '' },
            colorPunto(i) { return this.tema ? `background-color: ${this.tema.descripcion}; opacity: ${this.slide === i ? 1 : .45}` : '' },
            play() { this.stop(); if (this.total > 1) this.timer = setInterval(() => this.slide = (this.slide + 1) % this.total, 5000) },
            stop() { clearInterval(this.timer) },
            ir(i) { this.slide = (i + this.total) % this.total; this.play() },
            // Swipe en pantallas táctiles
            deslizar(dx) { if (Math.abs(dx) > 40) this.ir(this.slide + (dx < 0 ? 1 : -1)) },
         }"
         x-init="play()" @mouseenter="stop()" @mouseleave="play()"
         @touchstart.passive="x0 = $event.touches[0].clientX"
         @touchend.passive="deslizar($event.changedTouches[0].clientX - x0)"
         :style="tema ? `background-color: ${tema.fondo}` : ''"
         @if($cantidadSliders) style="background-color: {{ $temas[0]['fondo'] }}" @endif
         class="relative w-full overflow-hidden bg-[#002B56] text-white transition-colors duration-500">

    {{-- Adorno de los banners administrables: franja y círculos del fondo. --}}
    @if($cantidadSliders)
        <div x-show="tema" x-cloak x-transition.opacity.duration.500ms class="pointer-events-none absolute inset-0" aria-hidden="true">
            <span class="absolute inset-y-0 left-0 w-[6px]" :style="tema ? `background-color: ${tema.franja}` : ''"></span>
            <span class="absolute -top-[120px] right-[-60px] h-[340px] w-[340px] rounded-full opacity-[.06]" :style="tema ? `background-color: ${tema.descripcion}` : ''"></span>
            <span class="absolute -bottom-[150px] right-[220px] h-[260px] w-[260px] rounded-full opacity-[.04] max-lg:hidden" :style="tema ? `background-color: ${tema.descripcion}` : ''"></span>
        </div>
    @endif

    <div class="relative mx-auto w-full min-h-[200px] max-w-[1300px] px-4 pb-8 pt-5 xl:px-6 max-lg:pb-10">

        <nav class="text-[13px] text-white/85" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="transition hover:text-white" :style="colorTexto">Inicio</a>
            <span class="mx-1.5 text-white/50" :style="tema ? `${colorTexto}; opacity: .6` : ''">&gt;</span>
            <span class="text-white" :style="colorTexto">Productos</span>
        </nav>

        @if($totalSlides)
            <div class="mt-4">
                {{-- La animación envuelve sólo los slides: los puntos (absolute) se posicionan respecto al contenedor relative. --}}
                {{-- Todas las slides ocupan la misma celda (.rj-slide): el carrusel mide lo que la más alta y no salta al pasar de una a otra. --}}
                <div class="anim-entrada grid grid-cols-1 items-center">
                @foreach($sliders as $slider)
                    <div class="rj-slide" :data-activa="slide === {{ $loop->index }}" @if($loop->first) data-activa @endif>
                        @include('livewire.vistas.productos.partials.banner-slider')
                    </div>
                @endforeach

                @foreach($ofertas as $oferta)
                    <div :data-activa="slide === {{ $cantidadSliders + $loop->index }}" @if(! $cantidadSliders && $loop->first) data-activa @endif
                         class="rj-slide flex flex-col items-center gap-5 lg:flex-row lg:gap-8 lg:px-[130px]">

                        {{-- En mobile el descuento y la foto van lado a lado; en escritorio el wrapper desaparece (contents). --}}
                        <div class="flex items-center gap-5 lg:contents">
                            @if($oferta['descuento'] ?? null)
                                <div class="flex h-[110px] w-[100px] shrink-0 items-center justify-center bg-[#E11A22] text-[30px] font-bold"
                                     style="clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);">
                                    {{ rtrim(rtrim(number_format((float) $oferta['descuento'], 2, ',', '.'), '0'), ',') }}%
                                </div>
                            @endif

                            <div class="flex h-[130px] w-[130px] shrink-0 items-center justify-center rounded ">
                                @include('livewire.vistas.productos.partials.producto-imagen', [
                                    'class' => 'h-full w-full',
                                    'src' => $oferta['imagen'] ?? null,
                                    'alt' => $oferta['codigo'],
                                ])
                            </div>
                        </div>

                        <div class="min-w-0 flex-1 text-center lg:text-left">
                            <a wire:navigate href="{{ route('producto', ['codigo' => $oferta['codigo']]) }}"
                               class="mb-1 block text-[17px] font-bold tracking-wide hover:underline">{{ $oferta['codigo'] }}</a>
                            <a wire:navigate href="{{ route('producto', ['codigo' => $oferta['codigo']]) }}"
                               class="line-clamp-3 text-[18px] font-normal leading-[130%] transition hover:underline lg:block lg:h-[70px] lg:max-w-[460px]">
                                {{ $oferta['nombre'] }}
                            </a>
                            {{-- Precio y botón, sólo con sesión de cliente o vendedor. --}}
                            @auth('sitio')
                            <p class="mt-2 flex flex-wrap items-baseline justify-center gap-2 lg:justify-start">
                                @if($oferta['precio_ant'] ?? null)
                                    <span class="text-[15px] text-white line-through">{{ Precio::ar($oferta['precio_ant']) }}</span>
                                @endif
                                <span class="text-[26px] font-bold">{{ Precio::ar($oferta['precio']) }}</span>
                                <span class="text-[13px] text-white">+ IVA</span>
                                @if($oferta['hasta'] ?? null)
                                    <span class="text-[13px] text-white">· hasta el {{ $oferta['hasta']->format('d/m') }}</span>
                                @endif
                            </p>
                            @endauth
                        </div>

                        @auth('sitio')
                        <button type="button" wire:click="agregar('{{ $oferta['codigo'] }}')"
                                class="shrink-0 cursor-pointer rounded bg-[#E11A22] px-5 py-3 text-[15px] font-bold uppercase tracking-wide text-white transition hover:bg-[#B8141B] max-sm:w-full">
                            + Agregar
                        </button>
                        @endauth
                    </div>
                @endforeach
                </div>

                @if($totalSlides > 1)
                    <div class="absolute inset-x-0 bottom-2 flex items-center justify-center gap-2">
                        @for($i = 0; $i < $totalSlides; $i++)
                            <button type="button" @click="ir({{ $i }})"
                                    class="h-[5px] rounded-full transition-all"
                                    :class="slide === {{ $i }} ? 'w-8 bg-[#E11A22]' : 'w-6 bg-white/45 hover:bg-white/70'"
                                    :style="colorPunto({{ $i }})"
                                    aria-label="Ver destacado {{ $i + 1 }}"></button>
                        @endfor
                    </div>
                @endif
            </div>
        @endif
    </div>
</section>
