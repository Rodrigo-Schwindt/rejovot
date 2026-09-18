<div>
    {{-- Historial de pedidos del cliente activo, leído de Odoo. --}}

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">

        <nav class="anim-entrada mb-6 text-[13px] text-slate-500" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="transition hover:text-[#002B56]">Inicio</a>
            <span class="mx-1.5 text-slate-400">&gt;</span>
            <span class="text-slate-700">Mis Pedidos</span>
        </nav>

        @if($cliente)
            <p class="anim-entrada mb-4 text-[14px] text-slate-500" style="--retraso: 60">
                Pedidos de <span class="font-semibold text-slate-700">{{ $cliente->name }}</span>
            </p>
        @endif

        {{-- En pantallas angostas la tabla conserva sus columnas y se desplaza en horizontal. --}}
        <p class="anim-entrada mb-2 flex items-center gap-1.5 text-[12px] text-slate-400 lg:hidden" style="--retraso: 100">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7l-4 5 4 5M16 7l4 5-4 5"/></svg>
            Deslizá para ver todas las columnas
        </p>
        {{-- relative: los sr-only (absolute) del encabezado quedan dentro del recorte y no ensanchan la página en mobile. --}}
        <div class="anim-entrada relative overflow-x-auto rounded-t-[4px] bg-white max-lg:overscroll-x-contain max-lg:[-webkit-overflow-scrolling:touch]" style="--retraso: 140">
            <table class="w-full min-w-[900px] border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-[#F8F8F8] text-[16px] font-semibold text-slate-800">
                        <th class="w-[110px] px-4 py-5"><span class="sr-only">Pedido</span></th>
                        <th class="px-4 py-5 text-left">N° de pedido</th>
                        <th class="px-4 py-5 text-left">Fecha de compra</th>
                        <th class="px-4 py-5 text-left">Importe</th>
                        <th class="px-4 py-5 text-left">Estado</th>
                        <th class="px-4 py-5 text-right"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pedidos as $pedido)
                        @include('livewire.vistas.pedidos.partials.fila-pedido', ['pedido' => $pedido])
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-14 text-center text-[14px] text-slate-500">
                                @if($motivo)
                                    {{ $motivo }}
                                @else
                                    {{ $cliente->name }} todavía no tiene pedidos.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($hayMas)
            <div class="anim-entrada mt-5 text-center" style="--retraso: 220">
                <button type="button" wire:click="verMas" wire:loading.attr="disabled" wire:target="verMas"
                        class="h-[44px] cursor-pointer rounded-[4px] border border-[#002B56] px-7 text-[14px] font-bold uppercase tracking-wide text-[#002B56] transition hover:bg-[#002B56] hover:text-white disabled:opacity-60">
                    <span wire:loading.remove wire:target="verMas">Ver más pedidos</span>
                    <span wire:loading wire:target="verMas">Buscando…</span>
                </button>
            </div>
        @endif
    </div>
</div>
