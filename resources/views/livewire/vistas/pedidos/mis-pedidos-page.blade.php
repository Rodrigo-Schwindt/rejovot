<div>
    {{-- Historial de pedidos (PedidosDemo hasta conectar Odoo). --}}

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">

        <nav class="mb-6 text-[13px] text-slate-500" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="transition hover:text-[#0D2B5E]">Inicio</a>
            <span class="mx-1.5 text-slate-400">&gt;</span>
            <span class="text-slate-700">Mis Pedidos</span>
        </nav>

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
                                Todavía no hiciste pedidos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
