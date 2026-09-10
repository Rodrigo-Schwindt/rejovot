<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

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
        $request->session()->regenerate();

        return redirect()->route('login');
    }
}
