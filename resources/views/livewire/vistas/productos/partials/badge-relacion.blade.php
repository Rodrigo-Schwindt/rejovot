@php
    /**
     * Por qué aparece este producto en la búsqueda: no coincide con lo buscado,
     * entró por ser alternativo o accesorio de otro que sí coincide.
     */
    $relacion = $producto['relacion'] ?? null;
    $de = $producto['relacion_de'] ?? null;
@endphp

@if($relacion && $de)
    <span class="inline-flex shrink-0 items-center rounded border border-[#0D2B5E]/25 bg-[#0D2B5E]/[.06] px-1.5 py-[3px] text-[10px] font-semibold uppercase leading-none tracking-wide text-[#0D2B5E]"
          title="Odoo lo tiene cargado como {{ $relacion }} de {{ $de }}">
        {{ $relacion === 'accesorio' ? 'Accesorio' : 'Alternativo' }} de {{ $de }}
    </span>
@endif
