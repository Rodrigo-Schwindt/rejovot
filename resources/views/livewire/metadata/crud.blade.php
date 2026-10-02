@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn space-y-6">

    <div>
        <h2 class="text-xl font-semibold text-slate-800">Metadata SEO</h2>
        <p class="mt-1 text-sm text-slate-500">
            Lo que muestran Google y las redes al compartir cada sección. Si dejás un campo vacío, se usa el texto por defecto (el que se ve en gris).
        </p>
    </div>

    @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert-error"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    {{-- Productos: automática --}}
    <div class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
        <span class="sec-label">Productos (automática)</span>
        <p class="mt-2 text-sm text-slate-500">
            Cada producto tiene su propia metadata, que se arma sola con los datos de Odoo: nombre, marca, rubro, código y códigos OEM.
            Se actualiza cuando cambian en Odoo. Para escribir la de un producto a mano, entrá en
            <a href="{{ route('admin.catalogo.index') }}" class="font-medium text-[#2563C9] hover:underline">Productos</a> y tocá «SEO» en ese producto.
            @if($productosManuales)
                Hoy hay {{ $productosManuales }} {{ $productosManuales === 1 ? 'producto' : 'productos' }} con metadata escrita a mano.
            @endif
        </p>

        @if($ejemplo)
            <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-400">Así se ve en Google (ejemplo: {{ $ejemplo->code }})</p>
            @include('livewire.metadata.partials.vista-google', [
                'titulo' => $ejemploSeo['title'],
                'url' => route('producto', $ejemplo->code),
                'descripcion' => $ejemploSeo['description'],
            ])
            <p class="mt-2 text-xs text-slate-400">Keywords: {{ $ejemploSeo['keywords'] }}</p>
        @endif
    </div>

    {{-- Secciones --}}
    @foreach($secciones as $clave => $seccion)
        @php $cargada = $cargadas[$clave] ?? null; @endphp
        <form method="POST" action="{{ route('admin.metadata.save') }}" class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
            @csrf
            <input type="hidden" name="section" value="{{ $clave }}">

            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="sec-label">{{ $seccion['nombre'] }}</span>
                <span class="text-xs {{ $seccion['privada'] ? 'text-slate-400' : 'text-green-700' }}">
                    {{ $seccion['privada'] ? 'Requiere ingresar · Google no la indexa' : 'Pública · Google la indexa' }}
                    @if($cargada) · <span class="font-medium text-[#2563C9]">personalizada</span> @endif
                </span>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div>
                    <label class="f-label" for="title-{{ $clave }}">Título</label>
                    <input type="text" id="title-{{ $clave }}" name="title" maxlength="120" class="f-input"
                           value="{{ $cargada?->title }}" placeholder="{{ $seccion['title'] }}">
                    <p class="f-hint">Se le agrega « | {{ \App\Support\Seo::brand() }}». Ideal: hasta 60 caracteres.</p>
                </div>
                <div>
                    <label class="f-label" for="keywords-{{ $clave }}">Keywords</label>
                    <input type="text" id="keywords-{{ $clave }}" name="keywords" maxlength="500" class="f-input"
                           value="{{ $cargada?->keywords }}" placeholder="{{ $seccion['keywords'] ?: 'rejovot, autopartes, repuestos, mayorista' }}">
                    <p class="f-hint">Separadas por coma.</p>
                </div>
                <div class="lg:col-span-2">
                    <label class="f-label" for="description-{{ $clave }}">Descripción</label>
                    <textarea id="description-{{ $clave }}" name="description" rows="2" maxlength="500" class="f-textarea"
                              placeholder="{{ $seccion['description'] }}">{{ $cargada?->description }}</textarea>
                    <p class="f-hint">Ideal: hasta 160 caracteres, es lo que muestra Google.</p>
                </div>
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    @endforeach
</div>
@endsection
