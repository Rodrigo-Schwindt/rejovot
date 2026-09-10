{{-- Listado en formato tabla --}}
<div class="overflow-x-auto rounded-[4px] border border-slate-200 bg-white">
    <table class="w-full min-w-[760px] border-collapse">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-[13px] font-semibold text-slate-600">
                <th class="w-[64px] px-3 py-3"><span class="sr-only">Imagen</span></th>
                <th class="px-3 py-3 text-left">Producto</th>
                @unless($mostrador)
                    <th class="px-3 py-3 text-right whitespace-nowrap">Tu precio / Lista</th>
                @endunless
                <th class="px-3 py-3 text-left">Cantidad</th>
                @unless($mostrador)
                    <th class="px-3 py-3 text-right">Subtotal</th>
                @endunless
                <th class="px-3 py-3 text-right whitespace-nowrap">Precio venta</th>
                <th class="px-3 py-3 text-center">Stock</th>
                <th class="px-3 py-3 text-center"><span class="sr-only">Acciones</span></th>
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
