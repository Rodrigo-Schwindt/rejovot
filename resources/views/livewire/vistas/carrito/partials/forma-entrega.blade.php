@php use App\Support\Precio; @endphp

{{-- Formas de entrega publicadas en Odoo (delivery.carrier). --}}
<div class="rounded-[4px] border border-slate-200 bg-white">
    <h2 class="border-b border-slate-200 bg-slate-50 px-5 py-4 text-[18px] font-bold text-slate-900">Forma de entrega</h2>

    <div class="space-y-4 px-5 py-5">
        @forelse($envios as $envio)
            <label class="flex cursor-pointer items-start gap-3 text-[15px] text-slate-700">
                <input type="radio" value="{{ $envio['id'] }}" wire:model.live="entrega"
                       class="mt-0.5 h-[18px] w-[18px] shrink-0 accent-[#0D2B5E]">
                <span>
                    {{ $envio['nombre'] }}
                    @if($envio['precio'] > 0)
                        <span class="text-slate-500">(costo envío {{ Precio::arEntero($envio['precio']) }})</span>
                    @endif

                    @if($envio['gratis_desde'])
                        <span class="mt-0.5 block text-[13px] text-slate-500">
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
