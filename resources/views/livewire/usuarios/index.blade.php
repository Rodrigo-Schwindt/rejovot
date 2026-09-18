@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn space-y-6">

    @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert-error">{{ session('error') }}</div>@endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Usuarios del panel</h2>
            <p class="mt-1 text-sm text-slate-500">Los que entran acá, a administrar el sitio.</p>
        </div>
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary btn-sm">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Nuevo usuario
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
        <table class="w-full text-sm text-slate-700">
            <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase text-slate-400">
                <tr>
                    <th class="px-4 py-3 text-left">Nombre</th>
                    <th class="hidden px-4 py-3 text-left sm:table-cell">Email</th>
                    <th class="hidden px-4 py-3 text-left md:table-cell">Rol</th>
                    <th class="w-28 px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($users as $u)
                    <tr class="transition hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-medium text-slate-800">
                            {{ $u->name }}
                            <span class="block text-xs text-slate-400 sm:hidden">{{ $u->email }}</span>
                        </td>
                        <td class="hidden px-4 py-3 text-slate-500 sm:table-cell">{{ $u->email }}</td>
                        <td class="hidden px-4 py-3 md:table-cell">
                            <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium
                                {{ $u->role === 'admin' ? 'bg-red-50 text-[#E11A22]' : ($u->role === 'viewer' ? 'bg-blue-50 text-[#002B56]' : 'bg-slate-100 text-slate-600') }}">
                                {{ $u->role === 'admin' ? 'Admin' : ($u->role === 'viewer' ? 'Espectador' : 'Usuario') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-3">
                                <a href="{{ route('usuarios.edit', $u) }}" class="tbl-edit" title="Editar">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </a>
                                @if(auth()->id() !== $u->id)
                                    <form action="{{ route('usuarios.destroy', $u) }}" method="POST" onsubmit="return confirm('¿Eliminar usuario?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="tbl-del cursor-pointer" title="Eliminar">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $users->links() }}

    {{-- Accesos al sitio: los crea Odoo, no se cargan a mano. --}}
    <div class="pt-4">
        <div class="flex flex-col gap-1">
            <h2 class="text-xl font-semibold text-slate-800">Accesos al sitio</h2>
            <p class="text-sm text-slate-500">
                Vendedores y clientes que entraron al catálogo con su usuario y contraseña de Odoo.
                Se crean solos la primera vez que ingresan: no se cargan ni se editan desde acá, y su
                contraseña nunca se guarda en este sistema.
            </p>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
            <table class="w-full text-sm text-slate-700">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase text-slate-400">
                    <tr>
                        <th class="px-4 py-3 text-left">Nombre</th>
                        <th class="hidden px-4 py-3 text-left sm:table-cell">Email</th>
                        <th class="px-4 py-3 text-left">Entra como</th>
                        <th class="hidden px-4 py-3 text-left lg:table-cell">Vinculado a</th>
                        <th class="hidden px-4 py-3 text-left md:table-cell">Último ingreso</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($accesos as $acceso)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="px-4 py-3 font-medium text-slate-800">
                                {{ $acceso->name }}
                                <span class="block text-xs text-slate-400 sm:hidden">{{ $acceso->email }}</span>
                            </td>
                            <td class="hidden px-4 py-3 text-slate-500 sm:table-cell">{{ $acceso->email }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium
                                    {{ $acceso->esVendedor() ? 'bg-amber-50 text-amber-700' : 'bg-green-50 text-green-700' }}">
                                    {{ $acceso->esVendedor() ? 'Vendedor' : 'Cliente' }}
                                </span>
                            </td>
                            <td class="hidden px-4 py-3 text-xs text-slate-500 lg:table-cell">
                                {{ $acceso->salesperson?->name ?: ($acceso->customer?->name ?: '—') }}
                            </td>
                            <td class="hidden px-4 py-3 text-xs text-slate-500 md:table-cell">
                                {{ $acceso->last_login_at?->format('d/m/Y H:i') ?: 'Nunca' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                                Todavía no entró ningún vendedor ni cliente al sitio.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($totalAccesos > count($accesos))
            <p class="mt-2 text-xs text-slate-400">
                Mostrando los {{ count($accesos) }} ingresos más recientes de {{ number_format($totalAccesos, 0, ',', '.') }}.
            </p>
        @endif
    </div>
</div>
@endsection
