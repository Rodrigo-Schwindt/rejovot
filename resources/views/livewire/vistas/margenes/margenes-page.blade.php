@php use App\Services\Margenes\Margenes; @endphp

<div>
    {{-- Márgenes del cliente: se aplican al precio de venta del catálogo. --}}

    <div class="mx-auto w-full max-w-[1300px] px-4 py-6 xl:px-6">

        <nav class="mb-8 text-[13px] text-slate-500" aria-label="Migas de pan">
            <a wire:navigate href="{{ route('home') }}" class="font-semibold text-slate-700 transition hover:text-[#0D2B5E]">Inicio</a>
            <span class="mx-1.5 text-slate-400">&gt;</span>
            <span>Márgenes</span>
        </nav>

        <section class="mb-12">
            <h1 class="mb-7 text-[34px] font-bold leading-[120%] text-slate-900">Márgenes sobre lista de precios</h1>

            @include('livewire.vistas.margenes.partials.campo-margen', [
                'id' => 'margen-general',
                'modelo' => 'general',
                'etiqueta' => 'Márgen sobre lista de precios',
            ])

            <p class="mt-3 max-w-[560px] text-[14px] text-slate-500">
                Es el margen que se aplica cuando la marca o la familia del producto no tienen uno propio.
            </p>
        </section>

        <div class="grid grid-cols-1 gap-12 lg:grid-cols-2">

            <section>
                <h2 class="mb-7 text-[34px] font-bold leading-[120%] text-slate-900">Márgenes sobre marcas</h2>

                <div class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
                    @foreach($listaMarcas as $marca)
                        @php $clave = Margenes::clave($marca); @endphp
                        @include('livewire.vistas.margenes.partials.campo-margen', [
                            'id' => 'margen-marca-' . $clave,
                            'modelo' => 'marcas.' . $clave,
                            'etiqueta' => 'Márgen sobre ' . $marca,
                        ])
                    @endforeach
                </div>
            </section>

            <section>
                <h2 class="mb-7 text-[34px] font-bold leading-[120%] text-slate-900">Márgenes sobre familias</h2>

                <div class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
                    @foreach($listaFamilias as $familia)
                        @php $clave = Margenes::clave($familia); @endphp
                        @include('livewire.vistas.margenes.partials.campo-margen', [
                            'id' => 'margen-familia-' . $clave,
                            'modelo' => 'familias.' . $clave,
                            'etiqueta' => 'Márgen sobre ' . $familia,
                        ])
                    @endforeach
                </div>
            </section>
        </div>

        <p class="mt-10 text-[14px] text-slate-500">
            Los cambios se guardan solos y se ven en el <a wire:navigate href="{{ route('productos') }}" class="font-semibold text-[#0D2B5E] underline">precio de venta del catálogo</a>.
        </p>
    </div>
</div>
