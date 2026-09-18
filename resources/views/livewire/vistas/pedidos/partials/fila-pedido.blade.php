@php
    use App\Support\Precio;

    $estadoClase = match ($pedido['estado']) {
        'entregado' => 'text-[#1E9E3E]',
        'cancelado' => 'text-slate-500',
        'revision' => 'text-amber-600',
        default => 'text-[#E11A22]',
    };
    $estadoTexto = match ($pedido['estado']) {
        'entregado' => 'Entregado',
        'cancelado' => 'Cancelado',
        'revision' => 'En revisión',
        default => 'Pendiente',
    };
    $abiertoAca = $abierto === $pedido['numero'];
    // Entrada escalonada: cada fila aparece un poco después que la anterior.
    $retraso = min($loop->index ?? 0, 14) * 40;
@endphp

<tr wire:key="pedido-{{ $pedido['numero'] }}" class="anim-fila border-b border-slate-100 align-middle" style="--retraso: {{ $retraso }}">

    <td class="w-[110px] px-4 py-4">
        <div class="flex h-[70px] w-[70px] items-center justify-center rounded bg-slate-50">
            <svg class="h-[43px] w-[43px]" viewBox="0 0 43 43" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M28.666 7.16671H32.2493C33.1997 7.16671 34.1111 7.54424 34.7831 8.21624C35.4552 8.88825 35.8327 9.79968 35.8327 10.75V35.8334C35.8327 36.7837 35.4552 37.6952 34.7831 38.3672C34.1111 39.0392 33.1997 39.4167 32.2493 39.4167H10.7493C9.79899 39.4167 8.88755 39.0392 8.21555 38.3672C7.54354 37.6952 7.16602 36.7837 7.16602 35.8334V10.75C7.16602 9.79968 7.54354 8.88825 8.21555 8.21624C8.88755 7.54424 9.79899 7.16671 10.7493 7.16671H14.3327M21.4993 19.7084H28.666M21.4993 28.6667H28.666M14.3327 19.7084H14.3506M14.3327 28.6667H14.3506M16.1243 3.58337H26.8743C27.8639 3.58337 28.666 4.38553 28.666 5.37504V8.95837C28.666 9.94788 27.8639 10.75 26.8743 10.75H16.1243C15.1348 10.75 14.3327 9.94788 14.3327 8.95837V5.37504C14.3327 4.38553 15.1348 3.58337 16.1243 3.58337Z" stroke="#002B56" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
    </td>

    <td class="px-4 py-4 text-[15px] text-slate-700">{{ $pedido['numero'] }}</td>

    <td class="px-4 py-4 text-[15px] text-slate-700 whitespace-nowrap">{{ $pedido['fecha'] }}</td>

    <td class="px-4 py-4 text-[15px] text-slate-700 whitespace-nowrap">{{ Precio::ar($pedido['importe']) }}</td>

    <td class="px-4 py-4 text-[15px] font-medium {{ $estadoClase }}">{{ $estadoTexto }}</td>

    <td class=" py-4">
        <div class="flex flex-wrap items-center justify-end gap-3">
            <button type="button" wire:click="verDetalle('{{ $pedido['numero'] }}')"
                    wire:loading.attr="disabled" wire:target="verDetalle('{{ $pedido['numero'] }}')"
                    class="h-[44px] cursor-pointer rounded-[4px] bg-[#002B56] px-6 text-[14px] font-bold uppercase tracking-wide text-white transition hover:bg-[#0A2249] disabled:opacity-70"
                    aria-expanded="{{ $abiertoAca ? 'true' : 'false' }}">
                <span wire:loading.remove wire:target="verDetalle('{{ $pedido['numero'] }}')">{{ $abiertoAca ? 'Ocultar detalle' : 'Ver detalle' }}</span>
                <span wire:loading wire:target="verDetalle('{{ $pedido['numero'] }}')">Cargando…</span>
            </button>

            <button type="button" wire:click="recomprar('{{ $pedido['numero'] }}')"
                    wire:loading.attr="disabled" wire:target="recomprar('{{ $pedido['numero'] }}')"
                    class="h-[44px] cursor-pointer rounded-[4px] border border-[#002B56] px-6 text-[14px] font-bold uppercase tracking-wide text-[#002B56] transition hover:bg-[#002B56] hover:text-white disabled:opacity-70">
                <span wire:loading.remove wire:target="recomprar('{{ $pedido['numero'] }}')">Recomprar</span>
                <span wire:loading wire:target="recomprar('{{ $pedido['numero'] }}')">Agregando…</span>
            </button>
        </div>
    </td>
</tr>

@if($abiertoAca)
    <tr wire:key="detalle-{{ $pedido['numero'] }}" class="anim-fila border-b border-slate-100 bg-slate-50/60">
        <td colspan="6" class="px-4 py-5">
            <div class="anim-aparecer">
                @include('livewire.vistas.pedidos.partials.detalle-pedido')
            </div>
        </td>
    </tr>
@endif
