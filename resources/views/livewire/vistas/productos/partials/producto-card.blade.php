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
@endphp

{{-- h-full + mt-auto: los precios y el botón quedan a la misma altura en toda la fila. --}}
<div wire:key="card-{{ $clave }}"
     wire:click="seleccionar('{{ $producto['codigo'] }}')"
     class="flex h-full cursor-pointer flex-col rounded-[4px] border bg-white p-4 transition {{ $activo ? 'border-[#0D2B5E] shadow-[0_6px_18px_rgba(13,43,94,.12)]' : 'border-slate-200 hover:border-slate-300 hover:shadow-sm' }}">

    <div class="mb-3 flex items-start justify-between gap-2">
        <span class="flex min-w-0 flex-wrap items-center gap-2">
            <a wire:navigate href="{{ route('producto', ['codigo' => $producto['codigo']]) }}"
               onclick="event.stopPropagation()"
               class="text-[12px] font-medium text-[#2563C9] hover:underline">{{ $producto['codigo'] }}</a>
            @include('livewire.vistas.productos.partials.badge-oferta', ['producto' => $producto])
        </span>
        <span class="mt-1 block h-[13px] w-[13px] shrink-0 rounded-full {{ $stockColor }}"
              title="{{ $producto['stock'] === 'verde' ? 'Con stock' : ($producto['stock'] === 'amarillo' ? 'Última unidad' : 'Sin stock') }}"></span>
    </div>

    <div class="mb-3 flex h-[150px] items-center justify-center rounded border border-slate-100 bg-white p-2">
        @include('livewire.vistas.productos.partials.producto-imagen', ['class' => 'h-full w-full', 'src' => $producto['imagen'] ?? null, 'alt' => $producto['codigo']])
    </div>

    {{-- Tres líneas fijas: los nombres del catálogo van de una a cinco. --}}
    <p class="mb-3 line-clamp-3 min-h-[54px] text-[13px] font-semibold uppercase leading-[135%] text-slate-800"
       title="{{ $producto['nombre'] }}">
        {{ $producto['nombre'] }}
    </p>

    <div class="mt-auto">
        @unless($mostrador)
            <p class="text-[13px] text-slate-500">
                Tu precio <span class="font-semibold text-slate-700">{{ Precio::ar($producto['costo']) }}</span>
                @if($producto['lista'] > $producto['costo'])
                    <span class="ml-1 text-slate-400 line-through">{{ Precio::ar($producto['lista'], false) }}</span>
                @endif
            </p>
        @endunless

        <p class="mt-1 text-[18px] font-bold text-slate-900">{{ Precio::ar($producto['precio_venta'] * $cantidad) }}</p>
        <p class="text-[12px] text-slate-400">(Markup {{ $producto['markup'] }}%)</p>

        <div class="mt-4 flex items-center gap-2" onclick="event.stopPropagation()">
            <label class="sr-only" for="cant-card-{{ $clave }}">Cantidad de {{ $producto['codigo'] }}</label>
            <input id="cant-card-{{ $clave }}" type="number" min="1" step="1"
                   wire:model.live="cantidades.{{ $clave }}"
                   class="h-[38px] w-[68px] rounded border border-slate-300 px-2 text-[14px] text-slate-700 outline-none focus:border-[#0D2B5E]">

            <button type="button" wire:click="agregar('{{ $producto['codigo'] }}')"
                    @disabled(! $puedeOperar)
                    title="{{ $puedeOperar ? '' : $motivoBloqueo }}"
                    class="h-[38px] flex-1 rounded border text-[13px] font-bold uppercase transition {{ $puedeOperar ? 'cursor-pointer border-[#0D2B5E] text-[#0D2B5E] hover:bg-[#0D2B5E] hover:text-white' : 'cursor-not-allowed border-slate-200 text-slate-300' }}">
                + Agregar
            </button>
        </div>
    </div>
</div>
