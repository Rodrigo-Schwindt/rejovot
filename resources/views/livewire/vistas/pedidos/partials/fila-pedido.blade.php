@php
    use App\Support\Precio;

    $estadoClase = match ($pedido['estado']) {
        'entregado' => 'text-[#1E9E3E]',
        'cancelado' => 'text-slate-500',
        default => 'text-[#E11A22]',
    };
    $estadoTexto = match ($pedido['estado']) {
        'entregado' => 'Entregado',
        'cancelado' => 'Cancelado',
        default => 'Pendiente',
    };
    $abiertoAca = $abierto === $pedido['numero'];
@endphp

<tr wire:key="pedido-{{ $pedido['numero'] }}" class="border-b border-slate-100 align-middle">

    <td class="w-[110px] px-4 py-4">
        <div class="flex h-[70px] w-[70px] items-center justify-center rounded bg-slate-50">
            <svg class="h-8 w-8 text-[#0D2B5E]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 7h.01M9 16h.01M13 12h3m-3 4h3"/>
            </svg>
        </div>
    </td>

    <td class="px-4 py-4 text-[15px] text-slate-700">{{ $pedido['numero'] }}</td>

    <td class="px-4 py-4 text-[15px] text-slate-700 whitespace-nowrap">{{ $pedido['fecha'] }}</td>

    <td class="px-4 py-4 text-[15px] text-slate-700 whitespace-nowrap">{{ Precio::ar($pedido['importe']) }}</td>

    <td class="px-4 py-4 text-[15px] font-medium {{ $estadoClase }}">{{ $estadoTexto }}</td>

    <td class="px-4 py-4">
        <div class="flex flex-wrap items-center justify-end gap-3">
            <button type="button" wire:click="verDetalle('{{ $pedido['numero'] }}')"
                    class="h-[44px] cursor-pointer rounded-[4px] bg-[#0D2B5E] px-6 text-[14px] font-bold uppercase tracking-wide text-white transition hover:bg-[#0A2249]"
                    aria-expanded="{{ $abiertoAca ? 'true' : 'false' }}">
                {{ $abiertoAca ? 'Ocultar detalle' : 'Ver detalle' }}
            </button>

            <button type="button" wire:click="recomprar('{{ $pedido['numero'] }}')"
                    class="h-[44px] cursor-pointer rounded-[4px] border border-[#0D2B5E] px-6 text-[14px] font-bold uppercase tracking-wide text-[#0D2B5E] transition hover:bg-[#0D2B5E] hover:text-white">
                Recomprar
            </button>
        </div>
    </td>
</tr>

@if($abiertoAca)
    <tr wire:key="detalle-{{ $pedido['numero'] }}" class="border-b border-slate-100 bg-slate-50/60">
        <td colspan="6" class="px-4 py-5">
            @include('livewire.vistas.pedidos.partials.detalle-pedido')
        </td>
    </tr>
@endif
