@php use App\Support\Precio; @endphp

<div>
    {{-- Carrito de sesión (demo). Al conectar Odoo pasa a ser el pedido real. --}}

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">

        {{-- Breadcrumb + aviso de envío bonificado --}}
        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <nav class="text-[13px] text-slate-500" aria-label="Migas de pan">
                <a wire:navigate href="{{ route('home') }}" class="transition hover:text-[#0D2B5E]">Inicio</a>
                <span class="mx-1.5 text-slate-400">&gt;</span>
                <span class="text-slate-700">Carrito</span>
            </nav>

            @if($envioElegido && $envioElegido['gratis_desde'])
                <p class="rounded-[4px] bg-[#0D2B5E] px-5 py-3 text-[14px] text-white lg:text-[15px]">
                    Gratis si el importe del pedido <strong>con stock</strong> disponible es superior a
                    <strong>{{ Precio::arEntero($envioElegido['gratis_desde']) }}</strong>
                </p>
            @endif
        </div>

        @if($clienteActivo)
            <p class="mb-4 rounded-[4px] border border-[#0D2B5E] bg-[#0D2B5E]/[.04] px-4 py-3 text-[15px] text-slate-700">
                Pedido a nombre de <strong class="text-[#0D2B5E]">{{ $clienteActivo->name }}</strong>
                @if($clienteActivo->price_discount > 0)
                    · {{ rtrim(rtrim(number_format((float) $clienteActivo->price_discount, 2, ',', '.'), '0'), ',') }}% de descuento
                @endif
            </p>
        @else
            <p class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-[4px] border border-[#E11A22] bg-[#E11A22]/[.05] px-4 py-3 text-[15px] text-slate-700">
                {{ $motivoBloqueo }}
                <a href="{{ route('productos') }}" class="text-[14px] font-bold uppercase tracking-wide text-[#0D2B5E] underline">Elegir cliente</a>
            </p>
        @endif

        @include('livewire.vistas.carrito.partials.tabla-carrito')

        <div class="mt-5">
            <a wire:navigate href="{{ route('productos') }}"
               class="inline-flex h-[48px] items-center rounded-[4px] border border-[#0D2B5E] px-6 text-[15px] font-bold uppercase tracking-wide text-[#0D2B5E] transition hover:bg-[#0D2B5E] hover:text-white">
                Agregar más productos
            </a>
        </div>

        {{-- Información, mensaje, entrega y totales --}}
        <div class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-2">

            <div class="space-y-6">
                <div class="rounded-[4px] border border-slate-200 bg-white">
                    <h2 class="border-b border-slate-200 bg-slate-50 px-5 py-4 text-[18px] font-bold text-slate-900">Información importante</h2>
                    <ul class="space-y-2 px-5 py-5 text-[15px] leading-[150%] text-slate-700">
                        <li>- Venta sujeta a disponibilidad en stock</li>
                        <li>- Los precios se encuentran expresados en pesos</li>
                        <li>- El plazo de entrega se coordina con la empresa</li>
                        <li>- La mercadería viaja por cuenta y riesgo del destinatario</li>
                        <li>- No se aceptan devoluciones pasado los 10 días de la fecha de facturación</li>
                    </ul>
                </div>

                <div class="rounded-[4px] border border-slate-200 bg-white">
                    <h2 class="border-b border-slate-200 bg-slate-50 px-5 py-4 text-[18px] font-bold text-slate-900">Escribinos un mensaje</h2>
                    <div class="px-5 py-5">
                        <label for="mensaje-pedido" class="sr-only">Mensaje para el pedido</label>
                        <textarea id="mensaje-pedido" wire:model.blur="mensaje" rows="4"
                                  placeholder="Dias especiales de entrega, cambios de domicilio, expresos, requerimientos especiales en la mercaderia, exenciones."
                                  class="w-full resize-y rounded-[4px] border border-slate-200 p-3 text-[15px] leading-[150%] text-slate-700 outline-none placeholder:text-slate-500 focus:border-[#0D2B5E]"></textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                @include('livewire.vistas.carrito.partials.forma-entrega')
                @include('livewire.vistas.carrito.partials.resumen-pedido')

                <div class="flex flex-wrap justify-end gap-3">
                    <button type="button" wire:click="cancelar" wire:confirm="¿Vaciar el carrito?"
                            class="h-[48px] cursor-pointer rounded-[4px] border border-slate-300 px-7 text-[15px] font-semibold uppercase tracking-wide text-slate-700 transition hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="button" wire:click="realizarPedido" @disabled(! $clienteActivo)
                            title="{{ $clienteActivo ? '' : $motivoBloqueo }}"
                            class="h-[48px] rounded-[4px] px-7 text-[15px] font-bold uppercase tracking-wide text-white transition {{ $clienteActivo ? 'cursor-pointer bg-[#0D2B5E] hover:bg-[#0A2249]' : 'cursor-not-allowed bg-slate-300' }}">
                        Realizar pedido
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
