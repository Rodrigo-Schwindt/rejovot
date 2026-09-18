<div>
    {{-- Navegación del catálogo: marca → productos de esa marca → ficha. --}}

    @php
        // Cambia al filtrar, cambiar de nivel o de página: el listado se redibuja
        // completo y las filas vuelven a animar su entrada.
        $firmaListado = md5(json_encode([$marca, $buscarMarca, $q, $marca !== '' ? $productos->currentPage() : 0]));
        $objetivosCarga = 'buscarMarca, q, elegirMarca, volver, gotoPage, nextPage, previousPage';
    @endphp

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">

        <nav class="anim-entrada mb-6 flex flex-wrap items-center gap-1.5 text-[13px] text-slate-500" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="font-semibold text-slate-700 transition hover:text-[#002B56]">Inicio</a>
            <span class="text-slate-400">&gt;</span>

            @if($marca === '')
                <span>Marcas</span>
            @else
                <button type="button" wire:click="volver" class="cursor-pointer transition hover:text-[#002B56]">Marcas</button>
                <span class="text-slate-400">&gt;</span>
                <span>{{ $marca }}</span>
            @endif
        </nav>

        @if($marca !== '')
            <button type="button" wire:click="volver"
                    class="anim-entrada mb-5 inline-flex cursor-pointer items-center gap-2 text-[14px] font-semibold text-[#002B56] transition hover:text-[#0A2249]" style="--retraso: 40">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m15 19-7-7 7-7"/></svg>
                Volver a las marcas
            </button>
        @endif

        <div class="mx-auto w-full max-w-[1000px]">

            @if($marca === '')
                <div class="anim-entrada mb-5" style="--retraso: 80">
                    <label class="sr-only" for="buscar-marca">Buscar una marca</label>
                    <input id="buscar-marca" type="text" wire:model.live.debounce.300ms="buscarMarca"
                           placeholder="Buscar una marca"
                           class="h-[46px] w-full rounded-[4px] border border-slate-300 px-4 text-[15px] text-slate-700 outline-none placeholder:text-slate-400 focus:border-[#002B56]">
                </div>

                {{-- El listado se atenúa mientras se filtra y re-anima al llegar el resultado. --}}
                <div wire:key="marcas-{{ $firmaListado }}"
                     wire:loading.class="rj-cargando" wire:target="{{ $objetivosCarga }}"
                     class="rj-transicion-carga anim-entrada border-t border-slate-200" style="--retraso: 140">
                    @forelse($marcas as $item)
                        <div wire:key="marca-{{ $loop->index }}" class="anim-fila" style="--retraso: {{ 140 + min($loop->index, 14) * 35 }}">
                            @include('livewire.vistas.vehiculos.partials.fila-navegable', [
                                'titulo' => $item,
                                'accion' => "elegirMarca('" . addslashes($item) . "')",
                            ])
                        </div>
                    @empty
                        <p class="anim-aparecer px-2 py-10 text-center text-[15px] text-slate-500">
                            Ninguna marca coincide con «{{ $buscarMarca }}».
                        </p>
                    @endforelse
                </div>

            @else
                <div class="anim-entrada mb-4" style="--retraso: 80">
                    <label class="sr-only" for="buscar-producto">Buscar dentro de {{ $marca }}</label>
                    <input id="buscar-producto" type="text" wire:model.live.debounce.500ms="q"
                           placeholder="Buscar dentro de {{ $marca }}: código, descripción o código OEM"
                           class="h-[46px] w-full rounded-[4px] border border-slate-300 px-4 text-[15px] text-slate-700 outline-none placeholder:text-slate-400 focus:border-[#002B56]">
                </div>

                <p class="anim-entrada mb-4 text-[14px] text-slate-500" style="--retraso: 110">
                    {{ number_format($productos->total(), 0, ',', '.') }}
                    {{ $productos->total() === 1 ? 'producto' : 'productos' }} de {{ $marca }}
                </p>

                {{-- El listado se atenúa mientras se filtra y re-anima al llegar el resultado. --}}
                <div wire:key="productos-{{ $firmaListado }}"
                     wire:loading.class="rj-cargando" wire:target="{{ $objetivosCarga }}"
                     class="rj-transicion-carga anim-entrada border-t border-slate-200" style="--retraso: 140">
                    @forelse($productos as $producto)
                        <div wire:key="prod-{{ $producto['codigo'] }}" class="anim-fila" style="--retraso: {{ 140 + min($loop->index, 14) * 35 }}">
                            @include('livewire.vistas.vehiculos.partials.fila-navegable', [
                                'titulo' => $producto['codigo'],
                                'subtitulo' => $producto['descripcion'],
                                'href' => route('producto', ['codigo' => $producto['codigo'], 'desde' => 'vehiculos']),
                            ])
                        </div>
                    @empty
                        <p class="anim-aparecer px-2 py-10 text-center text-[15px] text-slate-500">
                            @if(trim($q) !== '')
                                Ningún producto de {{ $marca }} coincide con «{{ $q }}».
                            @else
                                Todavía no hay productos publicados de {{ $marca }}.
                            @endif
                        </p>
                    @endforelse
                </div>

                @if($productos->hasPages())
                    <div class="anim-entrada mt-6 rj-transicion-carga" style="--retraso: 200"
                         wire:loading.class="opacity-60" wire:target="{{ $objetivosCarga }}">{{ $productos->links('vendor.pagination.publico') }}</div>
                @endif
            @endif
        </div>
    </div>
</div>
