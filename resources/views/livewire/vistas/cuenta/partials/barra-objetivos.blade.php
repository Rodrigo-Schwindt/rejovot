@php
    use App\Support\Precio;

    /**
     * Objetivo de compra del mes. Sale del módulo de descuento automático de
     * Odoo: el período es mensual, no anual.
     */
    $items = [
        ['Descuento activo', Precio::ar($objetivos['descuento_activo'])],
        ['Objetivo del mes (sin impuestos)', Precio::ar($objetivos['objetivo'])],
        ['Comprado en el mes', Precio::ar($objetivos['comprado'])],
        ['Descuento acumulado', Precio::ar($objetivos['acumulado'])],
        ['Importe restante para el objetivo', Precio::ar($objetivos['restante'])],
    ];

    $alcanzado = $objetivos['objetivo'] > 0
        ? min(100, $objetivos['comprado'] / $objetivos['objetivo'] * 100)
        : 0;
@endphp

<section class="mb-6 rounded-[4px] border border-slate-200 bg-[#F4F7FB]">
    <div class="grid grid-cols-2 gap-x-6 gap-y-4 px-5 py-4 sm:grid-cols-3 lg:grid-cols-5">
        @foreach($items as [$titulo, $valor])
            <div>
                <p class="text-[13px] leading-[135%] text-slate-500">{{ $titulo }}:</p>
                <p class="mt-0.5 text-[17px] font-bold text-slate-900">{{ $valor }}</p>
            </div>
        @endforeach
    </div>

    @if($objetivos['objetivo'] > 0)
        <div class="border-t border-slate-200 px-5 py-3">
            <div class="h-[8px] w-full overflow-hidden rounded-full bg-slate-200">
                <div class="h-full rounded-full transition-all {{ $alcanzado >= 100 ? 'bg-[#1E9E3E]' : 'bg-[#0D2B5E]' }}"
                     style="width: {{ number_format($alcanzado, 2, '.', '') }}%"></div>
            </div>

            <p class="mt-2 text-[13px] text-slate-500">
                @if($alcanzado >= 100)
                    Objetivo del mes alcanzado.
                @else
                    Llevás el {{ number_format($alcanzado, 0, ',', '.') }}% del objetivo de este mes.
                @endif

                @if($objetivos['escala'])
                    Escala {{ $objetivos['escala']['categoria'] }}:
                    {{ rtrim(rtrim(number_format($objetivos['escala']['porcentaje'], 2, ',', '.'), '0'), ',') }}%
                    de descuento sobre lo comprado.
                @endif
            </p>
        </div>
    @endif
</section>
