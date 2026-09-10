@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn space-y-6">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-xl font-semibold text-slate-800">
            Newsletter
            <span class="text-base font-normal text-slate-400">({{ $subscribers->total() }} suscriptores)</span>
        </h2>
        <div class="flex gap-2 text-xs">
            <span class="inline-flex items-center rounded bg-green-100 px-2 py-1 font-medium text-green-700">{{ $totalActivos }} habilitados</span>
            <span class="inline-flex items-center rounded bg-slate-100 px-2 py-1 font-medium text-slate-600">{{ $totalInactivos }} deshabilitados</span>
        </div>
    </div>

    @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert-error">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert-error"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <details class="group rounded-xl border border-slate-100 bg-white shadow-sm" @if($errors->any()) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4">
            <div>
                <h3 class="font-semibold text-slate-800">Enviar mensaje masivo</h3>
                <p class="mt-1 text-xs text-slate-400">Se envía únicamente a los suscriptores habilitados ({{ $totalActivos }}).</p>
            </div>
            <svg class="h-5 w-5 text-slate-400 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
        </summary>

        <form method="POST" action="{{ route('admin.newsletter.send') }}" class="space-y-4 border-t border-slate-100 p-5"
              onsubmit="return confirm('¿Enviar este mensaje a {{ $totalActivos }} suscriptores?')">
            @csrf
            <div>
                <label class="f-label" for="newsletter-asunto">Asunto</label>
                <input class="f-input" id="newsletter-asunto" name="asunto" value="{{ old('asunto') }}" required maxlength="200">
            </div>
            <div>
                <label class="f-label" for="newsletter-cuerpo">Mensaje</label>
                <textarea class="f-textarea" id="newsletter-cuerpo" name="cuerpo" rows="8" required>{{ old('cuerpo') }}</textarea>
                <p class="f-hint mt-1">Texto plano. Los saltos de línea se respetan en el correo.</p>
            </div>
            <button type="submit" class="btn btn-primary" @disabled($totalActivos === 0)>Enviar a {{ $totalActivos }} suscriptores</button>
        </form>
    </details>

    <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.newsletter.index') }}">
            <label class="sr-only" for="newsletter-search">Buscar suscriptor</label>
            <div class="relative">
                <input type="text" id="newsletter-search" name="search" value="{{ $search }}" placeholder="Buscar por email…" class="f-input f-input-search">
                <svg class="pointer-events-none absolute inset-y-0 left-3 my-auto h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                @if($search !== '')
                    <a href="{{ route('admin.newsletter.index') }}" class="absolute inset-y-0 right-3 my-auto h-4 text-slate-400 hover:text-slate-600" aria-label="Limpiar búsqueda">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/></svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-100 bg-white shadow-sm">
        <table class="w-full min-w-[640px] text-sm text-slate-700">
            <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase text-slate-400">
                <tr>
                    <th class="px-4 py-3 text-left">Email</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-center">Suscripción</th>
                    <th class="px-4 py-3 text-center">Último envío</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($subscribers as $subscriber)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $subscriber->email }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium {{ $subscriber->active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $subscriber->active ? 'Habilitado' : 'Deshabilitado' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $subscriber->created_at?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-center text-xs text-slate-400">{{ $subscriber->last_sent_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                <form method="POST" action="{{ route('admin.newsletter.toggle', $subscriber) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="cursor-pointer rounded px-2 py-1 text-xs font-medium transition {{ $subscriber->active ? 'bg-slate-100 text-slate-600 hover:bg-slate-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                        {{ $subscriber->active ? 'Deshabilitar' : 'Habilitar' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.newsletter.destroy', $subscriber) }}" onsubmit="return confirm('¿Eliminar {{ $subscriber->email }} de la lista?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="tbl-del cursor-pointer transition" title="Eliminar">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 7-.87 12.14A2 2 0 0 1 16.14 21H7.86a2 2 0 0 1-2-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">Todavía no hay suscriptores</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $subscribers->links() }}</div>
</div>
@endsection
