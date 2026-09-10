<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Usuarios del panel administrativo.
 *
 * Los usuarios que entran al sitio (vendedores y clientes) no se administran
 * acá: los crea `OdooAuth` solo, cuando la persona entra con sus credenciales
 * de Odoo. Se listan aparte y de sólo lectura.
 */
class UsuariosController extends Controller
{
    public function index()
    {
        return view('livewire.usuarios.index', [
            'users' => User::delPanel()->orderBy('name')->paginate(15),
            'accesos' => User::delSitio()
                ->with(['customer', 'salesperson'])
                ->orderByDesc('last_login_at')
                ->limit(50)
                ->get(),
            'totalAccesos' => User::delSitio()->count(),
        ]);
    }

    public function create()
    {
        return view('livewire.usuarios.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'role'     => 'required|in:admin,user,viewer',
            'password' => 'required|min:6',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'role'     => $request->role,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario)
    {
        $this->soloPanel($usuario);

        return view('livewire.usuarios.edit', ['user' => $usuario]);
    }

    public function update(Request $request, User $usuario)
    {
        $this->soloPanel($usuario);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $usuario->id,
            'role'     => 'required|in:admin,user,viewer',
            'password' => 'nullable|min:6',
        ]);

        $usuario->update([
            'name'     => $request->name,
            'email'    => $request->email,
            'role'     => $request->role,
            'password' => $request->password ? Hash::make($request->password) : $usuario->password,
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario)
    {
        $this->soloPanel($usuario);

        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No podés eliminar tu propio usuario.');
        }

        $usuario->delete();

        return back()->with('success', 'Usuario eliminado correctamente.');
    }

    /** Un acceso creado desde Odoo no se edita ni se borra desde el panel. */
    protected function soloPanel(User $usuario): void
    {
        abort_unless($usuario->esDelPanel(), 404);
    }
}
