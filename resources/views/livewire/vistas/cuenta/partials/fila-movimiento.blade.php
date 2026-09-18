@php
    use App\Support\Precio;

    // Las filas vencidas van completas en rojo, como en el diseño.
    $texto = $movimiento['vencido'] ? 'text-[#E11A22]' : 'text-black';
    // Entrada escalonada: cada fila aparece un poco después que la anterior.
    $retraso = 300 + min($loop->index ?? 0, 14) * 40;
@endphp

<tr wire:key="mov-{{ $movimiento['tipo'] }}-{{ $movimiento['numero'] }}" class="anim-fila border-b border-[#D9D9D9] align-middle" style="--retraso: {{ $retraso }}">

    <td class="px-3 py-6 text-[17px] whitespace-nowrap {{ $texto }}">{{ $movimiento['emision'] }}</td>
    <td class="px-3 py-6 text-[17px] whitespace-nowrap {{ $texto }}">{{ $movimiento['vencimiento'] }}</td>
    <td class="px-3 py-6 text-[17px] {{ $texto }}">{{ $movimiento['tipo'] }}</td>
    <td class="px-3 py-6 text-[17px] max-lg:whitespace-nowrap {{ $texto }}">{{ $movimiento['numero'] }}</td>
    <td class="px-3 py-6 text-right text-[17px] whitespace-nowrap {{ $texto }}">{{ Precio::ar($movimiento['haber']) }}</td>
    <td class="px-3 py-6 text-right text-[17px] whitespace-nowrap {{ $texto }}">{{ Precio::ar(-$movimiento['saldo']) }}</td>
    <td class="px-3 py-6 text-right text-[17px] whitespace-nowrap {{ $texto }}">{{ Precio::ar($movimiento['importe_origen']) }}</td>
    <td class="px-3 py-6 text-center text-[17px] whitespace-nowrap {{ $texto }}">{{ Precio::ar($movimiento['importe_bruto_origen']) }}</td>

    <td class="w-[70px] px-3 py-6 text-center">
        @if($movimiento['move_id'] ?? null)
            <a href="{{ route('cuenta.comprobante', $movimiento['move_id']) }}"
               class="inline-flex text-black transition hover:text-[#002B56]"
               title="Descargar el comprobante {{ $movimiento['numero'] }} en PDF"
               aria-label="Descargar el comprobante {{ $movimiento['numero'] }} en PDF">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                </svg>
            </a>
        @else
            <span class="text-slate-300" title="Sin comprobante">—</span>
        @endif
    </td>
</tr>
