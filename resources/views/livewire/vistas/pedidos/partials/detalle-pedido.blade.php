@php use App\Support\Precio; @endphp

{{-- Detalle desplegable del pedido --}}
<div class="rounded-[4px] border border-slate-200 bg-white">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-3">
        <h3 class="text-[15px] font-bold text-slate-900">Detalle del pedido {{ $pedido['numero'] }}</h3>
        <span class="text-[13px] text-slate-500">{{ $pedido['entrega'] }} · {{ $pedido['fecha'] }}</span>
    </div>

    <table class="w-full border-collapse">
        <thead>
            <tr class="border-b border-slate-100 bg-slate-50 text-[13px] font-semibold text-slate-600">
                <th class="px-4 py-2.5 text-left">Producto</th>
                <th class="px-4 py-2.5 text-center">Cantidad</th>
                <th class="px-4 py-2.5 text-right">Precio unitario</th>
                <th class="px-4 py-2.5 text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pedido['lineas'] as $linea)
                <tr class="border-b border-slate-50 last:border-b-0">
                    <td class="px-4 py-3">
                        <span class="block text-[12px] font-medium text-[#2563C9]">{{ $linea['producto']['codigo'] }}</span>
                        <span class="block text-[13px] font-semibold uppercase leading-[135%] text-slate-800">{{ $linea['producto']['nombre'] }}</span>
                    </td>
                    <td class="px-4 py-3 text-center text-[14px] text-slate-700">{{ $linea['cantidad'] }}</td>
                    <td class="px-4 py-3 text-right text-[14px] text-slate-700 whitespace-nowrap">{{ Precio::ar($linea['producto']['costo']) }}</td>
                    <td class="px-4 py-3 text-right text-[14px] font-semibold text-slate-800 whitespace-nowrap">{{ Precio::ar($linea['subtotal']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="border-t border-slate-200">
                <td colspan="3" class="px-4 py-3 text-right text-[14px] font-semibold text-slate-700">Importe del pedido</td>
                <td class="px-4 py-3 text-right text-[16px] font-bold text-slate-900 whitespace-nowrap">{{ Precio::ar($pedido['importe']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
