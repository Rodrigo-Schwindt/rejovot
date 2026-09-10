{{-- Ficha del producto seleccionado --}}
<aside class="space-y-5">

    @if($detalle)
        <div class="rounded-[4px] border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-4 py-3 text-center">
                <h2 class="text-[17px] font-bold text-slate-900">{{ $detalle['codigo'] }}</h2>
            </div>
            <div class="flex h-[190px] items-center justify-center p-4">
                @include('livewire.vistas.productos.partials.producto-imagen', ['class' => 'h-36 w-36', 'src' => $detalle['imagen'] ?? null, 'alt' => $detalle['codigo']])
            </div>
        </div>

        <p class="text-[14px] font-semibold uppercase leading-[150%] text-slate-800">
            {{ $detalle['descripcion'] }}
        </p>

        @if(! empty($detalle['aplicaciones']))
            <div class="rounded-[4px] border border-slate-200 bg-white">
                <h3 class="border-b border-slate-200 px-4 py-3 text-center text-[17px] font-bold text-slate-900">Aplicaciones</h3>
                <ul class="divide-y divide-slate-100">
                    @foreach($detalle['aplicaciones'] as $aplicacion)
                        <li class="px-4 py-2.5 text-[13px] text-slate-600">{{ $aplicacion }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(! empty($detalle['atributos']))
            <div class="rounded-[4px] border border-slate-200 bg-white">
                <h3 class="border-b border-slate-200 px-4 py-3 text-center text-[17px] font-bold text-slate-900">Atributos</h3>
                <dl class="divide-y divide-slate-100">
                    @foreach($detalle['atributos'] as $nombre => $valor)
                        <div class="flex items-center justify-between gap-4 px-4 py-2.5">
                            <dt class="text-[13px] text-slate-600">{{ $nombre }}</dt>
                            <dd class="text-[13px] font-medium text-slate-800">{{ $valor }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif
    @else
        <div class="rounded-[4px] border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-[14px] text-slate-500">
            Seleccioná un producto para ver su ficha.
        </div>
    @endif
</aside>
