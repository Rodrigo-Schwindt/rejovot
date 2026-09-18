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
@endphp

{{-- Datos del objetivo, dentro de la franja celeste del encabezado --}}
<div class="mt-5 grid grid-cols-2  gap-y-4 text-center sm:grid-cols-3 lg:grid-cols-5">
    @foreach($items as [$titulo, $valor])
        <div class="anim-entrada" style="--retraso: {{ 60 + $loop->index * 50 }}">
            <p class="text-[15px] leading-[135%] text-[#111]">{{ $titulo }}:</p>
            <p class="mt-0.5 text-[18px] font-bold text-[#111]">{{ $valor }}</p>
        </div>
    @endforeach
</div>
