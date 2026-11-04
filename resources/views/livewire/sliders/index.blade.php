@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn space-y-6">

    @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert-error">{{ session('error') }}</div>@endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Sliders</h2>
            <p class="mt-1 text-sm text-slate-500">Banners que se suman al carrusel de <b>Productos</b>, antes de los productos destacados.</p>
        </div>
        <a href="{{ route('admin.sliders.create') }}" class="btn btn-primary btn-sm">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Nuevo slider
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
        <table class="admin-mobile-table w-full text-sm text-slate-700">
            <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase text-slate-400">
                <tr>
                    <th class="w-16 px-4 py-3 text-center">Orden</th>
                    <th class="px-4 py-3 text-left">Slider</th>
                    <th class="w-20 px-4 py-3 text-center">Color</th>
                    <th class="w-28 px-4 py-3 text-center">Imágenes</th>
                    <th class="w-28 px-4 py-3 text-center">Estado</th>
                    <th class="w-28 px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($sliders as $slider)
                    <tr class="transition hover:bg-slate-50/60">
                        <td data-label="Orden" class="px-4 py-3 text-center text-slate-500">{{ $slider->sort_order }}</td>
                        <td data-label="Slider" class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded border border-slate-200 bg-white p-1">
                                    @if($slider->imagenes_urls)
                                        <img src="{{ $slider->imagenes_urls[0] }}" alt="" class="h-full w-full object-contain">
                                    @else
                                        <svg class="h-5 w-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm0 11 4.5-4.5a1.5 1.5 0 0 1 2.1 0L15 16m-1.5-1.5 1-1a1.5 1.5 0 0 1 2.1 0L21 16M15 9.5h.01"/></svg>
                                    @endif
                                </span>
                                <span class="min-w-0">
                                    <span class="block font-medium text-slate-800">{{ $slider->titulo }}</span>
                                    @if($slider->etiqueta)
                                        <span class="block text-xs text-slate-400">{{ $slider->etiqueta }}</span>
                                    @endif
                                </span>
                            </div>
                        </td>
                        <td data-label="Color" class="px-4 py-3 text-center">
                            <span class="inline-block h-7 w-7 rounded border border-slate-200 align-middle" style="background-color: {{ $slider->fondo }}" title="Color de fondo: {{ $slider->fondo }}"></span>
                        </td>
                        <td data-label="Imágenes" class="px-4 py-3 text-center text-slate-500">{{ count($slider->imagenes ?? []) }}</td>
                        <td data-label="Estado" class="px-4 py-3 text-center">
                            <form method="POST" action="{{ route('admin.sliders.toggle', $slider) }}">
                                @csrf @method('PATCH')
                                <button type="submit" title="{{ $slider->activo ? 'Ocultar en el sitio' : 'Mostrar en el sitio' }}"
                                        class="inline-flex cursor-pointer items-center rounded px-2 py-0.5 text-xs font-medium {{ $slider->activo ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $slider->activo ? 'Visible' : 'Oculto' }}
                                </button>
                            </form>
                        </td>
                        <td data-label="Acciones" class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-3">
                                <a href="{{ route('admin.sliders.edit', $slider) }}" class="tbl-edit" title="Editar">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </a>
                                <form action="{{ route('admin.sliders.destroy', $slider) }}" method="POST" onsubmit="return confirm('¿Eliminar este slider?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="tbl-del cursor-pointer" title="Eliminar">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">
                            Todavía no hay sliders. El carrusel muestra sólo los productos destacados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
