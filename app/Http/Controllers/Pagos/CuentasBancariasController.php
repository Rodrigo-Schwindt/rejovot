<?php

namespace App\Http\Controllers\Pagos;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CuentasBancariasController extends Controller
{
    public function index()
    {
        return view('livewire.pagos.cuentas-bancarias', [
            'cuentas' => BankAccount::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    /** Guarda todas las cuentas del formulario de una sola vez. */
    public function save(Request $request)
    {
        $request->validate([
            'cuentas' => ['nullable', 'array'],
            'cuentas.*.titular' => ['nullable', 'string', 'max:255'],
            'cuentas.*.banco' => ['nullable', 'string', 'max:255'],
            'cuentas.*.tipo_cuenta' => ['nullable', 'string', 'max:255'],
            'cuentas.*.numero' => ['nullable', 'string', 'max:255'],
            'cuentas.*.cbu' => ['nullable', 'string', 'max:255'],
            'cuentas.*.alias' => ['nullable', 'string', 'max:255'],
            'cuentas.*.cuit' => ['nullable', 'string', 'max:255'],
        ]);

        $filas = [];
        $orden = 0;

        foreach ($request->input('cuentas', []) as $cuenta) {
            $cuenta = array_map(fn ($valor) => trim((string) $valor), $cuenta);

            // Una cuenta sin ningún dato se descarta.
            if (! array_filter($cuenta)) {
                continue;
            }

            $filas[] = $cuenta + ['sort_order' => $orden++];
        }

        // En una transacción: si algo falla, no se pierden las cuentas viejas.
        DB::transaction(function () use ($filas) {
            BankAccount::query()->delete();

            foreach ($filas as $fila) {
                BankAccount::create($fila);
            }
        });

        return back()->with('success', 'Cuentas bancarias guardadas correctamente.');
    }
}
