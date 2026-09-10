@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn mx-auto max-w-2xl space-y-6">

    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-slate-800">Editar usuario</h2>
        <a href="{{ route('usuarios.index') }}" class="btn btn-ghost btn-sm">Volver</a>
    </div>

    @if($errors->any())<div class="alert-error"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('usuarios.update', $user) }}" class="space-y-4 rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
        @csrf @method('PUT')

        <div>
            <label class="f-label" for="name">Nombre</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="f-input" required>
        </div>

        <div>
            <label class="f-label" for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="f-input" required>
        </div>

        <div>
            <label class="f-label" for="role">Rol</label>
            <select id="role" name="role" class="f-input" required>
                <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                <option value="user" @selected(old('role', $user->role) === 'user')>Usuario</option>
                <option value="viewer" @selected(old('role', $user->role) === 'viewer')>Espectador (solo lectura)</option>
            </select>
        </div>

        <div>
            <label class="f-label" for="password">Nueva contraseña</label>
            <input type="password" id="password" name="password" class="f-input" minlength="6">
            <p class="f-hint">Dejala vacía para conservar la actual.</p>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('usuarios.index') }}" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary px-8">Guardar cambios</button>
        </div>
    </form>
</div>
@endsection
