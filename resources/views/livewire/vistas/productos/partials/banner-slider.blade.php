{{-- Banner administrable (Admin > Sliders): textos e imágenes de una slide del carrusel. --}}
<div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between lg:gap-10">
    <div class="min-w-0 max-w-[680px]">
        @if($slider->etiqueta)
            <span class="mb-2.5 inline-block rounded-[3px] px-2.5 py-1 text-[12px] font-bold uppercase tracking-[.12em]"
                  style="background-color: {{ $slider->etiqueta_fondo }}; color: {{ $slider->etiqueta_color }}">
                {{ $slider->etiqueta }}
            </span>
        @endif

        <h2 class="text-[24px] font-bold leading-[125%] lg:text-[28px]" style="color: {{ $slider->titulo_color }}">
            {{ $slider->titulo }}
        </h2>

        @if($slider->descripcion)
            <p class="mt-2 whitespace-pre-line text-[14px] leading-[150%]" style="color: {{ $slider->descripcion_color }}">{{ $slider->descripcion }}</p>
        @endif
    </div>

    @if($slider->imagenes_urls)
        <div class="flex shrink-0 gap-3">
            @foreach($slider->imagenes_urls as $imagen)
                <div class="flex h-[76px] w-[76px] items-center justify-center overflow-hidden rounded-[6px] bg-white p-2 lg:h-[96px] lg:w-[96px]">
                    <img src="{{ $imagen }}" alt="" loading="lazy" class="h-full w-full object-contain">
                </div>
            @endforeach
        </div>
    @endif
</div>
