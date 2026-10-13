@php
    use App\Support\Precio;

    // Las filas vencidas van completas en rojo, como en el diseño.
    $texto = $movimiento['vencido'] ? 'text-[#E11A22]' : 'text-black';
    // Entrada escalonada: cada fila aparece un poco después que la anterior.
    $retraso = 300 + min($loop->index ?? 0, 14) * 40;
    // Como en Odoo: lo pendiente con fondo de color (naranja la factura, rojizo
    // la nota de crédito); lo pagado y lo a favor, sin color.
    $estado = $movimiento['estado'] ?? 'pendiente';
    $fondo = $estado === 'pendiente' ? ($movimiento['nota_credito'] ?? false ? 'bg-[#F4A79D]' : 'bg-[#F9CB7E]') : '';
@endphp

<tr wire:key="mov-{{ $movimiento['linea'] ?? ($movimiento['tipo'] . '-' . $movimiento['numero']) }}" class="anim-fila border-b border-[#D9D9D9] align-middle {{ $fondo }}" style="--retraso: {{ $retraso }}">

    <td class="px-3 py-6 text-[17px] whitespace-nowrap {{ $texto }}">{{ $movimiento['emision'] }}</td>
    <td class="px-3 py-6 text-[17px] whitespace-nowrap {{ $texto }}">{{ $movimiento['vencimiento'] }}</td>
    <td class="px-3 py-6 text-[17px] {{ $texto }}">{{ $movimiento['tipo'] }}</td>
    <td class="px-3 py-6 text-[17px] max-lg:whitespace-nowrap {{ $texto }}">{{ $movimiento['numero'] }}</td>
    <td class="px-3 py-6 text-right text-[17px] whitespace-nowrap {{ $texto }}">{{ Precio::ar($movimiento['importe']) }}</td>
    <td class="px-3 py-6 text-right text-[17px] whitespace-nowrap {{ $texto }}">{{ Precio::ar($movimiento['pago_a_cuenta']) }}</td>
    <td class="px-3 py-6 text-right text-[17px] whitespace-nowrap {{ $movimiento['a_favor'] ? 'text-green-700' : $texto }}">
        {{ Precio::ar($movimiento['saldo']) }}
        {{-- Recibo con plata de más (el cliente pagó de más): plata del cliente. --}}
        @if($movimiento['a_favor'])
            <span class="block text-[13px] font-semibold">a favor</span>
        @endif
    </td>
    <td class="px-3 py-6 text-center">
        @switch($estado)
            @case('pagado')
                <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-green-700 px-3 py-1 text-[13px] font-semibold text-white">✔ Pagado</span>
                @break
            @case('a_favor')
                <span class="inline-flex items-center whitespace-nowrap rounded-full bg-green-100 px-3 py-1 text-[13px] font-semibold text-green-700">A favor</span>
                @break
            @default
                <span class="inline-flex items-center whitespace-nowrap rounded-full bg-[#2563C9] px-3 py-1 text-[13px] font-semibold text-white">Pendiente de pago</span>
        @endswitch
    </td>

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
            <span class="text-slate-300" title="Este comprobante no tiene PDF para descargar">—</span>
        @endif
    </td>
</tr>
