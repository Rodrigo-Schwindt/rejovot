@php use App\Support\Precio; @endphp

{{-- Formas de entrega publicadas en Odoo (delivery.carrier). --}}
<div class="anim-entrada rounded-[4px] border border-slate-200 bg-white" style="--retraso: 300">
    <h2 class="border-b border-slate-200 bg-[#F8F8F8] px-5 py-4 text-[18px] font-bold text-[#111]">Forma de entrega</h2>

    <div class="space-y-4 px-5 py-5">
        @forelse($envios as $envio)
            <label class="flex cursor-pointer items-start gap-3 text-[15px] text-[#111]">
                <input type="radio" value="{{ $envio['id'] }}" wire:model.live="entrega"
                       class="mt-0.5 h-[18px] w-[18px] shrink-0 accent-[#002B56]">
                <span>
                    {{ $envio['nombre'] }}
                    @if($envio['bonificado'])
                        <span class="ml-1 inline-flex items-center rounded-[4px] bg-[#1DB100] px-2 py-[3px] align-middle text-[12px] font-bold uppercase leading-none text-white">Gratis</span>
                    @elseif($envio['precio'] > 0)
                        <span class="text-[#111]">(costo envío {{ Precio::arEntero($envio['precio']) }})</span>
                    @endif

                    @if($envio['gratis_desde'])
                        <span class="mt-0.5 block text-[13px] text-[#111]">
                            Sin cargo en pedidos con stock de más de {{ Precio::arEntero($envio['gratis_desde']) }}
                        </span>
                    @endif
                </span>
            </label>
        @empty
            <p class="text-[14px] text-slate-500">No hay formas de entrega publicadas en el sistema.</p>
        @endforelse
    </div>
</div>
