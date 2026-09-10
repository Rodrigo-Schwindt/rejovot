{{-- Ítems del carrito --}}
<div class="overflow-x-auto rounded-[4px] border border-slate-200 bg-white">
    <table class="w-full min-w-[760px] border-collapse">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-[14px] font-semibold text-slate-700">
                <th class="w-[70px] px-3 py-4"><span class="sr-only">Imagen</span></th>
                <th class="px-3 py-4 text-left">Producto</th>
                <th class="px-3 py-4 text-right whitespace-nowrap">Tu precio / Lista</th>
                <th class="px-3 py-4 text-center">Cantidad</th>
                <th class="px-3 py-4 text-right">Subtotal</th>
                <th class="px-3 py-4 text-center">Stock</th>
                <th class="px-3 py-4 text-center"><span class="sr-only">Acciones</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                @include('livewire.vistas.carrito.partials.fila-carrito', ['item' => $item])
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-14 text-center text-[14px] text-slate-500">
                        Tu carrito está vacío.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
