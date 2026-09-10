<?php

namespace App\Http\Controllers\Pagos;

use App\Http\Controllers\Controller;
use App\Models\PaymentReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ComprobantesController extends Controller
{
    public function index(Request $request)
    {
        $estado = $request->get('estado', '');

        $comprobantes = PaymentReceipt::query()
            ->when(in_array($estado, ['pendiente', 'procesado'], true), fn ($q) => $q->where('estado', $estado))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('livewire.pagos.comprobantes', [
            'comprobantes' => $comprobantes,
            'estado' => $estado,
            'totalPendientes' => PaymentReceipt::where('estado', 'pendiente')->count(),
            'totalProcesados' => PaymentReceipt::where('estado', 'procesado')->count(),
        ]);
    }

    public function download(PaymentReceipt $comprobante)
    {
        abort_unless(Storage::disk('public')->exists($comprobante->archivo), 404);

        return Storage::disk('public')->download(
            $comprobante->archivo,
            $comprobante->archivo_original ?: basename($comprobante->archivo),
        );
    }

    public function estado(PaymentReceipt $comprobante)
    {
        $comprobante->update([
            'estado' => $comprobante->estado === 'procesado' ? 'pendiente' : 'procesado',
        ]);

        return back()->with('success', $comprobante->estado === 'procesado'
            ? 'Comprobante marcado como procesado.'
            : 'Comprobante marcado como pendiente.');
    }

    public function destroy(PaymentReceipt $comprobante)
    {
        if ($comprobante->archivo) {
            Storage::disk('public')->delete($comprobante->archivo);
        }

        $comprobante->delete();

        return back()->with('success', 'Comprobante eliminado.');
    }
}
