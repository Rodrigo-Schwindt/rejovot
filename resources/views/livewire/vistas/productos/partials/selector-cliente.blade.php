{{--
    Cliente del pedido.
    - Vendedor: elige uno de su cartera. Sin cliente no puede cargar productos.
    - Cliente logueado: es él mismo y no puede cambiarlo.
    - Visitante: ve el catálogo a precio de lista.
--}}
<section class="mb-6" x-data="{ abierto: false }" @click.outside="abierto = false" @keydown.escape.window="abierto = false">
    <h2 class="mb-3 text-[20px] font-bold text-slate-900">Cliente</h2>

    <div class="relative w-full max-w-[560px]">

        @if($clienteElegido)
            <div class="flex items-start justify-between gap-3 rounded-[4px] border border-[#0D2B5E] bg-white px-3 py-2.5">
                <div class="min-w-0">
                    <p class="truncate text-[15px] font-semibold text-slate-800">{{ $clienteElegido->name }}</p>
                    <p class="mt-0.5 text-[13px] text-slate-500">
                        @if($clienteElegido->vat) CUIT {{ $clienteElegido->vat }} @endif
                        @if($clienteElegido->price_discount > 0)
                            · <span class="font-semibold text-[#0D2B5E]">{{ rtrim(rtrim(number_format((float) $clienteElegido->price_discount, 2, ',', '.'), '0'), ',') }}% de descuento</span>
                        @endif
                        @if($clienteElegido->salesperson) · {{ $clienteElegido->salesperson->name }} @endif
                    </p>
                </div>

                @if($puedeElegirCliente)
                    <button type="button" wire:click="quitarCliente"
                            wire:confirm="Se va a vaciar el carrito. ¿Cambiar de cliente?"
                            class="shrink-0 cursor-pointer rounded p-1 text-slate-400 transition hover:text-[#E11A22]"
                            title="Cambiar de cliente" aria-label="Cambiar de cliente">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>

        @elseif($puedeElegirCliente)
            @if($totalCartera === 0)
                {{-- Vendedor sin cartera: el problema está en Odoo, no acá --}}
                <div class="rounded-[4px] border border-[#E11A22] bg-[#E11A22]/[.05] px-4 py-3">
                    <p class="text-[15px] font-semibold text-slate-800">No tenés clientes asignados</p>
                    <p class="mt-1 text-[13px] text-slate-600">
                        Para poder cargar pedidos, administración tiene que asignarte clientes en el sistema.
                    </p>
                </div>
            @else
                <label class="sr-only" for="buscar-cliente">Buscar cliente por nombre o CUIT</label>
                <button type="button" @click="abierto = !abierto"
                        class="flex h-[46px] w-full cursor-pointer items-center justify-between gap-2 rounded-[4px] border border-[#E11A22] bg-white px-3 text-left text-[14px] outline-none"
                        :aria-expanded="abierto">
                    <span class="text-slate-500">Elegí un cliente para empezar</span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="abierto && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="abierto" x-cloak x-transition.opacity.duration.100ms
                     class="absolute z-40 mt-1 w-full overflow-hidden rounded-[4px] border border-slate-200 bg-white shadow-[0_12px_32px_rgba(13,43,94,.22)]">

                    <div class="border-b border-slate-100 p-2">
                        <input id="buscar-cliente" type="text" wire:model.live.debounce.400ms="buscarCliente"
                               placeholder="Buscar por nombre o CUIT…" autocomplete="off"
                               class="h-[38px] w-full rounded border border-slate-200 px-3 text-[14px] text-slate-700 outline-none focus:border-[#0D2B5E]">
                    </div>

                    <ul class="max-h-[320px] overflow-y-auto py-1">
                        @forelse($clientes as $cliente)
                            <li wire:key="cli-{{ $cliente->id }}">
                                <button type="button" wire:click="elegirCliente({{ $cliente->id }})" @click="abierto = false"
                                        class="w-full cursor-pointer px-3 py-2 text-left transition hover:bg-slate-50">
                                    <span class="block truncate text-[14px] text-slate-800">{{ $cliente->name }}</span>
                                    <span class="block text-[12px] text-slate-400">
                                        @if($cliente->vat) CUIT {{ $cliente->vat }} @endif
                                        @if($cliente->price_discount > 0) · {{ rtrim(rtrim(number_format((float) $cliente->price_discount, 2, ',', '.'), '0'), ',') }}% @endif
                                    </span>
                                </button>
                            </li>
                        @empty
                            <li class="px-3 py-3 text-[13px] text-slate-400">Ningún cliente de tu cartera coincide con «{{ $buscarCliente }}»</li>
                        @endforelse
                    </ul>

                    <p class="border-t border-slate-100 px-3 py-2 text-[12px] text-slate-400">
                        @if(trim($buscarCliente) === '')
                            Mostrando {{ count($clientes) }} de tus {{ $totalCartera }} clientes. Escribí para buscar.
                        @else
                            {{ count($clientes) }} {{ count($clientes) === 1 ? 'resultado' : 'resultados' }} en tus {{ $totalCartera }} clientes.
                        @endif
                    </p>
                </div>
            @endif
        @else
            {{-- Visitante: puede mirar, no operar --}}
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-[4px] border border-slate-300 bg-slate-50 px-4 py-3">
                <p class="text-[14px] text-slate-600">Estás viendo precios de lista.</p>
                <a href="{{ route('ingresar') }}"
                   class="inline-flex h-[38px] items-center rounded-[4px] bg-[#0D2B5E] px-5 text-[13px] font-bold uppercase tracking-wide text-white transition hover:bg-[#0A2249]">
                    Ingresar
                </a>
            </div>
        @endif
    </div>

    @if($motivoBloqueo && $puedeElegirCliente)
        <p class="mt-2 flex items-center gap-2 text-[13px] font-medium text-[#E11A22]">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
            {{ $motivoBloqueo }}
        </p>
    @endif
</section>
