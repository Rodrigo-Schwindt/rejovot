{{-- Ítems del carrito. En pantallas angostas la tabla conserva sus columnas y se desplaza en horizontal. --}}
<p class="anim-entrada mb-2 flex items-center gap-1.5 text-[12px] text-slate-400 lg:hidden" style="--retraso: 100">
    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7l-4 5 4 5M16 7l4 5-4 5"/></svg>
    Deslizá para ver todas las columnas
</p>
{{-- relative: los sr-only (absolute) del encabezado quedan dentro del recorte y no ensanchan la página en mobile. --}}
<div class="anim-entrada relative overflow-x-auto bg-white max-lg:overscroll-x-contain max-lg:[-webkit-overflow-scrolling:touch]" style="--retraso: 140">
    <table class="w-full min-w-[820px] border-collapse">
        <thead>
            <tr class="border-b border-slate-200 bg-[#F8F8F8] text-[17px] font-semibold text-black">
                <th class="w-[76px] px-3 py-4"><span class="sr-only">Imagen</span></th>
                <th class="w-[34%] px-3 py-4 text-left">Producto</th>
                <th class="w-[16%] px-3 py-4 text-right whitespace-nowrap">Costo / Lista</th>
                <th class="w-[16%] px-3 py-4 pr-6 text-right">Cantidad</th>
                <th class="w-[16%] px-3 py-4 text-center">Subtotal</th>
                <th class="w-[10%] px-3 py-4 text-center">Stock</th>
                <th class="w-[76px] px-3 py-4 text-center"><span class="sr-only">Acciones</span></th>
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
