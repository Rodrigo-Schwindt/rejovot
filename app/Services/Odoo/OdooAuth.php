<?php

namespace App\Services\Odoo;

use App\Models\Customer;
use App\Models\Salesperson;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Autenticación contra Odoo: cada uno entra con el mismo usuario y contraseña
 * que ya usa en el ERP. La contraseña no se guarda ni viaja a ningún lado más.
 *
 * El usuario local existe sólo para sostener la sesión y saber a quién
 * representa: un vendedor (usuario interno) o un cliente (usuario de portal).
 */
class OdooAuth
{
    public function __construct(protected OdooClient $odoo)
    {
    }

    /**
     * Valida las credenciales en Odoo y devuelve el usuario local, o null.
     */
    public function intentar(string $login, string $password): ?User
    {
        $uid = $this->validar($login, $password);

        if (! $uid) {
            return null;
        }

        $datos = $this->odoo->read('res.users', [$uid], ['name', 'login', 'share', 'partner_id', 'active']);
        $datos = $datos[0] ?? null;

        if (! $datos || empty($datos['active'])) {
            return null;
        }

        return empty($datos['share'])
            ? $this->comoVendedor($uid, $datos)
            : $this->comoCliente($uid, $datos);
    }

    /** Login contra Odoo. Devuelve el uid si las credenciales son válidas. */
    protected function validar(string $login, string $password): ?int
    {
        try {
            $uid = $this->odoo->autenticar($login, $password);
        } catch (OdooException) {
            return null;
        }

        return is_int($uid) && $uid > 0 ? $uid : null;
    }

    /** Usuario interno de Odoo: es un vendedor. */
    protected function comoVendedor(int $uid, array $datos): ?User
    {
        $vendedor = Salesperson::firstOrCreate(
            ['odoo_id' => $uid],
            ['name' => $datos['name'], 'login' => $datos['login'], 'active' => true],
        );

        return $this->guardarUsuario($uid, $datos, 'vendedor', [
            'salesperson_id' => $vendedor->id,
            'customer_id' => null,
        ]);
    }

    /** Usuario de portal: es un cliente. Tiene que estar sincronizado. */
    protected function comoCliente(int $uid, array $datos): ?User
    {
        $partnerId = $datos['partner_id'][0] ?? null;
        $cliente = $partnerId ? Customer::where('odoo_id', $partnerId)->first() : null;

        if (! $cliente) {
            return null;
        }

        return $this->guardarUsuario($uid, $datos, 'cliente', [
            'customer_id' => $cliente->id,
            'salesperson_id' => null,
        ]);
    }

    protected function guardarUsuario(int $uid, array $datos, string $rol, array $extra): User
    {
        $usuario = User::firstOrNew(['odoo_uid' => $uid]);

        $usuario->fill([
            'name' => $datos['name'],
            'email' => $datos['login'],
            'role' => $rol,
            'last_login_at' => now(),
        ] + $extra);

        // La contraseña real vive en Odoo; acá va un valor que nadie usa.
        $usuario->password ??= Hash::make(Str::random(40));
        $usuario->save();

        return $usuario;
    }
}
