<div>
    {{-- Navegación del catálogo: marca → productos de esa marca → ficha. --}}

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">

        <nav class="mb-6 flex flex-wrap items-center gap-1.5 text-[13px] text-slate-500" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="font-semibold text-slate-700 transition hover:text-[#0D2B5E]">Inicio</a>
            <span class="text-slate-400">&gt;</span>

            @if($marca === '')
                <span>Marcas</span>
            @else
                <button type="button" wire:click="volver" class="cursor-pointer transition hover:text-[#0D2B5E]">Marcas</button>
                <span class="text-slate-400">&gt;</span>
                <span>{{ $marca }}</span>
            @endif
        </nav>

        @if($marca !== '')
            <button type="button" wire:click="volver"
                    class="mb-5 inline-flex cursor-pointer items-center gap-2 text-[14px] font-semibold text-[#0D2B5E] transition hover:text-[#0A2249]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m15 19-7-7 7-7"/></svg>
                Volver a las marcas
            </button>
        @endif

        <div class="mx-auto w-full max-w-[1000px]">

            @if($marca === '')
                <div class="mb-5">
                    <label class="sr-only" for="buscar-marca">Buscar una marca</label>
                    <input id="buscar-marca" type="text" wire:model.live.debounce.300ms="buscarMarca"
                           placeholder="Buscar una marca"
                           class="h-[46px] w-full rounded-[4px] border border-slate-300 px-4 text-[15px] text-slate-700 outline-none placeholder:text-slate-400 focus:border-[#0D2B5E]">
                </div>

                <div class="border-t border-slate-200">
                    @forelse($marcas as $item)
                        <div wire:key="marca-{{ $loop->index }}">
                            @include('livewire.vistas.vehiculos.partials.fila-navegable', [
                                'titulo' => $item,
                                'accion' => "elegirMarca('" . addslashes($item) . "')",
                            ])
                        </div>
                    @empty
                        <p class="px-2 py-10 text-center text-[15px] text-slate-500">
                            Ninguna marca coincide con «{{ $buscarMarca }}».
                        </p>
                    @endforelse
                </div>

            @else
                <p class="mb-4 text-[14px] text-slate-500">
                    {{ number_format($productos->total(), 0, ',', '.') }}
                    {{ $productos->total() === 1 ? 'producto' : 'productos' }} de {{ $marca }}
                </p>

                <div class="border-t border-slate-200">
                    @forelse($productos as $producto)
                        <div wire:key="prod-{{ $producto['codigo'] }}">
                            @include('livewire.vistas.vehiculos.partials.fila-navegable', [
                                'titulo' => $producto['codigo'],
                                'subtitulo' => $producto['descripcion'],
                                'href' => route('producto', ['codigo' => $producto['codigo']]),
                            ])
                        </div>
                    @empty
                        <p class="px-2 py-10 text-center text-[15px] text-slate-500">
                            Todavía no hay productos publicados de {{ $marca }}.
                        </p>
                    @endforelse
                </div>

                @if($productos->hasPages())
                    <div class="mt-6">{{ $productos->links('vendor.pagination.publico') }}</div>
                @endif
            @endif
        </div>
    </div>
</div>
