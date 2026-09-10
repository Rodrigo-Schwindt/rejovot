<div>
    {{-- Catálogo sincronizado de Odoo. El precio depende del cliente elegido. --}}

    @include('livewire.vistas.productos.partials.banner-ofertas')

    <div class="mx-auto w-full max-w-[1300px] px-4 py-8 xl:px-6">

        @include('livewire.vistas.productos.partials.selector-cliente')

        @include('livewire.vistas.productos.partials.filtros')

        
        <div class="mt-6 mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3 text-[14px] text-slate-700">
                <span class="{{ $vista === 'cuadricula' ? 'font-semibold text-slate-900' : '' }}">Cuadrícula</span>
                <button type="button"
                        wire:click="cambiarVista('{{ $vista === 'lista' ? 'cuadricula' : 'lista' }}')"
                        class="relative inline-flex h-[22px] w-[42px] shrink-0 cursor-pointer items-center rounded-full transition-colors {{ $vista === 'lista' ? 'bg-[#0D2B5E]' : 'bg-slate-300' }}"
                        role="switch" aria-checked="{{ $vista === 'lista' ? 'true' : 'false' }}"
                        aria-label="Alternar entre cuadrícula y lista">
                    <span class="absolute left-[3px] h-[16px] w-[16px] rounded-full bg-white transition-transform {{ $vista === 'lista' ? 'translate-x-[20px]' : '' }}"></span>
                </button>
                <span class="{{ $vista === 'lista' ? 'font-semibold text-slate-900' : '' }}">Lista</span>
            </div>

            <label class="flex cursor-pointer items-center gap-3 text-[14px] text-slate-700">
                <span class="relative inline-flex h-[22px] w-[42px] shrink-0 items-center rounded-full transition-colors {{ $mostrador ? 'bg-[#0D2B5E]' : 'bg-slate-300' }}">
                    <input type="checkbox" wire:model.live="mostrador" class="peer sr-only">
                    <span class="absolute left-[3px] h-[16px] w-[16px] rounded-full bg-white transition-transform {{ $mostrador ? 'translate-x-[20px]' : '' }}"></span>
                </span>
                <span class="{{ $mostrador ? 'font-semibold text-slate-900' : '' }}">Vista mostrador</span>
            </label>
        </div>

       
        <div id="catalogo" class="grid grid-cols-1 gap-6 scroll-mt-6 lg:grid-cols-[minmax(0,1fr)_300px] xl:grid-cols-[minmax(0,1fr)_330px]">
            <div>
                @if($vista === 'lista')
                    @include('livewire.vistas.productos.partials.tabla-productos')
                @else
                    @include('livewire.vistas.productos.partials.grilla-productos')
                @endif

                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[13px] text-slate-500">
                        <span class="font-semibold text-slate-700">{{ number_format($paginador->total(), 0, ',', '.') }}</span>
                        {{ $paginador->total() === 1 ? 'producto' : 'productos' }}
                        @if($itemsCarrito > 0)
                            · <span class="font-semibold text-[#0D2B5E]">{{ $itemsCarrito }}</span> en el carrito
                        @endif
                    </p>

                    <label class="flex items-center gap-2 text-[13px] text-slate-500">
                        Mostrar
                        <select wire:model.live="porPagina"
                                class="h-[36px] cursor-pointer rounded-[4px] border border-slate-300 px-2 text-[13px] text-slate-700 outline-none focus:border-[#0D2B5E]">
                            @foreach([21, 51, 102] as $opcion)
                                <option value="{{ $opcion }}">{{ $opcion }} por página</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="mt-5" wire:loading.class="opacity-60">
                    {{ $paginador->onEachSide(1)->links('vendor.pagination.publico') }}
                </div>
            </div>

            @include('livewire.vistas.productos.partials.panel-detalle')
        </div>
    </div>
</div>
