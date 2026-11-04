{{-- Selector de color del formulario de sliders: $campo (nombre) y $label. --}}
<div>
    <label class="f-label" for="{{ $campo }}">{{ $label }}</label>
    <div class="flex items-center gap-2">
        <input type="color" id="{{ $campo }}" name="{{ $campo }}" x-model="f.{{ $campo }}"
               class="h-[42px] w-[56px] shrink-0 cursor-pointer rounded-lg border border-slate-200 bg-white p-1">
        <span class="text-xs uppercase text-slate-500" x-text="f.{{ $campo }}"></span>
    </div>
</div>
