<?php

namespace App\Services\Sesion;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Quién es el cliente del pedido en curso.
 *
 * - Un cliente logueado siempre es él mismo y no puede cambiarlo.
 * - Un vendedor tiene que elegir uno de su cartera: sin cliente elegido no
 *   puede agregar al carrito ni confirmar pedidos.
 */
class ClienteActivo
{
    private const SESSION_KEY = 'cliente_activo';

    public function actual(): ?Customer
    {
        $usuario = Auth::guard('sitio')->user();

        if ($usuario?->esCliente()) {
            return $usuario->customer;
        }

        if (! $usuario?->esVendedor()) {
            return null;
        }

        $id = Session::get(self::SESSION_KEY);

        if (! $id) {
            return null;
        }

        // Sólo puede operar sobre su propia cartera.
        return Customer::where('id', $id)
            ->where('salesperson_id', $usuario->salesperson_id)
            ->first();
    }

    /** El vendedor elige un cliente de su cartera. */
    public function elegir(int $customerId): bool
    {
        $usuario = Auth::guard('sitio')->user();

        if (! $usuario?->esVendedor()) {
            return false;
        }

        $cliente = Customer::where('id', $customerId)
            ->where('salesperson_id', $usuario->salesperson_id)
            ->first();

        if (! $cliente) {
            return false;
        }

        Session::put(self::SESSION_KEY, $cliente->id);

        return true;
    }

    public function limpiar(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /** El vendedor puede cambiar de cliente; el cliente no. */
    public function puedeElegir(): bool
    {
        return (bool) Auth::guard('sitio')->user()?->esVendedor();
    }

    /** Sin cliente no se agrega al carrito ni se confirma un pedido. */
    public function puedeOperar(): bool
    {
        return $this->actual() !== null;
    }

    /** Motivo para mostrar en pantalla cuando no se puede operar. */
    public function motivo(): ?string
    {
        if ($this->puedeOperar()) {
            return null;
        }

        return Auth::guard('sitio')->user()?->esVendedor()
            ? 'Elegí un cliente para poder cargar productos.'
            : 'Ingresá con tu usuario para poder comprar.';
    }
}
