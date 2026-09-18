@php use App\Support\Precio; @endphp

{{-- Resumen del pedido --}}
<div class="anim-entrada rounded-[4px] border border-slate-200 bg-white" style="--retraso: 380">
    <h2 class="border-b border-slate-200 bg-[#F8F8F8] px-5 py-4 text-[18px] font-bold text-[#111]">Tu pedido</h2>

    {{-- La clave cambia con los totales: al recalcular, el bloque vuelve a aparecer con un fundido. --}}
    <div wire:key="totales-{{ md5(json_encode([$totales, $sinStock, $envioBonificado])) }}"
         class="rj-transicion-carga anim-aparecer px-5 py-5"
         wire:loading.class="opacity-40" wire:target="cantidades, quitar, entrega, cancelar">
        @if($sinStock > 0)
            <p class="mb-3 rounded bg-slate-50 px-3 py-2 text-[13px] leading-[140%] text-[#111]">
                {{ $sinStock === 1 ? 'Un producto sin stock no entra' : "{$sinStock} productos sin stock no entran" }}
                en este pedido: quedan en el carrito para cuando ingresen.
            </p>
        @endif

        <div class="flex items-center justify-between py-1.5 text-[15px] text-[#111]">
            <span>Subtotal</span>
            <span class="font-medium text-slate-900">{{ Precio::ar($totales['subtotal']) }}</span>
        </div>

        <div class="flex items-center justify-between py-1.5 text-[15px] text-[#111]">
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
