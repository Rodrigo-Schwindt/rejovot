{{-- Listado en formato tabla. En pantallas angostas conserva las columnas y se desplaza en horizontal. --}}
<p class="mb-2 flex items-center gap-1.5 text-[12px] text-slate-400 lg:hidden">
    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7l-4 5 4 5M16 7l4 5-4 5"/></svg>
    Deslizá para ver todas las columnas
</p>
{{-- relative: los sr-only (absolute) de la tabla quedan dentro del recorte y no ensanchan la página en mobile. --}}
<div class="relative overflow-x-auto bg-white max-lg:overscroll-x-contain max-lg:[-webkit-overflow-scrolling:touch]">
    <table class="w-full min-w-[820px] border-collapse">
        <thead>
            <tr class="border-b border-slate-200 bg-[#F8F8F8] text-[16px] font-semibold text-black">
                <th class="w-[76px] px-3 py-4"><span class="sr-only">Imagen</span></th>
                <th class="px-3 py-4 text-left">Producto</th>
                @unless($mostrador)
                    <th class="px-3 w-[230px] py-4 text-right whitespace-nowrap">Costo / Lista</th>
                @endunless
                <th class="px-3 py-4 text-center">Cantidad</th>
                @unless($mostrador)
                    <th class="px-3 py-4 text-right">Subtotal</th>
                @endunless
                <th class="px-3 py-4 text-right whitespace-nowrap">Precio venta</th>
                <th class="px-3 py-4 text-center">Stock</th>
                <th class="w-[76px] px-3 py-4 text-center"><span class="sr-only">Acciones</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse($productos as $producto)
                @include('livewire.vistas.productos.partials.fila-producto', ['producto' => $producto])
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-14 text-center text-[14px] text-slate-500">
                        No encontramos productos con esos filtros.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
