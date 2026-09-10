@php
    /**
     * Cartel de oferta. El porcentaje sale de la tarifa de Odoo (odoo:sync-ofertas).
     * Sólo se etiqueta: hoy Odoo no baja el precio que devuelve la API.
     */
    $descuento = $producto['descuento'] ?? null;
@endphp

@if(! empty($producto['oferta']))
    <span class="inline-flex shrink-0 items-center rounded bg-[#E11A22] px-1.5 py-[3px] text-[10px] font-bold uppercase leading-none tracking-wide text-white">
        Oferta @if($descuento){{ rtrim(rtrim(number_format((float) $descuento, 2, ',', '.'), '0'), ',') }}%@endif
    </span>
@endif
