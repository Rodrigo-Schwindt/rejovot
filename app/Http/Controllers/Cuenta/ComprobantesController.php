<?php

namespace App\Http\Controllers\Cuenta;

use App\Http\Controllers\Controller;
use App\Services\Cuenta\CuentaOdoo;

/**
 * Descarga de comprobantes de la cuenta corriente.
 *
 * El PDF lo emite Odoo. Pasa por acá y no por un link directo para no publicar
 * el token de acceso del comprobante y para validar que sea del cliente activo.
 */
class ComprobantesController extends Controller
{
    public function show(int $move, CuentaOdoo $cuenta)
    {
        $pdf = $cuenta->comprobante($move);

        abort_unless($pdf, 404);

        return response($pdf['contenido'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $pdf['nombre'] . '"',
        ]);
    }
}
