@php
    /** Input de porcentaje con el sufijo "%" pegado, como en el diseño. */
    $id = $id ?? 'margen';
    $modelo = $modelo ?? '';
    $etiqueta = $etiqueta ?? 'Márgen';
@endphp

<div>
    <label for="{{ $id }}" class="mb-2 block text-[17px] text-slate-800">{{ $etiqueta }}</label>

    <div class="flex h-[52px] w-full max-w-[270px] overflow-hidden rounded-[4px] border border-slate-200 bg-white focus-within:border-[#0D2B5E]">
        <input id="{{ $id }}" type="number" min="0" max="1000" step="0.1"
               wire:model.blur="{{ $modelo }}"
               class="h-full w-full px-3 text-[16px] text-slate-800 outline-none">
        <span class="flex h-full w-[46px] shrink-0 items-center justify-center border-l border-slate-200 text-[16px] text-slate-600">%</span>
    </div>

    @error($modelo)<p class="mt-1 text-[13px] text-[#E11A22]">{{ $message }}</p>@enderror
</div>
