@php
    use App\Livewire\Vistas\Productos\ProductosPage;
    use App\Support\Precio;

    $producto = $item['producto'];
    $clave = ProductosPage::clave($producto['codigo']);
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

<tr wire:key="carrito-{{ $clave }}" class="border-b border-slate-100 align-middle last:border-b-0">

    <td class="w-[70px] px-3 py-4">
        <div class="flex h-[50px] w-[50px] items-center justify-center rounded border border-slate-200 bg-white">
            @include('livewire.vistas.productos.partials.producto-imagen', ['class' => 'h-10 w-10', 'src' => $producto['imagen'] ?? null, 'alt' => $producto['codigo']])
        </div>
    </td>

    <td class="px-3 py-4">
        <span class="block text-[12px] font-medium text-[#2563C9]">{{ $producto['codigo'] }}</span>
        <span class="mt-0.5 block max-w-[300px] text-[13px] font-semibold uppercase leading-[135%] text-slate-800">
            {{ $producto['nombre'] }}
        </span>
    </td>

    <td class="px-3 py-4 text-right whitespace-nowrap">
        <span class="block text-[14px] font-semibold text-slate-800">{{ Precio::ar($producto['costo']) }}</span>
        @if($producto['lista'] > $producto['costo'])
            <span class="block text-[12px] text-slate-400 line-through">{{ Precio::ar($producto['lista'], false) }}</span>
        @endif
    </td>

    <td class="px-3 py-4 text-center">
        <label class="sr-only" for="cant-carrito-{{ $clave }}">Cantidad de {{ $producto['codigo'] }}</label>
        <input id="cant-carrito-{{ $clave }}" type="number" min="1" step="1"
               wire:model.live="cantidades.{{ $clave }}"
               class="h-[38px] w-[68px] rounded border border-slate-300 px-2 text-[14px] text-slate-700 outline-none focus:border-[#0D2B5E]">
    </td>

    <td class="px-3 py-4 text-right whitespace-nowrap text-[14px] font-semibold text-slate-800">
        {{ Precio::ar($item['subtotal']) }}
    </td>

    <td class="px-3 py-4 text-center">
        <span class="mx-auto block h-[13px] w-[13px] rounded-full {{ $stockColor }}" title="{{ $stockTexto }}"></span>
        <span class="sr-only">{{ $stockTexto }}</span>
    </td>

    <td class="px-3 py-4 text-center">
        <button type="button" wire:click="quitar('{{ $producto['codigo'] }}')"
                wire:confirm="¿Quitar {{ $producto['codigo'] }} del carrito?"
                class="inline-flex h-[38px] w-[42px] cursor-pointer items-center justify-center rounded border border-[#0D2B5E] text-[#0D2B5E] transition hover:bg-[#0D2B5E] hover:text-white"
                title="Quitar del carrito" aria-label="Quitar {{ $producto['codigo'] }} del carrito">
            <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m19 7-.87 12.14A2 2 0 0 1 16.14 21H7.86a2 2 0 0 1-2-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/>
            </svg>
        </button>
    </td>
</tr>
