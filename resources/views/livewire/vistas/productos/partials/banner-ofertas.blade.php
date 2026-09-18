@php use App\Support\Precio; @endphp

{{-- Banner superior: breadcrumb + productos destacados desde el admin --}}
<section class="w-full bg-[#002B56] text-white">
    <div class="relative mx-auto w-full min-h-[200px] max-w-[1300px] px-4 pb-8 pt-5 xl:px-6 max-lg:pb-10">

        <nav class="text-[13px] text-white/85" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="transition hover:text-white">Inicio</a>
            <span class="mx-1.5 text-white/50">&gt;</span>
            <span class="text-white">Productos</span>
        </nav>

        @if(count($ofertas))
            <div x-data="{
                    slide: 0,
                    total: {{ count($ofertas) }},
                    timer: null,
                    x0: 0,
                    play() { this.stop(); if (this.total > 1) this.timer = setInterval(() => this.slide = (this.slide + 1) % this.total, 5000) },
                    stop() { clearInterval(this.timer) },
                    ir(i) { this.slide = (i + this.total) % this.total; this.play() },
                    // Swipe en pantallas táctiles
                    deslizar(dx) { if (Math.abs(dx) > 40) this.ir(this.slide + (dx < 0 ? 1 : -1)) },
                 }"
                 x-init="play()" @mouseenter="stop()" @mouseleave="play()"
                 @touchstart.passive="x0 = $event.touches[0].clientX"
                 @touchend.passive="deslizar($event.changedTouches[0].clientX - x0)"
                 class="mt-4">
                {{-- La animación envuelve sólo los slides: los puntos (absolute) se posicionan respecto al contenedor relative. --}}
                <div class="anim-entrada">
                @foreach($ofertas as $i => $oferta)
                    <div x-show="slide === {{ $i }}" x-cloak
                         x-transition:enter="transition ease-out duration-400"
                         x-transition:enter-start="translate-x-3 opacity-0"
                         x-transition:enter-end="translate-x-0 opacity-100"
                         class="flex flex-col items-center gap-5 lg:flex-row lg:gap-8 lg:px-[130px]">

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
                               class="line-clamp-3 text-[18px] font-normal leading-[130%] transition hover:underline lg:block lg:h-[70px] lg:max-w-[460px]">
                                {{ $oferta['nombre'] }}
                            </a>
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
                        </div>

                        <button type="button" wire:click="agregar('{{ $oferta['codigo'] }}')"
                                class="shrink-0 cursor-pointer rounded bg-[#E11A22] px-5 py-3 text-[15px] font-bold uppercase tracking-wide text-white transition hover:bg-[#B8141B] max-sm:w-full">
                            + Agregar 
                        </button>
                    </div>
                @endforeach
                </div>

                @if(count($ofertas) > 1)
                    <div class="absolute inset-x-0 bottom-2 flex items-center justify-center gap-2">
                        @foreach($ofertas as $i => $oferta)
                            <button type="button" @click="ir({{ $i }})"
                                    class="h-[5px] rounded-full transition-all"
                                    :class="slide === {{ $i }} ? 'w-8 bg-[#E11A22]' : 'w-6 bg-white/45 hover:bg-white/70'"
                                    aria-label="Ver destacado {{ $i + 1 }}"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>
</section>
