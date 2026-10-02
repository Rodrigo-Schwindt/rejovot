@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn space-y-6">

    <div>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.catalogo.index') }}"
           class="text-xs font-medium text-slate-400 hover:text-slate-600">← Productos</a>
        <h2 class="mt-1 text-xl font-semibold text-slate-800">SEO de {{ $producto->code }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $producto->name }}</p>
    </div>

    @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert-error"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
        <span class="sec-label">Así se ve en Google</span>
        @include('livewire.metadata.partials.vista-google', [
            'titulo' => $final['title'],
            'url' => route('producto', $producto->code),
            'descripcion' => $final['description'],
        ])
        @if(! ($producto->active && $producto->published && ! $producto->oculto))
            <p class="mt-3 text-xs text-amber-700">Hoy este producto no se muestra en el sitio, así que Google no lo ve.</p>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.catalogo.seo.guardar', $producto) }}" class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
        @csrf @method('PATCH')
        <span class="sec-label">Escribirla a mano</span>
        <p class="mt-2 text-sm text-slate-500">
            Por defecto se arma sola con los datos de Odoo (en gris). Lo que escribas acá la reemplaza; dejá un campo vacío para volver a la automática.
        </p>

        <div class="mt-4 space-y-4">
            <div>
                <label class="f-label" for="seo_title">Título</label>
                <input type="text" id="seo_title" name="seo_title" maxlength="120" class="f-input"
                       value="{{ old('seo_title', $producto->seo_title) }}" placeholder="{{ $automatico['title'] }}">
                <p class="f-hint">Completo, tal cual se muestra. Ideal: hasta 60–70 caracteres.</p>
            </div>
            <div>
                <label class="f-label" for="seo_description">Descripción</label>
                <textarea id="seo_description" name="seo_description" rows="3" maxlength="320" class="f-textarea"
                          placeholder="{{ $automatico['description'] }}">{{ old('seo_description', $producto->seo_description) }}</textarea>
                <p class="f-hint">Ideal: hasta 160 caracteres.</p>
            </div>
            <div>
                <label class="f-label" for="seo_keywords">Keywords</label>
                <input type="text" id="seo_keywords" name="seo_keywords" maxlength="500" class="f-input"
                       value="{{ old('seo_keywords', $producto->seo_keywords) }}" placeholder="{{ $automatico['keywords'] }}">
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn btn-primary px-8">Guardar</button>
        </div>
    </form>
</div>
@endsection
