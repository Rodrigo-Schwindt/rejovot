<div>
    {{-- Catálogo sincronizado de Odoo. El precio depende del cliente elegido. --}}

    {{-- El contador del carrito sigue lo que carguen otras sesiones del mismo cliente. --}}
    @if($clienteElegido)
        <span wire:poll.10s="sincronizarCarrito" class="hidden"></span>
    @endif

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

    {{-- «fuera»: el panel de filtros ya quedó arriba, fuera de la pantalla.
         Entonces aparece la barra fija (escritorio) o el botón flotante (celular). --}}
    <div class="mx-auto w-full max-w-[1300px] px-4 py-8 xl:px-6"
         x-data="{ fuera: false }"
         x-init="new IntersectionObserver(([e]) => fuera = ! e.isIntersecting && e.boundingClientRect.top < 0, { rootMargin: '-72px 0px 0px 0px' }).observe($refs.filtros)">

        <div class="anim-entrada" style="--retraso: 80" x-ref="filtros">
            @include('livewire.vistas.productos.partials.filtros')
        </div>

        @include('livewire.vistas.productos.partials.filtros-flotantes')

        <div id="catalogo" class="mt-6 grid grid-cols-1 gap-6 scroll-mt-6 lg:grid-cols-[minmax(0,1fr)_288px]">
            <div class="max-lg:min-w-0">

                {{-- Sin sesión no hay precios: siempre cuadrícula y sin vista mostrador. --}}
                @auth('sitio')
                {{-- En el celular: grilla texto | switch | texto, con los switches en la columna del medio. --}}
                <div class="anim-entrada mb-4 grid grid-cols-[1fr_auto_1fr] items-center gap-x-3 gap-y-3 sm:flex sm:flex-row sm:items-center sm:justify-between" style="--retraso: 160">
                    <div class="flex items-center gap-3 text-[14px] text-slate-700 max-sm:contents">
                        <span class="{{ $vista === 'cuadricula' ? 'font-semibold text-slate-900' : '' }} max-sm:justify-self-end">Cuadrícula</span>
                        <button type="button"
                                wire:click="cambiarVista('{{ $vista === 'lista' ? 'cuadricula' : 'lista' }}')"
                                class="relative inline-flex h-[22px] w-[42px] shrink-0 cursor-pointer items-center rounded-full bg-[#002B56]"
                                role="switch" aria-checked="{{ $vista === 'lista' ? 'true' : 'false' }}"
                                aria-label="Alternar entre cuadrícula y lista">
                            <span class="absolute left-[3px] h-[16px] w-[16px] rounded-full bg-white transition-transform duration-300 ease-out {{ $vista === 'lista' ? 'translate-x-[20px]' : '' }}"></span>
                        </button>
                        <span class="{{ $vista === 'lista' ? 'font-semibold text-slate-900' : '' }}">Lista</span>
                    </div>

                    @include('livewire.vistas.carrito.partials.switch-carrito', ['enGrilla' => true])

                    <label class="flex cursor-pointer items-center gap-3 text-[14px] text-slate-700 max-sm:contents">
                        {{-- Gris apagado; se pinta de azul recién cuando la vista mostrador está activa. --}}
                        <span class="relative inline-flex h-[22px] w-[42px] shrink-0 cursor-pointer items-center rounded-full transition-colors duration-300 max-sm:col-start-2 {{ $mostrador ? 'bg-[#002B56]' : 'bg-slate-300' }}">
                            <input type="checkbox" wire:model.live="mostrador" class="peer sr-only">
                            <span class="absolute left-[3px] h-[16px] w-[16px] rounded-full bg-white transition-transform duration-300 ease-out {{ $mostrador ? 'translate-x-[20px]' : '' }}"></span>
                        </span>
                        <span class="{{ $mostrador ? 'font-semibold text-slate-900' : '' }}">Vista mostrador</span>
                    </label>
                </div>
                @endauth

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

                <div class="anim-entrada mt-5 flex flex-col items-center gap-3 text-center sm:flex-row sm:justify-between sm:text-left" style="--retraso: 300">
                    <p class="text-[13px] text-slate-500">
                        <span class="font-semibold text-slate-700">{{ number_format($paginador->total(), 0, ',', '.') }}</span>
                        {{ $paginador->total() === 1 ? 'producto' : 'productos' }}
                        @if($itemsCarrito > 0)
                            · <span class="font-semibold text-[#002B56]">{{ $itemsCarrito }}</span> en el carrito{{ $carritoActivo === 'alternativo' ? ' alternativo' : '' }}
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
