@php
    use App\Support\Precio;

    $stockColor = match ($item['stock']) {
        'verde' => 'bg-[#2FBF4B]',
        'amarillo' => 'bg-[#F2C438]',
        default => 'bg-[#E11A22]',
    };
@endphp

<div class="relative flex flex-col rounded-[4px] border border-slate-200 bg-white p-4">

    @if($item['oferta'])
        <span class="absolute left-0 top-0 rounded-br-[4px] bg-[#0D2B5E] px-3 py-1.5 text-[12px] font-bold uppercase tracking-wide text-white">
            Oferta
        </span>
    @endif

    <a wire:navigate href="{{ route('producto', ['codigo' => $item['codigo']]) }}"
       class="mb-3 flex h-[150px] items-center justify-center rounded border border-slate-100 bg-white">
        @include('livewire.vistas.productos.partials.producto-imagen', ['class' => 'h-24 w-24', 'src' => $item['imagen'] ?? null, 'alt' => $item['codigo']])
    </a>

    <a wire:navigate href="{{ route('producto', ['codigo' => $item['codigo']]) }}" class="text-[12px] font-medium text-[#2563C9]">
        {{ $item['codigo'] }}
    </a>

    <a wire:navigate href="{{ route('producto', ['codigo' => $item['codigo']]) }}"
       class="mb-3 mt-1 block min-h-[54px] text-[13px] font-semibold uppercase leading-[135%] text-slate-800 transition hover:text-[#0D2B5E]">
        {{ $item['nombre'] }}
    </a>

    <dl class="space-y-1 text-[13px]">
        <div class="flex justify-between gap-3">
            <dt class="text-slate-500">Precio Lista</dt>
            <dd class="text-slate-700">{{ Precio::ar($item['lista']) }}</dd>
        </div>
        <div class="flex justify-between gap-3">
            <dt class="text-slate-500">Tu precio</dt>
            <dd class="text-slate-700">{{ Precio::ar($item['costo']) }}</dd>
        </div>
        <div class="flex justify-between gap-3">
            <dt class="text-slate-500">Precio Venta</dt>
            <dd class="font-semibold text-slate-900">{{ Precio::ar($item['precio_venta']) }}</dd>
        </div>
    </dl>

    <div class="mt-4 flex items-center justify-between gap-3">
        <span class="flex items-center gap-2 text-[13px] text-slate-600">
            <span class="block h-[11px] w-[11px] rounded-full {{ $stockColor }}"></span>
            Stock
        </span>

        <button type="button" wire:click="agregar('{{ $item['codigo'] }}')"
                class="inline-flex h-[38px] w-[46px] cursor-pointer items-center justify-center gap-0.5 rounded border border-[#0D2B5E] text-[#0D2B5E] transition hover:bg-[#0D2B5E] hover:text-white"
                title="Agregar al carrito" aria-label="Agregar {{ $item['codigo'] }} al carrito">
            <span class="text-[13px] font-bold">+</span>
            <svg class="h-[17px] w-[17px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13 5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/>
            </svg>
        </button>
    </div>
</div>
