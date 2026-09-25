<?php

namespace App\Http\Controllers\Reclamos;

use App\Http\Controllers\Controller;
use App\Mail\ReclamoEstadoMail;
use App\Models\Claim;
use App\Support\Destinatarios;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/** Reclamos de todos los clientes: se revisan y se les cambia el estado. */
class ReclamosAdminController extends Controller
{
    public function index(Request $request)
    {
        $filtros = [
            'q' => trim((string) $request->get('q')),
            'estado' => trim((string) $request->get('estado')),
        ];

        $query = Claim::query()->with(['customer.salesperson'])->withCount('items');

        if ($filtros['q'] !== '') {
            $texto = $filtros['q'];
            $id = Claim::idDesdeNumero($texto);

            $query->where(function (Builder $q) use ($texto, $id) {
                $q->where('factura_numero', 'like', "%{$texto}%")
                    ->orWhereHas('customer', fn (Builder $c) => $c->where('name', 'like', "%{$texto}%"))
                    ->orWhereHas('items', fn (Builder $i) => $i->where('codigo', 'like', "%{$texto}%"));

                if ($id) {
                    $q->orWhere('id', $id);
                }
            });
        }

        if (array_key_exists($filtros['estado'], Claim::ESTADOS)) {
            $query->where('estado', $filtros['estado']);
        }

        $porEstado = Claim::selectRaw('estado, count(*) as c')->groupBy('estado')->pluck('c', 'estado');

        return view('livewire.reclamos.index', [
            'reclamos' => $query->latest('id')->paginate(20)->withQueryString(),
            'filtros' => $filtros,
            'estados' => Claim::ESTADOS,
            'porEstado' => $porEstado,
        ]);
    }

    public function show(Claim $reclamo)
    {
        return view('livewire.reclamos.show', [
            'reclamo' => $reclamo->load(['customer.salesperson', 'items', 'fotos', 'user']),
            'estados' => Claim::ESTADOS,
        ]);
    }

    public function update(Request $request, Claim $reclamo)
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(array_keys(Claim::ESTADOS))],
            'respuesta' => ['nullable', 'string', 'max:3000'],
        ], [
            'estado.in' => 'Elegí un estado válido.',
        ]);

        $cambioEstado = $datos['estado'] !== $reclamo->estado;
        $respuesta = trim($datos['respuesta'] ?? '') ?: null;
        $cambioRespuesta = $respuesta !== $reclamo->respuesta;

        $reclamo->update([
            'estado' => $datos['estado'],
            'respuesta' => $respuesta,
            'respondido_at' => $cambioRespuesta && $respuesta ? now() : $reclamo->respondido_at,
        ]);

        // Al cliente se le avisa cuando cambia el estado o le escriben una respuesta.
        if ($cambioEstado || ($cambioRespuesta && $respuesta)) {
            $this->avisarAlCliente($reclamo);
        }

        return back()->with('success', $cambioEstado
            ? "Reclamo {$reclamo->numero} pasó a «{$reclamo->estado_nombre}». Se le avisó al cliente."
            : 'Reclamo actualizado.');
    }

    private function avisarAlCliente(Claim $reclamo): void
    {
        $destinos = Destinatarios::delCliente($reclamo->customer, conVendedor: false, conAdmin: false);

        if (! $destinos) {
            return;
        }

        try {
            Mail::to($destinos)->send(new ReclamoEstadoMail($reclamo->fresh()));
        } catch (\Throwable $e) {
            Log::warning("No se pudo avisar el cambio del reclamo {$reclamo->numero}: " . $e->getMessage());
        }
    }
}
