@php use App\Support\Precio; @endphp

<div>
    {{-- Carrito de sesión. Al confirmar se crea el pedido en Odoo con lo que tiene stock. --}}

    @include('livewire.vistas.productos.partials.visor-imagenes')

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">

        {{-- Breadcrumb + aviso de envío bonificado --}}
        <div class="anim-entrada mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <nav class="text-[13px] text-slate-500" aria-label="Migas de pan">
                <a wire:navigate href="{{ route('home') }}" class="transition hover:text-[#002B56]">Inicio</a>
                <span class="mx-1.5 text-slate-400">&gt;</span>
                <span class="text-slate-700">Carrito</span>
            </nav>

            @if($envioElegido && $envioElegido['gratis_desde'])
                <p class="rounded-[4px] bg-[#002B56] px-5 py-3 text-[14px] text-white lg:text-[15px]">
                    Gratis si el importe del pedido <strong>con stock</strong> disponible es superior a
                    <strong>{{ Precio::arEntero($envioElegido['gratis_desde']) }}</strong>
                </p>
            @endif
        </div>

        @if($clienteActivo)
            <p class="anim-entrada mb-4 rounded-[4px] border border-[#002B56] bg-[#002B56]/[.04] px-4 py-3 text-[15px] text-slate-700" style="--retraso: 60">
                Pedido a nombre de <strong class="text-[#002B56]">{{ $clienteActivo->name }}</strong>
                @if($clienteActivo->price_discount > 0)
                    · {{ rtrim(rtrim(number_format((float) $clienteActivo->price_discount, 2, ',', '.'), '0'), ',') }}% de descuento
                @endif
            </p>
        @else
            <p class="anim-entrada mb-4 flex flex-wrap items-center justify-between gap-3 rounded-[4px] border border-[#E11A22] bg-[#E11A22]/[.05] px-4 py-3 text-[15px] text-slate-700" style="--retraso: 60">
                {{ $motivoBloqueo }}
                <a href="{{ route('productos') }}" class="text-[14px] font-bold uppercase tracking-wide text-[#002B56] underline">Elegir cliente</a>
            </p>
        @endif

        @include('livewire.vistas.carrito.partials.tabla-carrito')

        <div class="anim-entrada mt-5" style="--retraso: 200">
            <a wire:navigate href="{{ route('productos') }}"
               class="inline-flex h-[48px] items-center rounded-[4px] border border-[#002B56] px-6 text-[15px] font-bold uppercase tracking-wide text-[#002B56] transition hover:bg-[#002B56] hover:text-white max-sm:w-full max-sm:justify-center">
                Agregar más productos
            </a>
        </div>

        {{-- Información, mensaje, entrega y totales --}}
        <div class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-2">

            <div class="space-y-6">
                <div class="anim-entrada rounded-[4px] border border-slate-200 bg-white" style="--retraso: 260">
                    <h2 class="border-b border-slate-200 bg-[#F8F8F8] px-5 py-4 text-[18px] font-bold text-[#111]">Información importante</h2>
                    <ul class="space-y-2 px-5 py-5 text-[15px] leading-[150%] text-[#111]">
                        <li>- Venta sujeta a disponibilidad en stock</li>
                        <li>- Los precios se encuentran expresados en pesos</li>
                        <li>- El plazo de entrega se coordina con la empresa</li>
                        <li>- La mercadería viaja por cuenta y riesgo del destinatario</li>
                        <li>- No se aceptan devoluciones pasado los 10 días de la fecha de facturación</li>
                    </ul>
                </div>

                <div class="anim-entrada rounded-[4px] border border-slate-200 bg-white" style="--retraso: 340">
                    <h2 class="border-b border-slate-200 bg-[#F8F8F8] px-5 py-4 text-[18px] font-bold text-[#111]">Escribinos un mensaje</h2>
                    <div class="px-5 py-5 min-h-[213px] max-lg:min-h-0">
                        <label for="mensaje-pedido" class="sr-only">Mensaje para el pedido</label>
                        <textarea id="mensaje-pedido" wire:model.blur="mensaje" rows="4"
                                  placeholder="Dias especiales de entrega, cambios de domicilio, expresos, requerimientos especiales en la mercaderia, exenciones."
                                  class="w-full resize-y rounded-[4px]   text-[15px] leading-[150%] text-slate-700 outline-none placeholder:text-slate-500 focus:border-[#002B56]"></textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                @include('livewire.vistas.carrito.partials.forma-entrega')
                @include('livewire.vistas.carrito.partials.resumen-pedido')

                <div class="anim-entrada flex flex-wrap justify-end gap-3" style="--retraso: 460">
                    <button type="button" wire:click="cancelar" wire:confirm="¿Vaciar el carrito?"
                            wire:loading.attr="disabled" wire:target="cancelar"
                            class="h-[48px] cursor-pointer rounded-[4px] border border-slate-300 px-7 text-[15px] font-semibold uppercase tracking-wide text-slate-700 transition hover:bg-[#F8F8F8] disabled:opacity-70 max-sm:w-full">
                        Cancelar
                    </button>
                    <button type="button" wire:click="realizarPedido" @disabled(! $clienteActivo)
                            wire:loading.attr="disabled" wire:target="realizarPedido"
                            title="{{ $clienteActivo ? '' : $motivoBloqueo }}"
                            class="h-[48px] rounded-[4px] px-7 text-[15px] font-bold uppercase tracking-wide text-white transition disabled:opacity-70 max-sm:w-full {{ $clienteActivo ? 'cursor-pointer bg-[#002B56] hover:bg-[#0A2249]' : 'cursor-not-allowed bg-slate-300' }}">
                        <span wire:loading.remove wire:target="realizarPedido">Realizar pedido</span>
                        <span wire:loading wire:target="realizarPedido">Enviando el pedido…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
