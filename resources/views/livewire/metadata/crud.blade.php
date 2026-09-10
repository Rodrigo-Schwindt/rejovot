@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn space-y-6">

    <h2 class="text-xl font-semibold text-slate-800">Metadata SEO</h2>
    <p class="-mt-4 text-sm text-slate-500">Título, descripción y palabras clave de cada sección pública.</p>

    @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert-error"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('admin.metadata.save') }}" class="space-y-4 rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
        @csrf
        <span class="sec-label">Cargar / actualizar sección</span>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="f-label" for="section">Sección</label>
                <select id="section" name="section" class="f-input" required>
                    @foreach(\App\Models\Metadata::SECTIONS as $value => $label)
                        <option value="{{ $value }}" @selected(old('section') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="f-hint">Si la sección ya existe, se sobrescribe.</p>
            </div>
            <div class="md:col-span-2">
                <label class="f-label" for="keywords">Keywords</label>
                <input type="text" id="keywords" name="keywords" value="{{ old('keywords') }}" class="f-input" placeholder="rejovot, autopartes, repuestos" maxlength="500">
            </div>
        </div>

        <div>
            <label class="f-label" for="description">Descripción</label>
            <textarea id="description" name="description" rows="3" class="f-textarea" maxlength="500"
                      placeholder="Catálogo mayorista de autopartes con stock y precios actualizados.">{{ old('description') }}</textarea>
            <p class="f-hint">Ideal hasta 160 caracteres: es lo que muestra Google.</p>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary px-8">Guardar</button>
        </div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-slate-100 bg-white shadow-sm">
        <table class="w-full min-w-[640px] text-sm text-slate-700">
            <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase text-slate-400">
                <tr>
                    <th class="px-4 py-3 text-left">Sección</th>
                    <th class="px-4 py-3 text-left">Keywords</th>
                    <th class="px-4 py-3 text-left">Descripción</th>
                    <th class="w-24 px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($items as $item)
                    <tr class="transition hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-medium text-slate-800">
                            {{ \App\Models\Metadata::SECTIONS[$item->section] ?? $item->section }}
                            <span class="block text-xs text-slate-400">{{ $item->section }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $item->keywords ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ \Illuminate\Support\Str::limit($item->description, 90) ?: '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <form method="POST" action="{{ route('admin.metadata.delete', $item) }}" onsubmit="return confirm('¿Eliminar esta metadata?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="tbl-del cursor-pointer" title="Eliminar">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">Todavía no cargaste metadata</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
