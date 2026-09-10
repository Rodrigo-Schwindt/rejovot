@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

        <p class="text-sm text-slate-500">
            Mostrando
            <span class="font-medium text-slate-700">{{ $paginator->firstItem() ?? 0 }}</span>
            –
            <span class="font-medium text-slate-700">{{ $paginator->lastItem() ?? 0 }}</span>
            de
            <span class="font-medium text-slate-700">{{ $paginator->total() }}</span>
        </p>

        <div class="flex items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="inline-flex h-9 w-9 cursor-default select-none items-center justify-center rounded-lg border border-slate-100 text-slate-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Anterior"
                   class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition-colors hover:border-[#0D2B5E] hover:text-[#0D2B5E]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true" class="inline-flex h-9 min-w-9 select-none items-center justify-center px-1 text-slate-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex h-9 min-w-9 select-none items-center justify-center rounded-lg bg-[#0D2B5E] px-2.5 text-sm font-semibold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-slate-200 px-2.5 text-sm text-slate-600 transition-colors hover:border-[#0D2B5E] hover:text-[#0D2B5E]">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Siguiente"
                   class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition-colors hover:border-[#0D2B5E] hover:text-[#0D2B5E]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <span aria-disabled="true" class="inline-flex h-9 w-9 cursor-default select-none items-center justify-center rounded-lg border border-slate-100 text-slate-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
