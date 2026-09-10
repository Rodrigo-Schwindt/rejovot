{{-- Listado en formato cuadrícula --}}
@if(count($productos))
    <div class="grid grid-cols-1 items-stretch gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($productos as $producto)
            @include('livewire.vistas.productos.partials.producto-card', ['producto' => $producto])
        @endforeach
    </div>
@else
    <div class="rounded-[4px] border border-slate-200 bg-white px-4 py-14 text-center text-[14px] text-slate-500">
        No encontramos productos con esos filtros.
    </div>
@endif
