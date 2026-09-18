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
    // Lo sin stock no entra en el pedido: queda esperando en el carrito.
    $sinStock = $producto['stock'] === 'rojo';
    // Entrada escalonada: cada fila aparece un poco después que la anterior.
    $retraso = 140 + min($loop->index ?? 0, 14) * 40;
@endphp

<tr wire:key="carrito-{{ $clave }}" class="anim-fila border-b border-slate-200 align-middle {{ $sinStock ? 'bg-slate-50/70' : '' }}" style="--retraso: {{ $retraso }}">

    <td class="w-[76px] px-2 py-3">
        <div class="flex h-[62px] w-[62px] items-center justify-center rounded-[4px] border border-slate-200 bg-white p-1 {{ ! empty($producto['imagen']) ? 'cursor-zoom-in' : '' }}"
             x-data @click="$dispatch('abrir-visor', { imagenes: @js($producto['imagenes'] ?? array_filter([$producto['imagen'] ?? null])), titulo: @js($producto['codigo']) })">
            @include('livewire.vistas.productos.partials.producto-imagen', ['class' => 'h-full w-full', 'src' => $producto['imagen'] ?? null, 'alt' => $producto['codigo']])
        </div>
    </td>

    <td class="px-3 py-3">
        <a wire:navigate href="{{ route('producto', ['codigo' => $producto['codigo']]) }}"
           class="block text-[13px] text-[#0D2B5E] hover:underline">{{ $producto['codigo'] }}</a>
        <a wire:navigate href="{{ route('producto', ['codigo' => $producto['codigo']]) }}"
           class="mt-0.5 max-w-[290px] text-[17px] uppercase leading-[125%] transition hover:text-[#002B56] {{ $sinStock ? 'line-clamp-2 text-slate-500' : 'line-clamp-3 text-black' }}"
           title="{{ $producto['nombre'] }}">
            {{ $producto['nombre'] }}
        </a>
        @if($sinStock)
            <span class="mt-1 inline-flex items-center gap-1 rounded bg-[#E11A22]/10 px-2 py-0.5 text-[11px] font-semibold text-[#B8141B]">
                Sin stock · queda en el carrito hasta que haya stock.
            </span>
        @endif
    </td>

    <td class="px-3 py-3 text-right whitespace-nowrap">
        <span class="block text-[17px] text-black">{{ Precio::ar($producto['costo']) }}</span>
        @if($producto['lista'] > $producto['costo'])
            <span class="block text-[14px] text-slate-500">{{ Precio::ar($producto['lista']) }}</span>
        @endif
    </td>

    <td class="px-3 py-3">
        <label class="sr-only" for="cant-carrito-{{ $clave }}">Cantidad de {{ $producto['codigo'] }}</label>
        {{-- Flechas propias: las del navegador sólo aparecen al pasar el mouse. --}}
        <div class="ml-auto mr-3 flex h-[46px] w-[66px] items-center rounded-[6px] border border-slate-300 bg-white focus-within:border-[#002B56]"
             x-data="{ paso(n) { const i = $refs.cant; i.stepUp(n); i.dispatchEvent(new Event('input', { bubbles: true })) } }">
            <input id="cant-carrito-{{ $clave }}" type="number" min="1" step="1" x-ref="cant"
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

    {{-- Se atenúa mientras Livewire recalcula la cantidad de esta fila. --}}
    <td class="rj-transicion-carga px-3 py-3 text-center whitespace-nowrap text-[17px] {{ $sinStock ? 'text-slate-400 line-through' : 'text-black' }}"
        wire:loading.class="opacity-40" wire:target="cantidades.{{ $clave }}">
        {{ Precio::ar($item['subtotal']) }}
    </td>

    <td class="px-3 py-3 text-center">
        <span class="mx-auto block h-[16px] w-[16px] rounded-full {{ $stockColor }}" title="{{ $stockTexto }}"></span>
        <span class="sr-only">{{ $stockTexto }}</span>
    </td>

    <td class="w-[76px] px-2 py-3 text-center">
        <button type="button" wire:click="quitar('{{ $producto['codigo'] }}')"
                wire:confirm="¿Quitar {{ $producto['codigo'] }} del carrito?"
                wire:loading.attr="disabled" wire:target="quitar('{{ $producto['codigo'] }}')"
                class="inline-flex h-[46px] w-[52px] cursor-pointer items-center justify-center rounded-[6px] border border-[#002B56] text-[#002B56] transition hover:bg-[#002B56] hover:text-white disabled:opacity-50"
                title="Quitar del carrito" aria-label="Quitar {{ $producto['codigo'] }} del carrito">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19 7-.87 12.14A2 2 0 0 1 16.14 21H7.86a2 2 0 0 1-2-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/>
            </svg>
        </button>
    </td>
</tr>
