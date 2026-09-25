@php
    $campo = 'h-[46px] w-full rounded-[4px] border border-slate-300 bg-white px-4 text-[15px] text-slate-700 outline-none placeholder:text-slate-400 focus:border-[#002B56]';
    $etiqueta = 'mb-2 block text-[19px] font-semibold text-slate-900';
    $error = 'mt-1 text-[13px] text-[#E11A22]';
@endphp

<div>
    {{-- Nuevo reclamo sobre una compra. --}}

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">

        <nav class="anim-entrada mb-6 text-[13px] text-slate-500" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="transition hover:text-[#002B56]">Inicio</a>
            <span class="mx-1.5 text-slate-400">&gt;</span>
            <a wire:navigate href="{{ route('reclamos') }}" class="transition hover:text-[#002B56]">Reclamos</a>
            <span class="mx-1.5 text-slate-400">&gt;</span>
            <span class="text-slate-700">Nuevo reclamo</span>
        </nav>

        <h1 class="anim-entrada mb-8 text-[34px] font-bold leading-[120%] text-slate-900 max-sm:text-[26px]" style="--retraso: 40">Nuevo reclamo</h1>

        <form wire:submit="enviar" class="anim-entrada" style="--retraso: 90">

            {{-- Factura --}}
            <div class="grid grid-cols-1 gap-x-8 gap-y-5 md:grid-cols-3">
                <div>
                    <label class="{{ $etiqueta }}">Fecha reclamo</label>
                    <div class="{{ $campo }} flex cursor-not-allowed items-center bg-slate-50 text-slate-500">{{ $hoy }}</div>
                </div>

                <div>
                    <label for="factura" class="{{ $etiqueta }}">N° factura</label>
                    {{-- datalist: sugiere sus facturas pero deja escribir cualquier número. --}}
                    <input id="factura" type="text" list="facturas-cliente" autocomplete="off"
                           wire:model.live.debounce.400ms="facturaNumero"
                           placeholder="{{ $facturas ? 'Elegí una factura o escribila' : 'Ingrese número de factura' }}"
                           class="{{ $campo }}">
                    <datalist id="facturas-cliente">
                        @foreach($facturas as $factura)
                            <option value="{{ $factura['numero'] }}">{{ $factura['fecha'] ? \Carbon\Carbon::parse($factura['fecha'])->format('d/m/Y') : '' }}</option>
                        @endforeach
                    </datalist>
                    @error('facturaNumero')<p class="{{ $error }}">{{ $message }}</p>@enderror
                    @if($sinOdoo)
                        <p class="mt-1 text-[13px] text-slate-500">No pudimos traer tus facturas: escribí el número a mano.</p>
                    @endif
                </div>

                <div>
                    <label for="factura-fecha" class="{{ $etiqueta }}">Fecha factura</label>
                    <input id="factura-fecha" type="date" wire:model="facturaFecha" max="{{ now()->toDateString() }}"
                           @readonly($facturaElegida)
                           class="{{ $campo }} {{ $facturaElegida ? 'cursor-not-allowed bg-slate-50 text-slate-500' : '' }}">
                    @error('facturaFecha')<p class="{{ $error }}">{{ $message }}</p>@enderror
                </div>
            </div>

            <hr class="my-8 border-slate-300">

            {{-- Artículos --}}
            @if($lineasFactura)
                <datalist id="articulos-factura">
                    @foreach($lineasFactura as $linea)
                        <option value="{{ $linea['codigo'] }}">{{ \Illuminate\Support\Str::limit($linea['nombre'], 60) }} · {{ $linea['cantidad'] }} u.</option>
                    @endforeach
                </datalist>
            @endif

            <div class="space-y-6">
                @foreach($items as $i => $item)
                    <div wire:key="item-{{ $i }}" class="relative grid grid-cols-1 gap-x-8 gap-y-5 md:grid-cols-3">
                        <div>
                            <label for="codigo-{{ $i }}" class="{{ $etiqueta }}">Código artículo</label>
                            <input id="codigo-{{ $i }}" type="text" autocomplete="off"
                                   @if($lineasFactura) list="articulos-factura" @endif
                                   wire:model="items.{{ $i }}.codigo"
                                   placeholder="{{ $lineasFactura ? 'Elegí un artículo de la factura' : 'Ingrese código de artículo' }}"
                                   class="{{ $campo }}">
                            @error("items.$i.codigo")<p class="{{ $error }}">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="cantidad-{{ $i }}" class="{{ $etiqueta }}">Cantidad</label>
                            <input id="cantidad-{{ $i }}" type="number" min="1" step="1"
                                   wire:model="items.{{ $i }}.cantidad" placeholder="Ingrese cantidad"
                                   class="{{ $campo }}">
                            @error("items.$i.cantidad")<p class="{{ $error }}">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label for="observacion-{{ $i }}" class="text-[19px] font-semibold text-slate-900">Observación</label>
                                @if(count($items) > 1)
                                    <button type="button" wire:click="quitarArticulo({{ $i }})"
                                            class="cursor-pointer text-[13px] font-semibold text-[#E11A22] hover:underline">
                                        Quitar
                                    </button>
                                @endif
                            </div>
                            <input id="observacion-{{ $i }}" type="text" wire:model="items.{{ $i }}.observacion"
                                   placeholder="Qué pasó con el artículo" class="{{ $campo }}">
                            @error("items.$i.observacion")<p class="{{ $error }}">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" wire:click="agregarArticulo"
                        class="cursor-pointer text-[19px] font-semibold text-slate-900 transition hover:text-[#002B56]">
                    + Agregar Artículo
                </button>
            </div>

            {{-- Fotos --}}
            <div class="mt-4">
                <p class="{{ $etiqueta }}">Fotos <span class="text-[14px] font-normal text-slate-500">(opcional, hasta {{ \App\Livewire\Vistas\Reclamos\NuevoReclamoPage::MAX_FOTOS }})</span></p>

                <div class="flex flex-wrap items-start gap-3">
                    @foreach($fotos as $i => $foto)
                        <div wire:key="foto-{{ $i }}" class="relative h-[96px] w-[96px] overflow-hidden rounded-[4px] border border-slate-200 bg-white">
                            @if(method_exists($foto, 'isPreviewable') && $foto->isPreviewable())
                                <img src="{{ $foto->temporaryUrl() }}" alt="" class="h-full w-full object-cover">
                            @endif
                            <button type="button" wire:click="quitarFoto({{ $i }})"
                                    class="absolute right-1 top-1 flex h-6 w-6 cursor-pointer items-center justify-center rounded-full bg-black/60 text-[14px] text-white hover:bg-black/80"
                                    aria-label="Quitar foto">×</button>
                        </div>
                    @endforeach

                    @if(count($fotos) < \App\Livewire\Vistas\Reclamos\NuevoReclamoPage::MAX_FOTOS)
                        <label class="flex h-[96px] w-[96px] cursor-pointer flex-col items-center justify-center gap-1 rounded-[4px] border border-dashed border-slate-400 text-[13px] text-slate-500 transition hover:border-[#002B56] hover:text-[#002B56]">
                            <span class="text-[24px] leading-none">+</span>
                            Agregar
                            <input type="file" wire:model="nuevasFotos" multiple accept="image/jpeg,image/png,image/webp" class="sr-only">
                        </label>
                    @endif
                </div>

                <p wire:loading wire:target="nuevasFotos" class="mt-2 text-[13px] text-slate-500">Subiendo…</p>
                @error('fotos')<p class="{{ $error }}">{{ $message }}</p>@enderror
                @error('fotos.*')<p class="{{ $error }}">{{ $message }}</p>@enderror
                @error('nuevasFotos.*')<p class="{{ $error }}">{{ $message }}</p>@enderror
            </div>

            <div class="mt-10 flex flex-wrap justify-end gap-4">
                <a wire:navigate href="{{ route('reclamos') }}"
                   class="inline-flex h-[46px] items-center rounded-[4px] border border-[#002B56] px-5 text-[15px] font-bold uppercase tracking-wide text-[#002B56] transition hover:bg-slate-50">
                    Cancelar
                </a>
                <button type="submit" wire:loading.attr="disabled" wire:target="enviar, nuevasFotos"
                        class="h-[46px] cursor-pointer rounded-[4px] bg-[#002B56] px-5 text-[15px] font-bold uppercase tracking-wide text-white transition hover:bg-[#0A2249] disabled:opacity-70">
                    <span wire:loading.remove wire:target="enviar">Enviar</span>
                    <span wire:loading wire:target="enviar">Enviando…</span>
                </button>
            </div>
        </form>
    </div>
</div>
