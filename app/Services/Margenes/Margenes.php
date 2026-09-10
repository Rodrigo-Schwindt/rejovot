<?php

namespace App\Services\Margenes;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * Márgenes que el cliente aplica sobre la lista de precios.
 * Hoy viven en sesión; cuando haya login pasan a guardarse por usuario.
 *
 * Prioridad al calcular el precio de venta: marca > familia (rubro) > general.
 */
class Margenes
{
    private const SESSION_KEY = 'margenes';

    public const POR_DEFECTO = 5.0;

    public function general(): float
    {
        return (float) ($this->todos()['general'] ?? self::POR_DEFECTO);
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

    /** Devuelve el producto con el markup y el precio de venta recalculados. */
    public function aplicar(array $producto): array
    {
        $margen = $this->paraProducto($producto);

        $producto['markup'] = $margen;
        $producto['precio_venta'] = round(($producto['costo'] ?? 0) * (1 + $margen / 100), 2);

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

    private function todos(): array
    {
        return Session::get(self::SESSION_KEY, ['general' => self::POR_DEFECTO, 'marcas' => [], 'familias' => []]);
    }

    private function guardar(array $margenes): void
    {
        Session::put(self::SESSION_KEY, $margenes);
    }
}
