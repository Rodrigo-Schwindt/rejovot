<div>
    {{-- Listas de precios publicadas desde el admin. --}}

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">

        <nav class="mb-6 text-[13px] text-slate-500" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="font-semibold text-slate-700 transition hover:text-[#0D2B5E]">Inicio</a>
            <span class="mx-1.5 text-slate-400">&gt;</span>
            <span>Lista de precios</span>
        </nav>

        <div class="overflow-x-auto rounded-[4px] border border-slate-200 bg-white">
            <table class="w-full min-w-[820px] border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[16px] font-semibold text-slate-800">
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
