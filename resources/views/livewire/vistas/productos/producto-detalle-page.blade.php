@php
    use App\Support\Precio;

    $mensajeWssp = rawurlencode("Hola, quiero consultar por {$producto['codigo']} - {$producto['nombre']}");
@endphp

<div>
    {{-- Ficha del producto. --}}

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">

        <nav class="mb-6 text-[13px] text-slate-500" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="font-semibold text-slate-700 transition hover:text-[#0D2B5E]">Inicio</a>
            <span class="mx-1.5 text-slate-400">&gt;</span>
            <a wire:navigate href="{{ route('productos') }}" class="transition hover:text-[#0D2B5E]">Producto</a>
            <span class="mx-1.5 text-slate-400">&gt;</span>
            <span class="text-slate-600">{{ $producto['descripcion'] }}</span>
        </nav>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-12">

            {{-- Galería --}}
            <div class="flex gap-4">
                <div class="flex flex-col gap-3">
                    @foreach([0, 1] as $i)
                        <div class="flex h-[70px] w-[70px] items-center justify-center rounded border {{ $i === 0 ? 'border-[#0D2B5E]' : 'border-slate-200' }} bg-white">
                            @include('livewire.vistas.productos.partials.producto-imagen', ['class' => 'h-12 w-12', 'src' => $producto['imagen'] ?? null, 'alt' => $producto['codigo']])
                        </div>
                    @endforeach
                </div>

                <div class="flex min-h-[420px] flex-1 items-center justify-center rounded border border-slate-200 bg-white p-6">
                    @include('livewire.vistas.productos.partials.producto-imagen', ['class' => 'h-72 w-72', 'src' => $producto['imagen'] ?? null, 'alt' => $producto['codigo']])
                </div>
            </div>

            {{-- Datos y compra --}}
            <div>
                <p class="flex flex-wrap items-center gap-3 text-[17px] font-bold text-[#2563C9]">
                    {{ $producto['codigo'] }}
                    @include('livewire.vistas.productos.partials.badge-oferta', ['producto' => $producto])
                </p>
                <hr class="my-3 border-slate-200">

                <h1 class="text-[30px] font-bold uppercase leading-[125%] text-slate-900">
                    {{ $producto['descripcion'] }}
                </h1>

                <dl class="mt-10 space-y-3 text-[17px]">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-700">Precio Lista</dt>
                        <dd class="text-slate-800">{{ Precio::ar($producto['lista']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-700">Tu precio</dt>
                        <dd class="text-slate-800">{{ Precio::ar($producto['costo']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-700">Precio Venta</dt>
                        <dd class="font-semibold text-slate-900">{{ Precio::ar($producto['precio_venta']) }}</dd>
                    </div>
                </dl>

                <div class="mt-8 flex flex-wrap items-center justify-between gap-4">
                    @if($wssp)
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $wssp) }}?text={{ $mensajeWssp }}"
                           target="_blank" rel="noopener"
                           class="inline-flex h-[52px] items-center rounded-[4px] bg-[#0D2B5E] px-8 text-[16px] font-bold uppercase tracking-wide text-white transition hover:bg-[#0A2249]">
                            Consultar
                        </a>
                    @endif

                    <div class="flex items-center gap-3">
                        <label class="sr-only" for="cantidad-detalle">Cantidad</label>
                        <input id="cantidad-detalle" type="number" min="1" step="1" wire:model.live="cantidad"
                               class="h-[52px] w-[80px] rounded border border-slate-300 px-3 text-[16px] text-slate-700 outline-none focus:border-[#0D2B5E]">

                        <button type="button" wire:click="agregar('{{ $producto['codigo'] }}')"
                                class="inline-flex h-[52px] w-[58px] cursor-pointer items-center justify-center gap-0.5 rounded border border-[#0D2B5E] text-[#0D2B5E] transition hover:bg-[#0D2B5E] hover:text-white"
                                title="Agregar al carrito" aria-label="Agregar {{ $producto['codigo'] }} al carrito">
                            <span class="text-[14px] font-bold">+</span>
                            <svg class="h-[19px] w-[19px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13 5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-8 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Aplicaciones y atributos --}}
        <div class="mt-14 grid grid-cols-1 gap-10 lg:grid-cols-2 lg:gap-12">
            <section>
                <h2 class="mb-5 text-[28px] font-bold text-slate-900">Aplicaciones</h2>
                <ul>
                    @forelse($producto['aplicaciones'] as $aplicacion)
                        <li class="px-4 py-3 text-[15px] text-slate-700 {{ $loop->even ? 'bg-white' : 'bg-slate-50' }}">{{ $aplicacion }}</li>
                    @empty
                        <li class="px-4 py-3 text-[15px] text-slate-500">Sin aplicaciones cargadas.</li>
                    @endforelse
                </ul>
            </section>

            <section>
                <h2 class="mb-5 text-[28px] font-bold text-slate-900">Atributos</h2>
                <dl>
                    @forelse($producto['atributos'] as $nombre => $valor)
                        <div class="flex items-center justify-between gap-4 px-4 py-3 text-[15px] {{ $loop->even ? 'bg-white' : 'bg-slate-50' }}">
                            <dt class="text-slate-700">{{ $nombre }}</dt>
                            <dd class="text-slate-800">{{ $valor }}</dd>
                        </div>
                    @empty
                        <p class="px-4 py-3 text-[15px] text-slate-500">Sin atributos cargados.</p>
                    @endforelse
                </dl>
            </section>
        </div>

        {{-- Relacionados --}}
        @if($relacionados)
            <section class="mt-14">
                <h2 class="mb-6 text-[28px] font-bold text-slate-900">Productos relacionados</h2>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($relacionados as $item)
                        <div wire:key="rel-{{ $item['codigo'] }}">
                            @include('livewire.vistas.productos.partials.producto-relacionado', ['item' => $item])
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
