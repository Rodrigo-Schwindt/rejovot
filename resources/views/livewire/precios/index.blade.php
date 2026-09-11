@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn space-y-6">

    <h2 class="text-xl font-semibold text-slate-800">
        Listas de precios
        <span class="text-base font-normal text-slate-400">({{ $listas->total() }})</span>
    </h2>
    <p class="-mt-4 text-sm text-slate-500">Los archivos que el cliente descarga desde <b>Lista de precios</b>.</p>

    @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert-error"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('admin.precios.store') }}" enctype="multipart/form-data"
          class="space-y-4 rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
        @csrf
        <span class="sec-label">Publicar una lista</span>

        <p class="mt-2 text-sm text-slate-500">
            La «Lista de precios completa» se arma sola todos los días a las 5 con los productos
            publicados en el sitio: no hace falta subirla. Acá se cargan las listas que preparás aparte.
        </p>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="md:col-span-2">
                <label class="f-label" for="descripcion">Descripción</label>
                <input type="text" id="descripcion" name="descripcion" value="{{ old('descripcion') }}" class="f-input"
                       placeholder="Lista de precios - Agosto 2026" required>
            </div>
            <div>
                <label class="f-label" for="vigencia">Vigencia</label>
                <input type="text" id="vigencia" name="vigencia" value="{{ old('vigencia') }}" class="f-input" placeholder="Agosto 2026">
            </div>
        </div>

        <div>
            <label class="f-label" for="notas">Notas</label>
            <input type="text" id="notas" name="notas" value="{{ old('notas') }}" class="f-input"
                   placeholder="Precios sin IVA, sujetos a modificación sin previo aviso.">
        </div>

        <div>
            <label class="f-label" for="archivo">Archivo</label>
            <input type="file" id="archivo" name="archivo" class="file-input" accept=".pdf,.xls,.xlsx,.csv" required>
            <p class="f-hint">PDF, XLS, XLSX o CSV · hasta 20 MB. El formato de la tabla se deduce de la extensión.</p>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary px-8">Publicar</button>
        </div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-slate-100 bg-white shadow-sm">
        <table class="w-full min-w-[760px] text-sm text-slate-700">
            <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase text-slate-400">
                <tr>
                    <th class="px-4 py-3 text-left">Descripción</th>
                    <th class="px-4 py-3 text-center">Formato</th>
                    <th class="px-4 py-3 text-center">Peso</th>
                    <th class="px-4 py-3 text-left">Vigencia</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($listas as $lista)
                    <tr class="transition hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-medium text-slate-800">
                            {{ $lista->descripcion }}
                            @if($lista->es_automatica)
                                <span class="ml-1 inline-flex items-center rounded bg-blue-50 px-2 py-0.5 text-[11px] font-semibold uppercase text-[#0D2B5E]">
                                    Automática
                                </span>
                            @endif
                            <span class="block text-xs text-slate-400">{{ $lista->archivo_original }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">{{ $lista->formato_nombre }}</td>
                        <td class="px-4 py-3 text-center text-slate-500">{{ $lista->tamano_legible }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $lista->vigencia ?: '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium {{ $lista->publicada ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $lista->publicada ? 'Publicada' : 'Oculta' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('precios.descargar', $lista) }}" class="tbl-edit" title="Descargar">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                                </a>

                                <form method="POST" action="{{ route('admin.precios.toggle', $lista) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="cursor-pointer rounded px-2 py-1 text-xs font-medium transition {{ $lista->publicada ? 'bg-slate-100 text-slate-600 hover:bg-slate-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                        {{ $lista->publicada ? 'Ocultar' : 'Publicar' }}
                                    </button>
                                </form>

                                @if($lista->es_automatica)
                                    <form method="POST" action="{{ route('admin.precios.regenerar') }}">
                                        @csrf
                                        <button type="submit" class="cursor-pointer rounded bg-blue-50 px-2 py-1 text-xs font-medium text-[#0D2B5E] transition hover:bg-blue-100">
                                            Actualizar ahora
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.precios.destroy', $lista) }}" onsubmit="return confirm('¿Eliminar esta lista?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="tbl-del cursor-pointer" title="Eliminar">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 7-.87 12.14A2 2 0 0 1 16.14 21H7.86a2 2 0 0 1-2-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">Todavía no publicaste listas</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $listas->links() }}</div>
</div>
@endsection
