<div>
    {{-- Historial de pedidos del cliente activo, leído de Odoo. --}}

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">

        <nav class="mb-6 text-[13px] text-slate-500" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="transition hover:text-[#0D2B5E]">Inicio</a>
            <span class="mx-1.5 text-slate-400">&gt;</span>
            <span class="text-slate-700">Mis Pedidos</span>
        </nav>

        @if($cliente)
            <p class="mb-4 text-[14px] text-slate-500">
                Pedidos de <span class="font-semibold text-slate-700">{{ $cliente->name }}</span>
            </p>
        @endif

        <div class="overflow-x-auto rounded-[4px] border border-slate-200 bg-white">
            <table class="w-full min-w-[900px] border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[16px] font-semibold text-slate-800">
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
            <div class="mt-5 text-center">
                <button type="button" wire:click="verMas" wire:loading.attr="disabled"
                        class="h-[44px] cursor-pointer rounded-[4px] border border-[#0D2B5E] px-7 text-[14px] font-bold uppercase tracking-wide text-[#0D2B5E] transition hover:bg-[#0D2B5E] hover:text-white disabled:opacity-60">
                    <span wire:loading.remove wire:target="verMas">Ver más pedidos</span>
                    <span wire:loading wire:target="verMas">Buscando…</span>
                </button>
            </div>
        @endif
    </div>
</div>
