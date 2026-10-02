<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('admin.contacto');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // El login de Laravel rota el token de formularios, y el admin comparte
        // sesión con el sitio: se conserva para no dejar con error 419 lo que
        // esté abierto en otra pestaña. El id de sesión sí cambia (fijación).
        $token = $request->session()->token();

        // Sólo usuarios del panel: un vendedor o cliente de Odoo puede tener el
        // mismo mail y es otro usuario.
        $credentials[] = fn ($query) => $query->whereIn('role', User::ROLES_PANEL);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->put('_token', $token);

            if (! auth()->user()->esDelPanel()) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'No tenés permisos para acceder al panel administrativo.',
                ])->withInput();
            }

            return redirect()->intended(route('admin.contacto'));
        }

        return back()->withErrors([
            'email' => 'Las credenciales no son válidas.',
        ])->withInput();
    }

    public function logout(Request $request)
    {
        // Sólo la sesión del panel: si además está logueado en el sitio como
        // cliente o vendedor, esa sigue abierta.
        Auth::guard('web')->logout();
        // Id nuevo sin rotar el token: no rompe formularios de otras pestañas.
        $request->session()->migrate(true);

        return redirect()->route('login');
    }
}
