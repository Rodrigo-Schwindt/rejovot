@php
    $campo = 'h-[52px] w-full rounded-[4px] border border-slate-200 bg-white px-3 text-[15px] text-slate-700 outline-none focus:border-[#0D2B5E]';
    $etiqueta = 'mb-2 block text-[17px] text-slate-800';
@endphp

{{-- Envío del comprobante de pago --}}
<form wire:submit.prevent="enviar" class="rounded-[4px] bg-slate-50 p-6 lg:p-8">

    <div class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
        <div>
            <label for="pago-fecha" class="{{ $etiqueta }}">Fecha*</label>
            <input id="pago-fecha" type="date" wire:model="fecha" class="{{ $campo }} @error('fecha') border-[#E11A22] @enderror">
            @error('fecha')<p class="mt-1 text-[13px] text-[#E11A22]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="pago-importe" class="{{ $etiqueta }}">Importe*</label>
            <input id="pago-importe" type="number" step="0.01" min="0" wire:model="importe" class="{{ $campo }} @error('importe') border-[#E11A22] @enderror">
            @error('importe')<p class="mt-1 text-[13px] text-[#E11A22]">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-3">
        <div>
            <label for="pago-banco" class="{{ $etiqueta }}">Banco*</label>
            <input id="pago-banco" type="text" wire:model="banco" class="{{ $campo }} @error('banco') border-[#E11A22] @enderror">
            @error('banco')<p class="mt-1 text-[13px] text-[#E11A22]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="pago-sucursal" class="{{ $etiqueta }}">Sucursal*</label>
            <input id="pago-sucursal" type="text" wire:model="sucursal" class="{{ $campo }} @error('sucursal') border-[#E11A22] @enderror">
            @error('sucursal')<p class="mt-1 text-[13px] text-[#E11A22]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="pago-facturas" class="{{ $etiqueta }}">Facturas canceladas</label>
            <input id="pago-facturas" type="text" wire:model="facturas" class="{{ $campo }}">
            @error('facturas')<p class="mt-1 text-[13px] text-[#E11A22]">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-5">
        <label for="pago-observaciones" class="{{ $etiqueta }}">Observaciones / Aclaraciones</label>
        <textarea id="pago-observaciones" wire:model="observaciones" rows="8"
                  class="w-full resize-y rounded-[4px] border border-slate-200 bg-white p-3 text-[15px] leading-[150%] text-slate-700 outline-none focus:border-[#0D2B5E]"></textarea>
        @error('observaciones')<p class="mt-1 text-[13px] text-[#E11A22]">{{ $message }}</p>@enderror
    </div>

    <div class="mt-6 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div class="w-full lg:max-w-[540px]">
            <label for="pago-archivo" class="{{ $etiqueta }}">Adjuntar archivo*</label>

            <label for="pago-archivo"
                   class="flex h-[52px] cursor-pointer items-center justify-between rounded-[4px] border bg-white px-3 text-[15px] transition hover:border-[#0D2B5E] @error('archivo') border-[#E11A22] @else border-slate-200 @enderror">
                <span class="truncate {{ $archivo ? 'text-slate-800' : 'text-slate-500' }}">
                    {{ $archivo ? $archivo->getClientOriginalName() : 'Seleccionar archivo' }}
                </span>
                <svg class="ml-3 h-6 w-6 shrink-0 text-[#0D2B5E]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15V3m0 0 4 4m-4-4L8 7M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                </svg>
            </label>
            <input id="pago-archivo" type="file" wire:model="archivo" class="sr-only" accept=".jpg,.jpeg,.png,.webp,.pdf">

            <p class="mt-1 text-[13px] text-slate-500" wire:loading wire:target="archivo">Subiendo archivo…</p>
            @error('archivo')<p class="mt-1 text-[13px] text-[#E11A22]">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center gap-6">
            <span class="whitespace-nowrap text-[17px] text-slate-700">* campos obligatorios</span>
            <button type="submit" wire:loading.attr="disabled" wire:target="enviar, archivo"
                    class="h-[52px] cursor-pointer rounded-[4px] bg-[#0D2B5E] px-8 text-[17px] font-bold uppercase tracking-wide text-white transition hover:bg-[#0A2249] disabled:opacity-60">
                <span wire:loading.remove wire:target="enviar">Enviar</span>
                <span wire:loading wire:target="enviar">Enviando…</span>
            </button>
        </div>
    </div>
</form>
