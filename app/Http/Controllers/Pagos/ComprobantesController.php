<?php

namespace App\Http\Controllers\Pagos;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\PaymentReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Info de pagos: los comprobantes de pago que cargan los clientes desde
 * Info de pagos. No vienen de Odoo; el pago se imputa allá a mano.
 */
class ComprobantesController extends Controller
{
    public function index(Request $request)
    {
        $estado = (string) $request->get('estado', '');
        $buscar = trim((string) $request->get('q'));

        $comprobantes = PaymentReceipt::query()
            ->with(['customer', 'user'])
            ->when(in_array($estado, ['pendiente', 'procesado'], true), fn ($q) => $q->where('estado', $estado))
            ->when($buscar !== '', fn ($q) => $q->where(function ($sub) use ($buscar) {
                $sub->where('banco', 'like', "%{$buscar}%")
                    ->orWhere('facturas_canceladas', 'like', "%{$buscar}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$buscar}%"));
            }))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('livewire.pagos.comprobantes', [
            'comprobantes' => $comprobantes,
            'estado' => $estado,
            'buscar' => $buscar,
            'totalPendientes' => PaymentReceipt::where('estado', 'pendiente')->count(),
            'totalProcesados' => PaymentReceipt::where('estado', 'procesado')->count(),
            'importePendiente' => (float) PaymentReceipt::where('estado', 'pendiente')->sum('importe'),
            'contacto' => Contact::first(),
        ]);
    }

    /** Casilla a la que se avisa cada vez que un cliente carga un comprobante. */
    public function guardarMail(Request $request)
    {
        $datos = $request->validate([
            'mail_comprobantes' => ['nullable', 'email', 'max:255'],
        ], [
            'mail_comprobantes.email' => 'Escribí una dirección de correo válida.',
        ]);

        $contacto = Contact::first() ?? new Contact;
        $contacto->mail_comprobantes = $datos['mail_comprobantes'] ?: null;
        $contacto->save();

        return back()->with('success', $contacto->mail_comprobantes
            ? "Los comprobantes se van a avisar a {$contacto->mail_comprobantes}."
            : 'Sin casilla propia: los comprobantes se avisan al mail de contacto.');
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
