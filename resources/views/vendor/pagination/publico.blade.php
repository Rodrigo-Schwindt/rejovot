@php
    /**
     * Paginación del sitio público. Usa wire:click en vez de links: el listado
     * se actualiza sin recargar y vuelve al inicio del catálogo.
     */
    $volverArriba = "document.getElementById('catalogo')?.scrollIntoView({ behavior: 'smooth', block: 'start' })";
    $boton = 'inline-flex h-[42px] min-w-[42px] items-center justify-center rounded-[4px] border px-3 text-[14px] font-semibold transition';
    $inactivo = 'border-slate-300 text-slate-500 cursor-not-allowed';
    $normal = 'border-slate-300 text-slate-700 cursor-pointer hover:border-[#002B56] hover:text-[#002B56]';
    $actual = 'border-[#002B56] bg-[#002B56] text-white cursor-default';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación"
         class="flex flex-col gap-4 border-t border-slate-200 pt-5 lg:flex-row lg:items-center lg:justify-between">

        <p class="whitespace-nowrap text-[13px] text-slate-500">
            Mostrando
            <span class="font-semibold text-slate-700">{{ number_format($paginator->firstItem() ?? 0, 0, ',', '.') }}</span>
            &ndash;
            <span class="font-semibold text-slate-700">{{ number_format($paginator->lastItem() ?? 0, 0, ',', '.') }}</span>
            de
            <span class="font-semibold text-slate-700">{{ number_format($paginator->total(), 0, ',', '.') }}</span>
        </p>

        <div class="flex items-center gap-1.5">

            {{-- Anterior --}}
            @if ($paginator->onFirstPage())
                <span class="{{ $boton }} {{ $inactivo }} gap-1.5" aria-disabled="true">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="m15 19-7-7 7-7"/></svg>
                    <span class="hidden uppercase tracking-wide sm:inline">Anterior</span>
                </span>
            @else
                <button type="button" wire:click="previousPage" wire:loading.attr="disabled"
                        x-on:click="{{ $volverArriba }}"
                        class="{{ $boton }} {{ $normal }} gap-1.5" rel="prev" aria-label="Página anterior">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="m15 19-7-7 7-7"/></svg>
                    <span class="hidden uppercase tracking-wide sm:inline">Anterior</span>
                </button>
            @endif

            {{-- Números: en pantallas chicas alcanza con "página X de Y" --}}
            <span class="{{ $boton }} border-slate-200 text-slate-600 sm:hidden">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            <span class="hidden items-center gap-1.5 sm:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-1 text-[14px] text-slate-400" aria-hidden="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="{{ $boton }} {{ $actual }}" aria-current="page">{{ $page }}</span>
                            @else
                                <button type="button" wire:key="pag-{{ $page }}"
                                        wire:click="gotoPage({{ $page }})" wire:loading.attr="disabled"
                                        x-on:click="{{ $volverArriba }}"
                                        class="{{ $boton }} {{ $normal }}"
                                        aria-label="Ir a la página {{ $page }}">{{ $page }}</button>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </span>

            {{-- Siguiente --}}
            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage" wire:loading.attr="disabled"
                        x-on:click="{{ $volverArriba }}"
                        class="{{ $boton }} {{ $normal }} gap-1.5" rel="next" aria-label="Página siguiente">
                    <span class="hidden uppercase tracking-wide sm:inline">Siguiente</span>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="m9 5 7 7-7 7"/></svg>
                </button>
            @else
                <span class="{{ $boton }} {{ $inactivo }} gap-1.5" aria-disabled="true">
                    <span class="hidden uppercase tracking-wide sm:inline">Siguiente</span>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="m9 5 7 7-7 7"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
