@php
    // Porcentaje del objetivo del mes ya comprado.
    $alcanzado = $objetivos['objetivo'] > 0
        ? min(100, $objetivos['comprado'] / $objetivos['objetivo'] * 100)
        : 0;
@endphp

@if($objetivos['objetivo'] > 0)
    <div class="anim-entrada mt-5" style="--retraso: 200">
        <div class="h-[8px] w-full overflow-hidden rounded-full bg-slate-200">
            {{-- anim-barra: la barra crece desde cero hasta su porcentaje al entrar. --}}
            <div class="anim-barra h-full rounded-full transition-all {{ $alcanzado >= 100 ? 'bg-[#1E9E3E]' : 'bg-[#002B56]' }}"
                 style="width: {{ number_format($alcanzado, 2, '.', '') }}%; --retraso: 350"></div>
        </div>

        <p class="mt-2 text-[13px] text-slate-700">
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
