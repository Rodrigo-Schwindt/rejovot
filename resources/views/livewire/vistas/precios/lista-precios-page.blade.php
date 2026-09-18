<div>
    {{-- Listas de precios publicadas desde el admin. --}}

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">

        <nav class="anim-entrada mb-6 text-[13px] text-slate-500" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="font-semibold text-slate-700 transition hover:text-[#002B56]">Inicio</a>
            <span class="mx-1.5 text-slate-400">&gt;</span>
            <span>Lista de precios</span>
        </nav>

        {{-- En pantallas angostas la tabla conserva sus columnas y se desplaza en horizontal. --}}
        <p class="anim-entrada mb-2 flex items-center gap-1.5 text-[12px] text-slate-400 lg:hidden" style="--retraso: 60">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7l-4 5 4 5M16 7l4 5-4 5"/></svg>
            Deslizá para ver todas las columnas
        </p>
        {{-- relative: los sr-only (absolute) del encabezado quedan dentro del recorte y no ensanchan la página en mobile. --}}
        <div class="anim-entrada relative overflow-x-auto rounded-t-[4px] bg-white max-lg:overscroll-x-contain max-lg:[-webkit-overflow-scrolling:touch]" style="--retraso: 100">
            <table class="w-full min-w-[820px] border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-[#F8F8F8] text-[16px] font-semibold text-slate-800">
                        <th class="w-[110px] px-4 py-5"><span class="sr-only">Lista</span></th>
                        <th class="px-4 py-5 text-left">Descripción</th>
                        <th class="px-4 py-5 text-left">Formato</th>
                        <th class="px-4 py-5 text-right"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($listas as $lista)
                        @include('livewire.vistas.precios.partials.fila-lista', ['lista' => $lista])
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-14 text-center text-[14px] text-slate-500">
                                Todavía no hay listas de precios publicadas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
