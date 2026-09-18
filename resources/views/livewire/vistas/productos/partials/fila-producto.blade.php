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
    $stockTexto = match ($producto['stock']) {
        'verde' => 'Con stock',
        'amarillo' => 'Stock limitado',
        default => 'Sin stock',
    };
    // Entrada escalonada: cada fila aparece un poco después que la anterior.
    $retraso = min($loop->index ?? 0, 14) * 35;
@endphp

{{-- En mobile, al tocar la fila se abre la ficha como panel inferior (ver panel-detalle). --}}
<tr wire:key="fila-{{ $clave }}"
    x-data @click="$dispatch('abrir-ficha')"
    style="--retraso: {{ $retraso }}"
    class="anim-fila cursor-pointer border-b border-slate-200 align-middle transition {{ $activo ? 'bg-[#002B56]/[.04]' : 'hover:bg-slate-50' }}"
    wire:click="seleccionar('{{ $producto['codigo'] }}')">

    <td class="w-[76px] px-2 py-3">
        <div class="flex h-[62px] w-[62px] items-center justify-center rounded-[4px] border border-slate-200 {{ ! empty($producto['imagen']) ? 'cursor-zoom-in' : '' }}"
             x-data @click.stop="$dispatch('abrir-visor', { imagenes: @js($producto['imagenes'] ?? array_filter([$producto['imagen'] ?? null])), titulo: @js($producto['codigo']) })">
            @include('livewire.vistas.productos.partials.producto-imagen', ['class' => 'h-full w-full', 'src' => $producto['imagen'] ?? null, 'alt' => $producto['codigo']])
        </div>
    </td>

    <td class="px-3 py-3">
        <a wire:navigate href="{{ route('producto', ['codigo' => $producto['codigo']]) }}"
           onclick="event.stopPropagation()"
           class="block text-[13px] text-[#0D2B5E] hover:underline">{{ $producto['codigo'] }}</a>
        <a wire:navigate href="{{ route('producto', ['codigo' => $producto['codigo']]) }}"
           onclick="event.stopPropagation()"
           class="mt-0.5 line-clamp-3 w-[230px] max-w-[230px] text-[16px] uppercase leading-[125%] text-black transition hover:text-[#002B56]"
           title="{{ $producto['nombre'] }}">
            {{ $producto['nombre'] }}
        </a>
        @include('livewire.vistas.productos.partials.badge-oferta', ['producto' => $producto])
        @include('livewire.vistas.productos.partials.badge-relacion', ['producto' => $producto])
    </td>

    @unless($mostrador)
        <td class="px-3 py-3 text-right whitespace-nowrap">
            <span class="block text-[17px] text-black">{{ Precio::ar($producto['costo']) }}</span>
            @if($producto['lista'] > $producto['costo'])
                <span class="block text-[14px] text-slate-500">{{ Precio::ar($producto['lista']) }}</span>
            @endif
        </td>
    @endunless

    <td class="px-3 py-3" onclick="event.stopPropagation()">
        <label class="sr-only" for="cant-{{ $clave }}">Cantidad de {{ $producto['codigo'] }}</label>
        {{-- Flechas propias: las del navegador sólo aparecen al pasar el mouse. --}}
        <div class="mx-auto flex h-[46px] w-[66px] items-center rounded-[6px] border border-slate-300 bg-white focus-within:border-[#002B56]"
             x-data="{ paso(n) { const i = $refs.cant; i.stepUp(n); i.dispatchEvent(new Event('input', { bubbles: true })) } }">
            <input id="cant-{{ $clave }}" type="number" min="1" step="1" x-ref="cant"
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
    </td>

    @unless($mostrador)
        <td class="px-3 py-3 text-right whitespace-nowrap text-[17px] text-black">
            {{ Precio::ar($producto['costo'] * $cantidad) }}
        </td>
    @endunless

    <td class="px-3 py-3 text-right whitespace-nowrap">
        <span class="block text-[17px] text-black">{{ Precio::ar($producto['precio_venta'] * $cantidad) }}</span>
        <span class="block text-[13px] text-slate-500">(Markup {{ $producto['markup'] }}%)</span>
    </td>

    <td class="px-3 py-3 text-center">
        <span class="mx-auto block h-[16px] w-[16px] rounded-full {{ $stockColor }}" title="{{ $stockTexto }}"></span>
        <span class="sr-only">{{ $stockTexto }}</span>
    </td>

    <td class="w-[76px] pr-2 py-3 text-center " onclick="event.stopPropagation()">
        <button type="button" wire:click="agregar('{{ $producto['codigo'] }}')"
                @disabled(! $puedeOperar)
                class="inline-flex h-[46px]  w-[52px] items-center justify-center gap-0.5 rounded-[6px] border transition {{ $puedeOperar ? 'cursor-pointer border-[#002B56] text-[#002B56] hover:bg-[#002B56] hover:text-white' : 'cursor-not-allowed border-slate-200 text-slate-300' }}"
                title="{{ $puedeOperar ? 'Agregar al carrito' : $motivoBloqueo }}"
                aria-label="Agregar {{ $producto['codigo'] }} al carrito">
            <span class="text-[17px] font-bold leading-none">+</span>
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l3-7H5.4M7 13 5.4 5M7 13l-1.3 2.6A1 1 0 0 0 6.6 17H19"/>
                <circle cx="9" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/>
            </svg>
        </button>
    </td>
</tr>
