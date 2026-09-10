@php use App\Support\Precio; @endphp

{{-- Banner superior: breadcrumb + productos destacados desde el admin --}}
<section class="w-full bg-[#0D2B5E] text-white">
    <div class="mx-auto w-full max-w-[1300px] px-4 pb-8 pt-5 xl:px-6">

        <nav class="text-[13px] text-white/85" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="transition hover:text-white">Inicio</a>
            <span class="mx-1.5 text-white/50">&gt;</span>
            <span class="text-white">Productos</span>
        </nav>

        @if(count($ofertas))
            <div x-data="{ slide: 0, total: {{ count($ofertas) }} }" class="mt-4">
                @foreach($ofertas as $i => $oferta)
                    <div x-show="slide === {{ $i }}" x-cloak
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         class="flex flex-col items-center gap-5 lg:flex-row lg:gap-8">

                        @if($oferta['descuento'] ?? null)
                            <div class="flex h-[110px] w-[100px] shrink-0 items-center justify-center bg-[#E11A22] text-[30px] font-bold"
                                 style="clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);">
                                {{ rtrim(rtrim(number_format((float) $oferta['descuento'], 2, ',', '.'), '0'), ',') }}%
                            </div>
                        @endif

                        <div class="flex h-[70px] w-[70px] shrink-0 items-center justify-center rounded bg-white/95 p-1">
                            @include('livewire.vistas.productos.partials.producto-imagen', [
                                'class' => 'h-full w-full',
                                'src' => $oferta['imagen'] ?? null,
                                'alt' => $oferta['codigo'],
                            ])
                        </div>

                        <div class="min-w-0 flex-1 text-center lg:text-left">
                            <a wire:navigate href="{{ route('producto', ['codigo' => $oferta['codigo']]) }}"
                               class="text-[18px] font-semibold leading-[130%] transition hover:underline lg:max-w-[460px] lg:block">
                                {{ $oferta['nombre'] }}
                            </a>
                            <p class="mt-2 flex flex-wrap items-baseline justify-center gap-2 lg:justify-start">
                                @if($oferta['precio_ant'] ?? null)
                                    <span class="text-[15px] text-white/70 line-through">{{ Precio::ar($oferta['precio_ant']) }}</span>
                                @endif
                                <span class="text-[26px] font-bold">{{ Precio::ar($oferta['precio']) }}</span>
                                <span class="text-[13px] text-white/80">+ IVA</span>
                                @if($oferta['hasta'] ?? null)
                                    <span class="text-[13px] text-white/70">· hasta el {{ $oferta['hasta']->format('d/m') }}</span>
                                @endif
                            </p>
                        </div>

                        <button type="button" wire:click="agregar('{{ $oferta['codigo'] }}')"
                                class="shrink-0 cursor-pointer rounded bg-[#E11A22] px-7 py-3.5 text-[15px] font-bold uppercase tracking-wide text-white transition hover:bg-[#B8141B]">
                            + Agregar al carrito
                        </button>
                    </div>
                @endforeach

                @if(count($ofertas) > 1)
                    <div class="mt-5 flex items-center justify-center gap-2">
                        @foreach($ofertas as $i => $oferta)
                            <button type="button" @click="slide = {{ $i }}"
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
