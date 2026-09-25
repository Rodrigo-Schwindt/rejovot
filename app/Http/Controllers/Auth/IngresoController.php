<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Odoo\OdooAuth;
use App\Services\Sesion\ClienteActivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Ingreso de clientes y vendedores con su usuario de Odoo.
 *
 * Usa el guard `sitio`, separado del `web` del panel: se puede estar logueado
 * como administrador y como cliente/vendedor al mismo tiempo, sin pisarse.
 */
class IngresoController extends Controller
{
    /**
     * El sitio no tiene página de login: se entra por el modal del header.
     * La ruta queda para que los links viejos no den 404.
     */
    public function formulario()
    {
        return redirect()->route('productos');
    }

    public function ingresar(Request $request, OdooAuth $auth)
    {
        $datos = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Ingresá tu usuario.',
            'password.required' => 'Ingresá tu contraseña.',
        ]);

        $usuario = $auth->intentar($datos['login'], $datos['password']);

        if (! $usuario) {
            return back()->withErrors([
                'login' => 'No pudimos validar ese usuario. Revisá los datos o escribinos.',
            ])->withInput($request->only('login'));
        }

        Auth::guard('sitio')->login($usuario, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('productos'));
    }

    public function salir(Request $request, ClienteActivo $clienteActivo)
    {
        $clienteActivo->limpiar();

        // Sólo se cierra la sesión del sitio: la del panel, si la hay, sigue.
        Auth::guard('sitio')->logout();
        $request->session()->regenerate();

        // Se queda en el catálogo: para volver a entrar está el modal del header.
        return redirect()->route('productos');
    }
}
