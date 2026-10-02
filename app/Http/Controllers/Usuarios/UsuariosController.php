<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

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
            'email'    => ['required', 'email', 'max:255', $this->emailLibre()],
            'role'     => ['required', Rule::in(User::ROLES_PANEL)],
            'password' => 'required|min:6',
        ], $this->mensajes());

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'role'     => $request->role,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario creado correctamente.');
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
            'email'    => ['required', 'email', 'max:255', $this->emailLibre()->ignore($usuario->id)],
            'role'     => ['required', Rule::in(User::ROLES_PANEL)],
            'password' => 'nullable|min:6',
        ], $this->mensajes());

        if ($request->role !== 'admin' && $this->esElUltimoAdmin($usuario)) {
            return back()->withInput()->withErrors([
                'role' => 'Es el único administrador: si le cambiás el rol no queda nadie con acceso completo.',
            ]);
        }

        $usuario->update([
            'name'     => $request->name,
            'email'    => $request->email,
            'role'     => $request->role,
            'password' => $request->password ? Hash::make($request->password) : $usuario->password,
        ]);

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario)
    {
        $this->soloPanel($usuario);

        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No podés eliminar tu propio usuario.');
        }

        if ($this->esElUltimoAdmin($usuario)) {
            return back()->with('error', 'No se puede eliminar al único administrador.');
        }

        $usuario->delete();

        return back()->with('success', 'Usuario eliminado correctamente.');
    }

    /**
     * El mail no se repite entre usuarios del panel, pero sí puede coincidir
     * con un acceso del sitio: alguien puede ser admin acá y vendedor en Odoo
     * con el mismo correo, y son dos usuarios distintos.
     */
    protected function emailLibre(): Unique
    {
        return Rule::unique('users', 'email')->where(fn ($q) => $q->whereIn('role', User::ROLES_PANEL));
    }

    protected function mensajes(): array
    {
        return [
            'email.unique' => 'Ya hay un usuario del panel con ese mail.',
            'role.in' => 'Elegí un rol válido.',
        ];
    }

    protected function esElUltimoAdmin(User $usuario): bool
    {
        return $usuario->role === 'admin' && User::where('role', 'admin')->count() <= 1;
    }

    /** Un acceso creado desde Odoo no se edita ni se borra desde el panel. */
    protected function soloPanel(User $usuario): void
    {
        abort_unless($usuario->esDelPanel(), 404);
    }
}
