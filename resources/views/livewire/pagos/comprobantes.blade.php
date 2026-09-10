@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn space-y-6">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-xl font-semibold text-slate-800">
            Comprobantes de pago
            <span class="text-base font-normal text-slate-400">({{ $comprobantes->total() }})</span>
        </h2>
        <div class="flex gap-2 text-xs">
            <a href="{{ route('admin.pagos.comprobantes.index') }}"
               class="inline-flex items-center rounded px-2 py-1 font-medium {{ $estado === '' ? 'bg-[#0D2B5E] text-white' : 'bg-slate-100 text-slate-600' }}">Todos</a>
            <a href="{{ route('admin.pagos.comprobantes.index', ['estado' => 'pendiente']) }}"
               class="inline-flex items-center rounded px-2 py-1 font-medium {{ $estado === 'pendiente' ? 'bg-[#E11A22] text-white' : 'bg-amber-100 text-amber-700' }}">{{ $totalPendientes }} pendientes</a>
            <a href="{{ route('admin.pagos.comprobantes.index', ['estado' => 'procesado']) }}"
               class="inline-flex items-center rounded px-2 py-1 font-medium {{ $estado === 'procesado' ? 'bg-green-700 text-white' : 'bg-green-100 text-green-700' }}">{{ $totalProcesados }} procesados</a>
        </div>
    </div>

    @if(session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert-error">{{ session('error') }}</div>@endif

    <div class="overflow-x-auto rounded-xl border border-slate-100 bg-white shadow-sm">
        <table class="w-full min-w-[900px] text-sm text-slate-700">
            <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase text-slate-400">
                <tr>
                    <th class="px-4 py-3 text-left">Enviado</th>
                    <th class="px-4 py-3 text-left">Fecha de pago</th>
                    <th class="px-4 py-3 text-right">Importe</th>
                    <th class="px-4 py-3 text-left">Banco / Sucursal</th>
                    <th class="px-4 py-3 text-left">Facturas</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($comprobantes as $comprobante)
                    <tr class="transition hover:bg-slate-50/60">
                        <td class="px-4 py-3 text-xs text-slate-400">{{ $comprobante->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $comprobante->fecha?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right font-medium text-slate-800">{{ \App\Support\Precio::ar($comprobante->importe) }}</td>
                        <td class="px-4 py-3">
                            {{ $comprobante->banco }}
                            <span class="block text-xs text-slate-400">Sucursal {{ $comprobante->sucursal }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $comprobante->facturas_canceladas ?: '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium {{ $comprobante->procesado ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $comprobante->procesado ? 'Procesado' : 'Pendiente' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.pagos.comprobantes.download', $comprobante) }}" class="tbl-edit" title="Descargar comprobante">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                                </a>

                                <form method="POST" action="{{ route('admin.pagos.comprobantes.estado', $comprobante) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="cursor-pointer rounded px-2 py-1 text-xs font-medium transition {{ $comprobante->procesado ? 'bg-slate-100 text-slate-600 hover:bg-slate-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                        {{ $comprobante->procesado ? 'Reabrir' : 'Procesar' }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.pagos.comprobantes.destroy', $comprobante) }}" onsubmit="return confirm('¿Eliminar este comprobante?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="tbl-del cursor-pointer" title="Eliminar">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 7-.87 12.14A2 2 0 0 1 16.14 21H7.86a2 2 0 0 1-2-1.86L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    @if($comprobante->observaciones)
                        <tr class="bg-slate-50/40">
                            <td colspan="7" class="px-4 pb-3 text-xs text-slate-500">
                                <b>Observaciones:</b> {{ $comprobante->observaciones }}
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">Todavía no recibiste comprobantes</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $comprobantes->links() }}</div>
</div>
@endsection
