@php
    use App\Livewire\Vistas\Productos\ProductosPage;
    use App\Support\Precio;

    $clave = ProductosPage::clave($producto['codigo']);
    $cantidad = max(1, (int) ($cantidades[$clave] ?? 1));
    $activo = $seleccionado === $producto['codigo'];
    $stockColor = match ($producto['stock']) {
        'verde' => 'bg-[#2FBF4B]',
        'amarillo' => 'bg-[#F2C438]',
        default => 'bg-[#E11A22]',
    };
    $stockTitulo = match ($producto['stock']) {
        'verde' => 'Con stock',
        'amarillo' => 'Última unidad',
        default => 'Sin stock',
    };
    // Entrada escalonada: cada card aparece un poco después que la anterior.
    $retraso = min($loop->index ?? 0, 11) * 45;
@endphp

{{-- h-full + mt-auto: los precios y el botón quedan a la misma altura en toda la fila. --}}
{{-- En mobile, al tocar la card se abre la ficha como panel inferior (ver panel-detalle). --}}
<div wire:key="card-{{ $clave }}"
     wire:click="seleccionar('{{ $producto['codigo'] }}')"
     x-data @click="$dispatch('abrir-ficha')"
     style="--retraso: {{ $retraso }}"
     class="anim-entrada relative flex h-full cursor-pointer flex-col overflow-hidden rounded-[6px] border bg-white transition {{ $activo ? 'border-[#002B56] shadow-[0_6px_18px_rgba(13,43,94,.18)]' : 'border-slate-200 shadow-[0_2px_10px_rgba(0,0,0,.06)] hover:shadow-[0_6px_18px_rgba(13,43,94,.12)]' }}">

    @if(! empty($producto['oferta']))
        <span class="absolute left-0 top-0 rounded-br-[6px] bg-[#002B56] px-4 py-2 text-[12px] font-bold uppercase tracking-wide text-white">
            Oferta
        </span>
    @endif

    <div class="flex h-[288px] w-full items-center justify-center max-sm:h-[220px] {{ ! empty($producto['imagen']) ? 'cursor-zoom-in' : '' }}"
         x-data @click.stop="$dispatch('abrir-visor', { imagenes: @js($producto['imagenes'] ?? array_filter([$producto['imagen'] ?? null])), titulo: @js($producto['codigo']) })">
        @include('livewire.vistas.productos.partials.producto-imagen', ['class' => 'h-full w-full', 'src' => $producto['imagen'] ?? null, 'alt' => $producto['codigo']])
    </div>

    <div class="flex flex-1 flex-col px-5 pb-[20px]">
        <a wire:navigate href="{{ route('producto', ['codigo' => $producto['codigo']]) }}"
           onclick="event.stopPropagation()"
           class="mt-[16px] block text-[12px] font-bold text-[#002B56] hover:underline">{{ $producto['codigo'] }}</a>

        <span class="mt-1 block" onclick="event.stopPropagation()">
            @include('livewire.vistas.productos.partials.badge-relacion', ['producto' => $producto])
        </span>

        {{-- Tres líneas fijas: los nombres del catálogo van de una a cinco. --}}
        {{-- Con la etiqueta de alternativo o accesorio van dos, así la fila no se ensancha. --}}
        @php $conEtiqueta = ! empty($producto['relacion']); @endphp
        <a wire:navigate href="{{ route('producto', ['codigo' => $producto['codigo']]) }}"
           onclick="event.stopPropagation()"
           class="mt-[16px] text-[16px] font-bold uppercase leading-[140%] text-black transition hover:text-[#002B56] {{ $conEtiqueta ? 'line-clamp-2 min-h-[47px]' : 'line-clamp-3 min-h-[70px]' }}"
           title="{{ $producto['nombre'] }}">
            {{ $producto['nombre'] }}
        </a>

        <div class="mt-auto">
            <dl class="mt-[10px] space-y-1 text-[14px] text-black ">
                <div class="flex items-baseline justify-between gap-3">
                    <dt>Precio Lista</dt>
                    <dd class="font-semibold">{{ Precio::ar($producto['lista']) }}</dd>
                </div>
                @unless($mostrador)
                    <div class="flex items-baseline justify-between gap-3">
                        <dt>Precio con descuento</dt>
                        <dd class="font-semibold">{{ Precio::ar($producto['costo']) }}</dd>
                    </div>
                @endunless
                <div class="flex items-baseline justify-between gap-3">
                    <dt>Precio Venta</dt>
                    <dd class="font-semibold">{{ Precio::ar($producto['precio_venta'] * $cantidad) }}</dd>
                </div>
            </dl>

            <div class="mt-5 flex items-end justify-between gap-3">
                <span class="mb-2 flex items-center gap-2 text-[14px] text-black" title="{{ $stockTitulo }}">
                    <span class="block h-[14px] w-[14px] shrink-0 rounded-full {{ $stockColor }}"></span>
                    Stock
                </span>

                <div class="flex items-center gap-3" onclick="event.stopPropagation()">
                    <label class="sr-only" for="cant-card-{{ $clave }}">Cantidad de {{ $producto['codigo'] }}</label>
                    {{-- Flechas propias: las del navegador sólo aparecen al pasar el mouse. --}}
                    <div class="flex h-[46px] w-[70px] items-center rounded-[6px] border border-slate-300 focus-within:border-[#002B56]"
                         x-data="{ paso(n) { const i = $refs.cant; i.stepUp(n); i.dispatchEvent(new Event('input', { bubbles: true })) } }">
                        <input id="cant-card-{{ $clave }}" type="number" min="1" step="1" x-ref="cant"
                               wire:model.live="cantidades.{{ $clave }}"
                               class="cantidad h-full w-full min-w-0 rounded-l-[6px] bg-transparent pl-3 text-[15px] text-slate-800 outline-none">
                        <div class="flex h-full shrink-0 flex-col justify-center gap-[3px] pr-2">
                            <button type="button" @click="paso(1)" class="cursor-pointer" aria-label="Sumar uno">
                                <svg width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M5 0 9.33 5.25H.67L5 0Z" fill="black"/></svg>
                            </button>
                            <button type="button" @click="paso(-1)" class="cursor-pointer" aria-label="Restar uno">
                                <svg width="10" height="6" viewBox="0 0 10 6" fill="none"><path d="M5 6 .67.75h8.66L5 6Z" fill="black"/></svg>
                            </button>
                        </div>
                    </div>

                    <button type="button" wire:click="agregar('{{ $producto['codigo'] }}')"
                            @disabled(! $puedeOperar)
                            title="{{ $puedeOperar ? 'Agregar al carrito' : $motivoBloqueo }}"
                            aria-label="Agregar {{ $producto['codigo'] }} al carrito"
                            class="flex h-[46px] w-[56px] items-center justify-center gap-0.5 rounded-[6px] border transition {{ $puedeOperar ? 'cursor-pointer border-[#002B56] text-[#002B56] hover:bg-[#002B56] hover:text-white' : 'cursor-not-allowed border-slate-200 text-slate-300' }}">
                        <span class="text-[18px] font-bold leading-none">+</span>
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l3-7H5.4M7 13 5.4 5M7 13l-1.3 2.6A1 1 0 0 0 6.6 17H19"/>
                            <circle cx="9" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
