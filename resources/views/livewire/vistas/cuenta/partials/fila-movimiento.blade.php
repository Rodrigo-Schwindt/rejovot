@php
    use App\Support\Precio;

    // Las filas vencidas van completas en rojo, como en el diseño.
    $texto = $movimiento['vencido'] ? 'text-[#E11A22]' : 'text-slate-700';
@endphp

<tr wire:key="mov-{{ $movimiento['tipo'] }}-{{ $movimiento['numero'] }}"
    class="border-b border-slate-100 align-middle last:border-b-0 {{ $movimiento['vencido'] ? 'bg-[#E11A22]/[.03]' : '' }}">

    <td class="px-4 py-5 text-[15px] whitespace-nowrap {{ $texto }}">{{ $movimiento['emision'] }}</td>
    <td class="px-4 py-5 text-[15px] whitespace-nowrap {{ $texto }}">{{ $movimiento['vencimiento'] }}</td>
    <td class="px-4 py-5 text-[15px] {{ $texto }}">{{ $movimiento['tipo'] }}</td>
    <td class="px-4 py-5 text-[15px] {{ $texto }}">{{ $movimiento['numero'] }}</td>
    <td class="px-4 py-5 text-[15px] whitespace-nowrap {{ $texto }}">{{ Precio::ar($movimiento['haber']) }}</td>
    <td class="px-4 py-5 text-[15px] whitespace-nowrap {{ $texto }}">{{ Precio::ar(-$movimiento['saldo']) }}</td>
    <td class="px-4 py-5 text-[15px] whitespace-nowrap {{ $texto }}">{{ Precio::ar($movimiento['importe_origen']) }}</td>
    <td class="px-4 py-5 text-[15px] whitespace-nowrap {{ $texto }}">{{ Precio::ar($movimiento['importe_bruto_origen']) }}</td>

    <td class="px-4 py-5 text-center">
        @if($movimiento['move_id'] ?? null)
            <a href="{{ route('cuenta.comprobante', $movimiento['move_id']) }}"
               class="inline-flex text-slate-700 transition hover:text-[#0D2B5E]"
               title="Descargar el comprobante {{ $movimiento['numero'] }} en PDF"
               aria-label="Descargar el comprobante {{ $movimiento['numero'] }} en PDF">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                </svg>
            </a>
        @else
            <span class="text-slate-300" title="Sin comprobante">—</span>
        @endif
    </td>
</tr>
