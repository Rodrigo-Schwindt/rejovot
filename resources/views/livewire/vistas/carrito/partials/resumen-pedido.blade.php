@php use App\Support\Precio; @endphp

{{-- Resumen del pedido --}}
<div class="rounded-[4px] border border-slate-200 bg-white">
    <h2 class="border-b border-slate-200 bg-slate-50 px-5 py-4 text-[18px] font-bold text-slate-900">Tu pedido</h2>

    <div class="px-5 py-5">
        <div class="flex items-center justify-between py-1.5 text-[15px] text-slate-700">
            <span>Subtotal</span>
            <span class="font-medium text-slate-900">{{ Precio::ar($totales['subtotal']) }}</span>
        </div>

        <div class="flex items-center justify-between py-1.5 text-[15px] text-slate-700">
            <span class="flex items-center gap-2">
                {{ $envioElegido['nombre'] ?? 'Entrega' }}
                @if($envioBonificado)
                    <span class="rounded bg-[#2FBF4B] px-2 py-0.5 text-[11px] font-bold uppercase text-white">Gratis</span>
                @endif
            </span>
            <span class="font-medium text-slate-900">{{ Precio::ar($totales['envio']) }}</span>
        </div>

        <hr class="my-3 border-slate-200">

        <div class="flex items-center justify-between py-1.5 text-[15px] text-slate-700">
            <span>IVA {{ config('carrito.iva') }}%</span>
            <span class="font-medium text-slate-900">{{ Precio::ar($totales['iva']) }}</span>
        </div>

        <div class="mt-2 flex items-end justify-between">
            <span class="text-[24px] font-bold text-slate-900">
                Total <span class="text-[14px] font-normal text-slate-600">(IVA incluido)</span>
            </span>
            <span class="text-[24px] font-bold text-slate-900">{{ Precio::ar($totales['total']) }}</span>
        </div>
    </div>
</div>
