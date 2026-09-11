<?php

namespace App\Services\Odoo;

use Illuminate\Support\Facades\Cache;

/**
 * Formas de entrega publicadas en Odoo (delivery.carrier). Reemplazan a los
 * valores fijos del mockup: el costo y el mínimo de envío gratis salen del ERP.
 */
class OdooEnvios
{
    private const CACHE_KEY = 'odoo:envios';

    private const CACHE_MINUTOS = 60;

    public function __construct(protected OdooClient $odoo)
    {
    }

    /**
     * @return array<int, array{id:int, nombre:string, precio:float, gratis_desde:?float, producto_id:?int}>
     */
    public function disponibles(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_MINUTOS), function () {
            $rows = $this->odoo->searchRead('delivery.carrier', [
                ['website_published', '=', true],
                ['company_id', '=', config('odoo.company_id')],
                ['active', '=', true],
            ], ['name', 'delivery_type', 'fixed_price', 'free_over', 'amount', 'product_id', 'sequence'], [
                'order' => 'sequence asc, id asc',
            ]);

            return array_map(fn (array $row) => [
                'id' => (int) $row['id'],
                'nombre' => self::limpiarNombre($row['name']),
                'precio' => (float) ($row['fixed_price'] ?? 0),
                'gratis_desde' => ! empty($row['free_over']) ? (float) $row['amount'] : null,
                'producto_id' => $row['product_id'][0] ?? null,
            ], $rows);
        });
    }

    public function porId(int $id): ?array
    {
        foreach ($this->disponibles() as $envio) {
            if ($envio['id'] === $id) {
                return $envio;
            }
        }

        return null;
    }

    /** El primero de la lista es el que viene seleccionado por defecto. */
    public function porDefecto(): ?array
    {
        return $this->disponibles()[0] ?? null;
    }

    /** Costo del envío para un importe de pedido, contemplando el envío bonificado. */
    public function costo(?array $envio, float $importe): float
    {
        if (! $envio) {
            return 0.0;
        }

        if ($envio['gratis_desde'] !== null && $importe >= $envio['gratis_desde']) {
            return 0.0;
        }

        return $envio['precio'];
    }

    public function olvidarCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Los nombres vienen con relleno: "Retiro por REJOVOT .. --- MOSTRADOR ---..". */
    /** Los nombres de Odoo vienen con asteriscos y guiones de relleno. */
    public static function limpiarNombre(string $nombre): string
    {
        $limpio = preg_replace('/[*._\-]{2,}/u', ' ', $nombre);
        $limpio = preg_replace('/\s+/u', ' ', (string) $limpio);

        return trim((string) $limpio);
    }
}
