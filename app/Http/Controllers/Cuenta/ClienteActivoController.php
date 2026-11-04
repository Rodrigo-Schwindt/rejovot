<?php

namespace App\Http\Controllers\Cuenta;

use App\Http\Controllers\Controller;
use App\Services\Sesion\ClienteActivo;
use Illuminate\Http\Request;

/**
 * El vendedor elige, desde el desplegable del header, para qué cliente de su
 * cartera está operando. Es lo mismo que el selector de Productos.
 */
class ClienteActivoController extends Controller
{
    public function elegir(Request $request, ClienteActivo $clienteActivo)
    {
        $id = (int) $request->input('cliente');

        if (! $id || ! $clienteActivo->elegir($id)) {
            return back()->with(['toast' => 'Ese cliente no está en tu cartera.', 'toast_type' => 'error']);
        }

        return back()->with('toast', 'Comprando para ' . $clienteActivo->actual()->name);
    }
}
