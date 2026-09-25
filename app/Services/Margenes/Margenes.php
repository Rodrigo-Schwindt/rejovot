<?php

namespace App\Services\Margenes;

use App\Models\MarginSetting;
use App\Services\Sesion\ClienteActivo;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * Márgenes que el cliente aplica sobre la lista de precios.
 *
 * Se guardan **por cliente**, no por navegador: si en una misma máquina entran
 * dos vendedores, cada uno ve los del cliente que esté atendiendo, y el cliente
 * los encuentra iguales desde cualquier computadora. Un visitante sin cliente
 * elegido los tiene sólo en su sesión, para poder probar.
 *
 * Prioridad al calcular el precio de venta: marca > familia (rubro) > general.
 */
class Margenes
{
    private const SESSION_KEY = 'margenes';

    /** Se leen muchas veces por request (una por producto): se cachean. */
    private ?array $cache = null;

    public function __construct(private ClienteActivo $clienteActivo)
    {
    }

    public const POR_DEFECTO = 5.0;

    /** El descuento arranca en cero: lo define el cliente desde Márgenes. */
    public const DESCUENTO_POR_DEFECTO = 0.0;

    public function general(): float
    {
        return (float) ($this->todos()['general'] ?? self::POR_DEFECTO);
    }

    /**
     * Descuento sobre la lista de precios. Es lo que separa «Lista» de
     * «Tu precio» en el catálogo: lo administra el cliente desde la web, no
     * sale del descuento que tiene cargado en Odoo.
     */
    public function descuento(): float
    {
        return (float) ($this->todos()['descuento'] ?? self::DESCUENTO_POR_DEFECTO);
    }

    public function guardarDescuento(float $valor): void
    {
        $this->guardar(['descuento' => $this->normalizarDescuento($valor)] + $this->todos());
    }

    /** Precio del cliente: la lista menos el descuento que él configuró. */
    public function precioNeto(float $lista): float
    {
        return round($lista * (1 - $this->descuento() / 100), 2);
    }

    /** @return array<string, float> margen por marca, indexado por clave */
    public function marcas(): array
    {
        return $this->todos()['marcas'] ?? [];
    }

    /** @return array<string, float> margen por familia (rubro), indexado por clave */
    public function familias(): array
    {
        return $this->todos()['familias'] ?? [];
    }

    public function guardarGeneral(float $valor): void
    {
        $this->guardar(['general' => $this->normalizar($valor)] + $this->todos());
    }

    public function guardarMarca(string $clave, float $valor): void
    {
        $todos = $this->todos();
        $todos['marcas'][$clave] = $this->normalizar($valor);

        $this->guardar($todos);
    }

    public function guardarFamilia(string $clave, float $valor): void
    {
        $todos = $this->todos();
        $todos['familias'][$clave] = $this->normalizar($valor);

        $this->guardar($todos);
    }

    /** Margen que corresponde a un producto del catálogo. */
    public function paraProducto(array $producto): float
    {
        $marca = self::clave($producto['marca'] ?? '');
        $familia = self::clave($producto['rubro'] ?? '');

        return (float) ($this->marcas()[$marca]
            ?? $this->familias()[$familia]
            ?? $this->general());
    }

    /**
     * Devuelve el producto con el descuento, el markup y el precio de venta
     * recalculados: «Tu precio» es la lista menos el descuento, y el precio de
     * venta es eso más el margen.
     */
    public function aplicar(array $producto): array
    {
        $margen = $this->paraProducto($producto);

        $producto['costo'] = $this->precioNeto((float) ($producto['lista'] ?? $producto['costo'] ?? 0));
        $producto['descuento_lista'] = $this->descuento();
        $producto['markup'] = $margen;
        $producto['precio_venta'] = round($producto['costo'] * (1 + $margen / 100), 2);

        return $producto;
    }

    /** Las claves viajan en wire:model, así que van sin acentos ni espacios. */
    public static function clave(string $nombre): string
    {
        return Str::slug($nombre, '_') ?: 'sin_dato';
    }

    private function normalizar(float $valor): float
    {
        return max(0, min(1000, round($valor, 2)));
    }

    /** Un descuento no puede pasar del 100%: regalaría el producto. */
    private function normalizarDescuento(float $valor): float
    {
        return max(0, min(100, round($valor, 2)));
    }

    private function porDefecto(): array
    {
        return [
            'general' => self::POR_DEFECTO,
            'descuento' => self::DESCUENTO_POR_DEFECTO,
            'marcas' => [],
            'familias' => [],
        ];
    }

    private function todos(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $cliente = $this->clienteActivo->actual();

        if (! $cliente) {
            return $this->cache = Session::get(self::SESSION_KEY, $this->porDefecto());
        }

        $fila = MarginSetting::firstWhere('customer_id', $cliente->id);

        return $this->cache = $fila ? [
            'general' => $fila->general,
            'descuento' => $fila->descuento,
            'marcas' => $fila->marcas ?? [],
            'familias' => $fila->familias ?? [],
        ] : $this->porDefecto();
    }

    private function guardar(array $margenes): void
    {
        $this->cache = $margenes;

        $cliente = $this->clienteActivo->actual();

        if (! $cliente) {
            Session::put(self::SESSION_KEY, $margenes);

            return;
        }

        MarginSetting::updateOrCreate(['customer_id' => $cliente->id], [
            'general' => $margenes['general'] ?? self::POR_DEFECTO,
            'descuento' => $margenes['descuento'] ?? self::DESCUENTO_POR_DEFECTO,
            'marcas' => $margenes['marcas'] ?? [],
            'familias' => $margenes['familias'] ?? [],
        ]);
    }
}
