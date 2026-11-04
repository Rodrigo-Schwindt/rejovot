<?php

namespace App\Services\Carrito;

use App\Contracts\CatalogoRepository;
use App\Services\Margenes\Margenes;
use App\Services\Odoo\OdooEnvios;
use App\Services\Sesion\ClienteActivo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Carrito del cliente activo. Cada cliente tiene **dos**: el principal y uno
 * alternativo, y los dos viven en la base: el vendedor y el cliente, o el
 * cliente desde varios dispositivos, operan los mismos carritos y ven lo que
 * agrega o envía el otro. Con cuál está trabajando cada uno lo elige cada
 * persona (queda en su sesión). Sin cliente elegido, todo queda en la sesión.
 *
 * Los productos se resuelven contra CatalogoRepository; al confirmar,
 * `EnviarPedido` lo convierte en un pedido de Odoo (sale.order).
 */
class Carrito
{
    private const SESSION_KEY = 'carrito';

    /** Con qué carrito trabaja esta persona (cada uno elige el suyo). */
    private const SESSION_ACTIVO = 'carrito_activo';

    public const PRINCIPAL = 'principal';

    public const ALTERNATIVO = 'alternativo';

    public function __construct(
        private CatalogoRepository $catalogo,
        private Margenes $margenes,
        private ClienteActivo $clienteActivo,
    ) {
    }

    /** @return array<int, array{producto: array, cantidad: int, subtotal: float}> */
    public function items(): array
    {
        $items = [];

        foreach ($this->lineas() as $codigo => $cantidad) {
            $producto = $this->catalogo->detalle((string) $codigo);

            if (! $producto) {
                continue;
            }

            // El precio del carrito es el mismo del catálogo: lista menos el
            // descuento que el cliente configuró en Márgenes.
            $producto = $this->margenes->aplicar($producto);

            $items[] = [
                'producto' => $producto,
                'cantidad' => (int) $cantidad,
                'subtotal' => round($producto['costo'] * (int) $cantidad, 2),
            ];
        }

        return $items;
    }

    /** Lo que entra en el pedido: sólo lo que tiene stock. */
    public function itemsConStock(): array
    {
        return array_values(array_filter($this->items(), fn (array $item) => $item['producto']['stock'] !== 'rojo'));
    }

    /** Lo que espera en el carrito hasta que ingrese stock. */
    public function itemsSinStock(): array
    {
        return array_values(array_filter($this->items(), fn (array $item) => $item['producto']['stock'] === 'rojo'));
    }

    /** Carrito con el que trabaja esta persona: principal o alternativo. */
    public function activo(): string
    {
        return Session::get(self::SESSION_ACTIVO) === self::ALTERNATIVO ? self::ALTERNATIVO : self::PRINCIPAL;
    }

    public function usar(string $carrito): void
    {
        Session::put(self::SESSION_ACTIVO, $carrito === self::ALTERNATIVO ? self::ALTERNATIVO : self::PRINCIPAL);
    }

    public function esAlternativo(): bool
    {
        return $this->activo() === self::ALTERNATIVO;
    }

    public function agregar(string $codigo, int $cantidad = 1): void
    {
        $cantidad = max(1, $cantidad);

        if ($cliente = $this->clienteId()) {
            $this->sumarEnBase($cliente, $this->activo(), $codigo, $cantidad);

            return;
        }

        $lineas = $this->lineas();
        $lineas[$codigo] = ($lineas[$codigo] ?? 0) + $cantidad;

        Session::put($this->claveSesion(), $lineas);
    }

    /**
     * Suma en una sola sentencia: si dos personas agregan el mismo producto a
     * la vez, no se pisa ninguna de las dos cantidades.
     */
    private function sumarEnBase(int $cliente, string $carrito, string $codigo, int $cantidad): void
    {
        DB::statement(
            'insert into cart_items (customer_id, carrito, codigo, cantidad, created_at, updated_at) values (?, ?, ?, ?, ?, ?)
             on duplicate key update cantidad = cantidad + values(cantidad), updated_at = values(updated_at)',
            [$cliente, $carrito, $codigo, $cantidad, now(), now()],
        );
    }

    public function actualizar(string $codigo, int $cantidad): void
    {
        $cantidad = max(1, $cantidad);

        if ($cliente = $this->clienteId()) {
            $this->enBase($cliente)
                ->where('codigo', $codigo)
                ->update(['cantidad' => $cantidad, 'updated_at' => now()]);

            return;
        }

        $lineas = $this->lineas();

        if (isset($lineas[$codigo])) {
            $lineas[$codigo] = $cantidad;
            Session::put($this->claveSesion(), $lineas);
        }
    }

    public function quitar(string $codigo): void
    {
        $this->quitarVarios([$codigo]);
    }

    /** @param  array<int, string>  $codigos */
    public function quitarVarios(array $codigos): void
    {
        if ($cliente = $this->clienteId()) {
            $this->enBase($cliente)
                ->whereIn('codigo', $codigos)
                ->delete();

            return;
        }

        $lineas = $this->lineas();

        foreach ($codigos as $codigo) {
            unset($lineas[$codigo]);
        }

        Session::put($this->claveSesion(), $lineas);
    }

    public function vaciar(): void
    {
        if ($cliente = $this->clienteId()) {
            $this->enBase($cliente)->delete();

            return;
        }

        Session::forget($this->claveSesion());
    }

    /**
     * Huella del contenido. Las pantallas abiertas la comparan cada tanto para
     * enterarse de lo que cambió otra persona en el mismo carrito.
     */
    public function firma(): string
    {
        // Con el carrito activo: al pasar al otro, la pantalla se actualiza.
        return md5($this->activo() . json_encode($this->lineas()));
    }

    /** @return array<string, int> código => cantidad, del carrito activo */
    public function cantidades(): array
    {
        return $this->lineas();
    }

    /** @return array<int, string> códigos de los productos cargados */
    public function codigos(): array
    {
        return array_map('strval', array_keys($this->lineas()));
    }

    public function cantidadTotal(): int
    {
        return (int) array_sum($this->lineas());
    }

    /** Importe total del carrito. */
    public function subtotal(): float
    {
        return collect($this->items())->sum('subtotal');
    }

    /** Importe de lo que entra en el pedido: lo sin stock no se cobra ni se cuenta. */
    public function subtotalConStock(): float
    {
        return collect($this->itemsConStock())->sum('subtotal');
    }

    /** Lo mismo pero a precio de lista, para poder mostrar cuánto se descontó. */
    public function subtotalListaConStock(): float
    {
        return round(collect($this->itemsConStock())
            ->sum(fn (array $item) => (float) $item['producto']['lista'] * $item['cantidad']), 2);
    }

    /** El envío se bonifica según lo que diga la forma de entrega de Odoo. */
    public function envioBonificado(?array $envio): bool
    {
        return $envio
            && $envio['gratis_desde'] !== null
            && $this->subtotalConStock() >= $envio['gratis_desde'];
    }

    /** @param array|null $envio forma de entrega de delivery.carrier */
    /**
     * IVA de lo que entra en el pedido, calculado línea por línea: cada
     * producto tiene el suyo en Odoo («Impuestos cliente»).
     *
     * @return array{monto:float, tasas:array<int, float>}
     */
    public function ivaConStock(): array
    {
        $monto = 0.0;
        $tasas = [];

        foreach ($this->itemsConStock() as $item) {
            $tasa = (float) ($item['producto']['iva'] ?? config('carrito.iva'));
            $monto += $item['subtotal'] * $tasa / 100;
            $tasas[(string) $tasa] = $tasa;
        }

        return ['monto' => round($monto, 2), 'tasas' => array_values($tasas)];
    }

    public function totales(?array $envio): array
    {
        $lista = $this->subtotalListaConStock();
        $subtotal = $this->subtotalConStock();
        $envio = app(OdooEnvios::class)->costo($envio, $subtotal);

        $ivaProductos = $this->ivaConStock();
        // El envío va al 21%: es el impuesto del producto de entrega en Odoo.
        $iva = round($ivaProductos['monto'] + $envio * (float) config('carrito.iva') / 100, 2);

        return [
            // A precio de lista, antes del descuento del cliente.
            'lista' => $lista,
            'descuento' => round($lista - $subtotal, 2),
            'descuento_porcentaje' => $this->margenes->descuento(),
            'subtotal' => $subtotal,
            'envio' => $envio,
            'iva' => $iva,
            // Si todo el carrito comparte tasa, se muestra el porcentaje.
            'iva_tasa' => count($ivaProductos['tasas']) === 1 && $envio <= 0
                ? $ivaProductos['tasas'][0]
                : null,
            'total' => $subtotal + $envio + $iva,
        ];
    }

    /**
     * @return array<string, int> código => cantidad, lo último agregado primero
     * (así lo pidió Rejovot: arriba de todo en el carrito). Cambiar la cantidad
     * de un producto no lo mueve de lugar.
     */
    private function lineas(): array
    {
        if (! $cliente = $this->clienteId()) {
            return array_reverse(Session::get($this->claveSesion(), []), true);
        }

        $this->pasarCarritoDeSesion($cliente);

        return $this->enBase($cliente)
            ->orderByDesc('id')
            ->pluck('cantidad', 'codigo')
            ->map(fn ($cantidad) => (int) $cantidad)
            ->all();
    }

    private function clienteId(): ?int
    {
        return $this->clienteActivo->actual()?->id;
    }

    /** Las filas del carrito activo de ese cliente. */
    private function enBase(int $cliente): \Illuminate\Database\Query\Builder
    {
        return DB::table('cart_items')
            ->where('customer_id', $cliente)
            ->where('carrito', $this->activo());
    }

    /** Sin cliente elegido, cada carrito en su propia clave de sesión. */
    private function claveSesion(): string
    {
        return $this->esAlternativo() ? self::SESSION_KEY . '_' . self::ALTERNATIVO : self::SESSION_KEY;
    }

    /**
     * Lo que haya quedado en el carrito de sesión (de antes de que fuera
     * compartido) se suma al del cliente una sola vez.
     */
    private function pasarCarritoDeSesion(int $cliente): void
    {
        if (! Session::has(self::SESSION_KEY)) {
            return;
        }

        $viejo = Session::pull(self::SESSION_KEY, []);

        // Es de antes de que hubiera dos: va al principal.
        foreach ($viejo as $codigo => $cantidad) {
            $this->sumarEnBase($cliente, self::PRINCIPAL, (string) $codigo, (int) $cantidad);
        }
    }
}
