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
@endphp

<tr wire:key="fila-{{ $clave }}"
    class="cursor-pointer border-b border-slate-100 align-middle transition {{ $activo ? 'bg-[#0D2B5E]/[.04]' : 'hover:bg-slate-50' }}"
    wire:click="seleccionar('{{ $producto['codigo'] }}')">

    <td class="w-[64px] px-3 py-3">
        <div class="flex h-[46px] w-[46px] items-center justify-center rounded border border-slate-200 bg-white">
            @include('livewire.vistas.productos.partials.producto-imagen', ['class' => 'h-9 w-9', 'src' => $producto['imagen'] ?? null, 'alt' => $producto['codigo']])
        </div>
    </td>

    <td class="px-3 py-3">
        <a wire:navigate href="{{ route('producto', ['codigo' => $producto['codigo']]) }}"
           onclick="event.stopPropagation()"
           class="block text-[12px] font-medium text-[#2563C9] hover:underline">{{ $producto['codigo'] }}</a>
        <span class="mt-0.5 block max-w-[280px] text-[13px] font-semibold uppercase leading-[135%] text-slate-800">
            {{ $producto['nombre'] }}
        </span>
        @include('livewire.vistas.productos.partials.badge-oferta', ['producto' => $producto])
    </td>

    @unless($mostrador)
        <td class="px-3 py-3 text-right whitespace-nowrap">
            <span class="block text-[14px] font-semibold text-slate-800">{{ Precio::ar($producto['costo']) }}</span>
            @if($producto['lista'] > $producto['costo'])
                <span class="block text-[12px] text-slate-400 line-through">{{ Precio::ar($producto['lista'], false) }}</span>
            @endif
        </td>
    @endunless

    <td class="px-3 py-3" onclick="event.stopPropagation()">
        <label class="sr-only" for="cant-{{ $clave }}">Cantidad de {{ $producto['codigo'] }}</label>
        <input id="cant-{{ $clave }}" type="number" min="1" step="1"
               wire:model.live="cantidades.{{ $clave }}"
               class="h-[34px] w-[62px] rounded border border-slate-300 px-2 text-[14px] text-slate-700 outline-none focus:border-[#0D2B5E]">
    </td>

    @unless($mostrador)
        <td class="px-3 py-3 text-right whitespace-nowrap text-[14px] font-semibold text-slate-800">
            {{ Precio::ar($producto['costo'] * $cantidad) }}
        </td>
    @endunless

    <td class="px-3 py-3 text-right whitespace-nowrap">
        <span class="block text-[14px] font-semibold text-slate-800">{{ Precio::ar($producto['precio_venta'] * $cantidad) }}</span>
        <span class="block text-[12px] text-slate-400">(Markup {{ $producto['markup'] }}%)</span>
    </td>

    <td class="px-3 py-3 text-center">
        <span class="mx-auto block h-[13px] w-[13px] rounded-full {{ $stockColor }}" title="{{ $stockTexto }}"></span>
        <span class="sr-only">{{ $stockTexto }}</span>
    </td>

    <td class="px-3 py-3 text-center" onclick="event.stopPropagation()">
        <button type="button" wire:click="agregar('{{ $producto['codigo'] }}')"
                @disabled(! $puedeOperar)
                class="inline-flex h-[38px] w-[46px] items-center justify-center gap-0.5 rounded border transition {{ $puedeOperar ? 'cursor-pointer border-[#0D2B5E] text-[#0D2B5E] hover:bg-[#0D2B5E] hover:text-white' : 'cursor-not-allowed border-slate-200 text-slate-300' }}"
                title="{{ $puedeOperar ? 'Agregar al carrito' : $motivoBloqueo }}"
                aria-label="Agregar {{ $producto['codigo'] }} al carrito">
            <span class="text-[13px] font-bold">+</span>
            <svg class="h-[17px] w-[17px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13 5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/>
            </svg>
        </button>
    </td>
</tr>
