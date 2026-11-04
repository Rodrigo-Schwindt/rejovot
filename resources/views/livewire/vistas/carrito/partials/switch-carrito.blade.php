{{-- Carrito principal / alternativo. Cada persona elige con cuál trabaja; los dos
     quedan guardados. Lo usan Productos y Carrito (método usarCarrito). --}}
@php
    $alternativo = $carritoActivo === \App\Services\Carrito\Carrito::ALTERNATIVO;
    // En la grilla centrada del celular (Productos) el contenedor desaparece y
    // cada parte ocupa su columna: texto | switch | texto.
    $enGrilla = $enGrilla ?? false;
@endphp

<div class="flex items-center gap-3 text-[14px] text-slate-700 {{ $enGrilla ? 'max-sm:contents' : '' }}">
    <span class="{{ $alternativo ? '' : 'font-semibold text-slate-900' }} {{ $enGrilla ? 'max-sm:justify-self-end' : '' }}">Carrito principal</span>
    <button type="button"
            wire:click="usarCarrito('{{ $alternativo ? 'principal' : 'alternativo' }}')"
            wire:loading.attr="disabled" wire:target="usarCarrito"
            class="relative inline-flex h-[22px] w-[42px] shrink-0 cursor-pointer items-center rounded-full transition-colors duration-300 {{ $alternativo ? 'bg-[#E11A22]' : 'bg-[#002B56]' }}"
            role="switch" aria-checked="{{ $alternativo ? 'true' : 'false' }}"
            aria-label="Usar el carrito alternativo">
        <span class="absolute left-[3px] h-[16px] w-[16px] rounded-full bg-white transition-transform duration-300 ease-out {{ $alternativo ? 'translate-x-[20px]' : '' }}"></span>
    </button>
    <span class="{{ $alternativo ? 'font-semibold text-[#E11A22]' : '' }}">Alternativo</span>
</div>
