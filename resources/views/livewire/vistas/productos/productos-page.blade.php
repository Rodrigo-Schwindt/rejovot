<div>
    {{-- Catálogo sincronizado de Odoo. El precio depende del cliente elegido. --}}

    @php
        // Cambia con cada filtro, vista o página: fuerza a redibujar el listado
        // completo y así las filas/cards vuelven a animar su entrada.
        $firmaListado = md5(json_encode([
            $q, $marca, $rubro, $codigo, $oem, $tipo, $soloOfertas,
            $vista, $mostrador, $porPagina, $paginador->currentPage(),
        ]));
        $objetivosCarga = 'q, marca, rubro, codigo, oem, tipo, soloOfertas, buscar, limpiar, cambiarVista, mostrador, porPagina, gotoPage, nextPage, previousPage';
    @endphp

    @include('livewire.vistas.productos.partials.banner-ofertas')
    @include('livewire.vistas.productos.partials.visor-imagenes')

    <div class="mx-auto w-full max-w-[1300px] px-4 py-8 xl:px-6">

        <div class="anim-entrada">
            @include('livewire.vistas.productos.partials.selector-cliente')
        </div>

        <div class="anim-entrada" style="--retraso: 80">
            @include('livewire.vistas.productos.partials.filtros')
        </div>

        <div id="catalogo" class="mt-6 grid grid-cols-1 gap-6 scroll-mt-6 lg:grid-cols-[minmax(0,1fr)_288px]">
            <div class="max-lg:min-w-0">

                <div class="anim-entrada mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" style="--retraso: 160">
                    <div class="flex items-center gap-3 text-[14px] text-slate-700">
                        <span class="{{ $vista === 'cuadricula' ? 'font-semibold text-slate-900' : '' }}">Cuadrícula</span>
                        <button type="button"
                                wire:click="cambiarVista('{{ $vista === 'lista' ? 'cuadricula' : 'lista' }}')"
                                class="relative inline-flex h-[22px] w-[42px] shrink-0 cursor-pointer items-center rounded-full bg-[#002B56]"
                                role="switch" aria-checked="{{ $vista === 'lista' ? 'true' : 'false' }}"
                                aria-label="Alternar entre cuadrícula y lista">
                            <span class="absolute left-[3px] h-[16px] w-[16px] rounded-full bg-white transition-transform duration-300 ease-out {{ $vista === 'lista' ? 'translate-x-[20px]' : '' }}"></span>
                        </button>
                        <span class="{{ $vista === 'lista' ? 'font-semibold text-slate-900' : '' }}">Lista</span>
                    </div>

                    <label class="flex cursor-pointer items-center gap-3 text-[14px] text-slate-700">
                        <span class="relative inline-flex h-[22px] w-[42px] shrink-0 items-center rounded-full bg-[#002B56]">
                            <input type="checkbox" wire:model.live="mostrador" class="peer sr-only">
                            <span class="absolute left-[3px] h-[16px] w-[16px] rounded-full bg-white transition-transform duration-300 ease-out {{ $mostrador ? 'translate-x-[20px]' : '' }}"></span>
                        </span>
                        <span class="{{ $mostrador ? 'font-semibold text-slate-900' : '' }}">Vista mostrador</span>
                    </label>
                </div>

                {{-- El listado se atenúa mientras se filtra y re-anima al llegar el resultado. --}}
                <div wire:key="listado-{{ $firmaListado }}"
                     wire:loading.class="rj-cargando" wire:target="{{ $objetivosCarga }}"
                     class="rj-transicion-carga anim-entrada" style="--retraso: 220">
                    @if($vista === 'lista')
                        @include('livewire.vistas.productos.partials.tabla-productos')
                    @else
                        @include('livewire.vistas.productos.partials.grilla-productos')
                    @endif
                </div>

                <div class="anim-entrada mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" style="--retraso: 300">
                    <p class="text-[13px] text-slate-500">
                        <span class="font-semibold text-slate-700">{{ number_format($paginador->total(), 0, ',', '.') }}</span>
                        {{ $paginador->total() === 1 ? 'producto' : 'productos' }}
                        @if($itemsCarrito > 0)
                            · <span class="font-semibold text-[#002B56]">{{ $itemsCarrito }}</span> en el carrito
                        @endif
                    </p>

                    <label class="flex items-center gap-2 text-[13px] text-slate-500">
                        Mostrar
                        <select wire:model.live="porPagina"
                                class="h-[36px] cursor-pointer rounded-[4px] border border-slate-300 px-2 text-[13px] text-slate-700 outline-none focus:border-[#002B56]">
                            @foreach([21, 51, 102] as $opcion)
                                <option value="{{ $opcion }}">{{ $opcion }} por página</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="anim-entrada mt-5 rj-transicion-carga" style="--retraso: 340"
                     wire:loading.class="opacity-60" wire:target="{{ $objetivosCarga }}">
                    {{ $paginador->onEachSide(1)->links('vendor.pagination.publico') }}
                </div>
            </div>

            @include('livewire.vistas.productos.partials.panel-detalle')
        </div>
    </div>
</div>
