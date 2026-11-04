@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn mx-auto max-w-4xl space-y-6">

    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-slate-800">Editar slider</h2>
        <a href="{{ route('admin.sliders.index') }}" class="btn btn-ghost btn-sm">Volver</a>
    </div>

    @include('livewire.sliders.partials.form', [
        'accion' => route('admin.sliders.update', $slider),
        'metodo' => 'PATCH',
        'boton' => 'Guardar cambios',
    ])
</div>
@endsection
