@php
    /**
     * Fila del listado: título en negrita, subtítulo opcional y chevron.
     * Se usa como botón (marcas y modelos) o como link (productos).
     */
    $titulo = $titulo ?? '';
    $subtitulo = $subtitulo ?? null;
    $accion = $accion ?? null;   // wire:click
    $href = $href ?? null;       // link
    $clases = 'flex w-full cursor-pointer items-center justify-between gap-4 border-b border-slate-200 px-2 py-5 text-left transition hover:bg-slate-50';
@endphp

<{{ $href ? 'a' : 'button' }}
    @if($href) href="{{ $href }}" wire:navigate @else type="button" wire:click="{{ $accion }}" @endif
    class="{{ $clases }}">

    {{-- min-w-0 + break-words: en pantallas angostas los códigos largos se parten en vez de desbordar. --}}
    <span class="max-lg:min-w-0 max-lg:break-words">
        <span class="block text-[19px] font-bold uppercase leading-[130%] text-slate-900">{{ $titulo }}</span>
        @if($subtitulo)
            <span class="mt-1 block text-[17px] leading-[140%] text-slate-700">{{ $subtitulo }}</span>
        @endif
    </span>

    <svg class="h-6 w-6 shrink-0 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 5 7 7-7 7"/>
    </svg>
</{{ $href ? 'a' : 'button' }}>
