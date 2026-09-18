@php
    /** Input de porcentaje con el sufijo "%" pegado, como en el diseño. */
    $id = $id ?? 'margen';
    $modelo = $modelo ?? '';
    $etiqueta = $etiqueta ?? 'Márgen';
@endphp

{{-- Columna completa: la etiqueta ocupa siempre dos líneas y el input queda abajo, alineado con los de la fila. --}}
<div class="flex h-full flex-col">
    <label for="{{ $id }}" title="{{ $etiqueta }}"
           class="mb-2 line-clamp-2 min-h-[48px] text-[17px] leading-[24px] text-slate-800 max-sm:min-h-0">{{ $etiqueta }}</label>

    {{-- Mientras se guarda este campo, el sufijo muestra un spinner en lugar del "%". --}}
    <div class="mt-auto flex h-[52px] w-full max-w-[270px] overflow-hidden max-sm:max-w-none rounded-[4px] border border-slate-200 bg-white focus-within:border-[#002B56] rj-transicion-carga"
         wire:loading.class="opacity-60" wire:target="{{ $modelo }}">
        <input id="{{ $id }}" type="number" min="0" max="1000" step="0.1"
               wire:model.blur="{{ $modelo }}"
               class="h-full w-full px-3 text-[16px] text-slate-800 outline-none">
        <span class="flex h-full w-[46px] shrink-0 items-center justify-center border-l border-slate-200 text-[16px] text-slate-600">
            <span wire:loading.remove wire:target="{{ $modelo }}">%</span>
            <svg wire:loading wire:target="{{ $modelo }}" style="display: none" class="h-4 w-4 animate-spin text-[#002B56]" fill="none" viewBox="0 0 24 24" aria-label="Guardando"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4z"/></svg>
        </span>
    </div>

    @error($modelo)<p class="anim-aparecer mt-1 text-[13px] text-[#E11A22]">{{ $message }}</p>@enderror
</div>
