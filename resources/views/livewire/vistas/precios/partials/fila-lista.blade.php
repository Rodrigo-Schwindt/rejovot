@php
    $abiertaAca = $abierta === $lista->id;
    // Entrada escalonada: cada fila aparece un poco después que la anterior.
    $retraso = min($loop->index ?? 0, 14) * 40;
@endphp

<tr wire:key="lista-{{ $lista->id }}" class="anim-fila border-b border-slate-100 align-middle" style="--retraso: {{ $retraso }}">

    <td class="w-[110px] px-4 py-4">
        <div class="flex h-[70px] w-[70px] items-center justify-center rounded bg-slate-50">
            <svg class="h-8 w-8 text-[#002B56]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 7h.01M9 16h.01M13 12h3m-3 4h3"/>
            </svg>
        </div>
    </td>

    <td class="px-4 py-4 text-[15px] text-slate-700">
        {{ $lista->descripcion }}
        @if($lista->vigencia)
            <span class="block text-[13px] text-slate-500">{{ $lista->vigencia }}</span>
        @endif
        @if($lista->notas)
            <span class="block text-[13px] text-slate-400">{{ $lista->notas }}</span>
        @endif
    </td>

    <td class="px-4 py-4 text-[15px] text-slate-700">{{ $lista->formato_nombre }}</td>

    <td class=" py-4">
        <div class="flex flex-wrap items-center justify-end gap-3">
            {{-- Si hay PDF se abre directo; si no, se despliega la ficha. --}}
            @if($lista->puede_verse)
                <a href="{{ route('precios.ver', $lista) }}" target="_blank" rel="noopener"
                   class="inline-flex h-[44px] items-center gap-2 rounded-[4px] bg-[#002B56] px-6 text-[14px] font-bold uppercase tracking-wide text-white transition hover:bg-[#0A2249]">
                    Ver detalle
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            @else
                <button type="button" wire:click="verDetalle({{ $lista->id }})"
                        wire:loading.attr="disabled" wire:target="verDetalle({{ $lista->id }})"
                        class="h-[44px] cursor-pointer rounded-[4px] bg-[#002B56] px-6 text-[14px] font-bold uppercase tracking-wide text-white transition hover:bg-[#0A2249] disabled:opacity-70"
                        aria-expanded="{{ $abiertaAca ? 'true' : 'false' }}">
                    <span wire:loading.remove wire:target="verDetalle({{ $lista->id }})">{{ $abiertaAca ? 'Ocultar detalle' : 'Ver detalle' }}</span>
                    <span wire:loading wire:target="verDetalle({{ $lista->id }})">Cargando…</span>
                </button>
            @endif

            <a href="{{ route('precios.descargar', $lista) }}"
               class="inline-flex h-[44px] items-center rounded-[4px] border border-[#002B56] px-6 text-[14px] font-bold uppercase tracking-wide text-[#002B56] transition hover:bg-[#002B56] hover:text-white">
                Descargar
            </a>
        </div>
    </td>
</tr>

@if($abiertaAca)
    <tr wire:key="detalle-lista-{{ $lista->id }}" class="anim-fila border-b border-slate-100 bg-slate-50/60">
        <td colspan="4" class="px-4 py-5">
            <div class="anim-aparecer rounded-[4px] border border-slate-200 bg-white px-5 py-4">
                <h3 class="mb-3 text-[15px] font-bold text-slate-900">{{ $lista->descripcion }}</h3>

                <dl class="grid grid-cols-1 gap-x-8 gap-y-2 text-[14px] sm:grid-cols-2 lg:grid-cols-4">
                    <div class="flex justify-between gap-4 border-b border-slate-100 py-1.5">
                        <dt class="text-slate-500">Formato</dt>
                        <dd class="font-medium text-slate-800">{{ $lista->formato_nombre }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-slate-100 py-1.5">
                        <dt class="text-slate-500">Vigencia</dt>
                        <dd class="font-medium text-slate-800">{{ $lista->vigencia ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-slate-100 py-1.5">
                        <dt class="text-slate-500">Peso</dt>
                        <dd class="font-medium text-slate-800">{{ $lista->tamano_legible }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-slate-100 py-1.5">
                        <dt class="text-slate-500">Publicada</dt>
                        <dd class="font-medium text-slate-800">{{ $lista->updated_at?->format('d/m/Y') }}</dd>
                    </div>
                </dl>

                @if($lista->notas)
                    <p class="mt-3 text-[14px] text-slate-600">{{ $lista->notas }}</p>
                @endif

            </div>
        </td>
    </tr>
@endif
