<?php

namespace App\Services\Catalogo;

use App\Contracts\CatalogoRepository;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Catálogo real: lee la copia local que sincroniza `odoo:sync-catalog`.
 *
 * Sólo se publican los productos de Rejovot activos y marcados para la web.
 * Precios: `list_price` es la lista pública (lst_price_with_margin). El precio
 * del cliente y su descuento se resolverán en vivo cuando haya login.
 */
class CatalogoLocal implements CatalogoRepository
{
    /** Lo que todavía no tiene datos en Odoo se sirve de la implementación demo. */
    public function __construct(private CatalogoDemo $demo)
    {
    }

    /**
     * Slides del banner: los productos que el admin marcó como destacados.
     *
     * Ojo con el precio: las reglas de descuento de Odoo hoy no bajan el precio
     * que devuelve la API, así que no se muestra un precio anterior tachado
     * salvo que el descuento se refleje de verdad.
     */
    public function ofertas(): array
    {
        return Product::with('brand')
            ->publicables()
            ->where('destacado', true)
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(fn (Product $p) => [
                'codigo' => $p->code ?? (string) $p->odoo_id,
                'nombre' => $p->name,
                'descuento' => $p->en_oferta ? (float) $p->discount_percent : null,
                'precio' => (float) $p->list_price,
                'precio_ant' => null,
                'imagen' => $p->imagen_url,
                'hasta' => $p->en_oferta ? $p->discount_to : null,
            ])
            ->all();
    }

    public function clientes(): array
    {
        return $this->demo->clientes();
    }

    public function vehiculos(): array
    {
        return $this->demo->vehiculos();
    }

    public function filtros(): array
    {
        return [
            'marcas' => Brand::orderBy('name')->pluck('name')->all(),
            // El rubro es la categoría de Odoo: son muchas, el combo las busca.
            'rubros' => Category::whereHas('products', fn (Builder $q) => $q->publicables())
                ->orderBy('name')
                ->pluck('name')
                ->all(),
            // Tipo de producto de Odoo: sólo los que realmente hay publicados.
            'tipos' => Product::publicables()
                ->whereNotNull('type')
                ->distinct()
                ->pluck('type')
                ->map(fn (string $t) => Product::TIPOS[$t] ?? null)
                ->filter()
                ->sort()
                ->values()
                ->all(),
        ];
    }

    public function paginados(array $filtros = [], int $porPagina = 20): LengthAwarePaginator
    {
        return $this->consulta($filtros)
            ->paginate($porPagina)
            ->through(fn (Product $p) => $this->comoArray($p));
    }

    public function productos(array $filtros = [], int $limite = 100): array
    {
        return $this->consulta($filtros)
            ->limit($limite)
            ->get()
            ->map(fn (Product $p) => $this->comoArray($p))
            ->all();
    }

    public function detalle(string $codigo): ?array
    {
        $producto = Product::with(['category', 'brand'])
            ->publicables()
            ->where('code', $codigo)
            ->first();

        return $producto ? $this->comoArray($producto) : null;
    }

    /** Arma la consulta con los filtros del buscador. */
    private function consulta(array $filtros): Builder
    {
        $query = Product::with(['category', 'brand'])->publicables();

        // Odoo no tiene datos de vehículos: si filtran por eso, no hay resultados.
        if (! empty($filtros['vehiculo_marca']) || ! empty($filtros['vehiculo_modelo'])) {
            return $query->whereRaw('1 = 0');
        }

        // Buscador libre: código, descripción y códigos OEM.
        $texto = trim((string) ($filtros['q'] ?? ''));

        if ($texto !== '') {
            $query->where(function (Builder $q) use ($texto) {
                $q->where('name', 'like', '%' . $texto . '%')
                    ->orWhere('code', 'like', '%' . $texto . '%')
                    ->orWhere('oem_codes', 'like', '%' . $texto . '%');
            });
        }

        // Código del producto (referencia interna), distinto del código OEM.
        if (! empty($filtros['codigo'])) {
            $query->where('code', 'like', '%' . trim($filtros['codigo']) . '%');
        }

        if (! empty($filtros['tipo'])) {
            // El combo trabaja con el nombre visible; en base está la clave de Odoo.
            $clave = array_search($filtros['tipo'], Product::TIPOS, true);

            if ($clave === false) {
                return $query->whereRaw('1 = 0');
            }

            $query->where('type', $clave);
        }

        if (! empty($filtros['marca'])) {
            $query->whereHas('brand', fn (Builder $q) => $q->where('name', $filtros['marca']));
        }

        if (! empty($filtros['rubro'])) {
            $query->whereHas('category', fn (Builder $q) => $q->where('name', $filtros['rubro']));
        }

        if (! empty($filtros['oem'])) {
            $query->where('oem_codes', 'like', '%' . trim($filtros['oem']) . '%');
        }

        if (! empty($filtros['solo_ofertas'])) {
            // Descuentos vigentes de la tarifa de Odoo (odoo:sync-ofertas).
            $query->enOferta();
        }

        return $query->orderBy('name');
    }

    /** Traduce el modelo al formato que ya consumen las vistas. */
    private function comoArray(Product $p): array
    {
        $lista = (float) $p->list_price;

        return [
            'codigo' => $p->code ?? (string) $p->odoo_id,
            'nombre' => $p->name,
            'descripcion' => $p->name,
            'marca' => $p->brand?->name ?? '',
            'rubro' => $p->category?->name ?? '',
            'oem' => $p->oem_codes ?? '',
            'tipo' => $p->tipo_nombre,
            // Hasta que haya login, el precio del cliente es la lista pública.
            'costo' => $lista,
            'lista' => $lista,
            'precio_venta' => $lista,
            'markup' => 0,
            'stock' => $p->semaforo,
            'unidades' => (float) $p->stock,
            'oferta' => $p->en_oferta,
            'descuento' => $p->en_oferta ? (float) $p->discount_percent : null,
            'imagen' => $p->imagen_url,
            'aplicaciones' => [],
            'atributos' => [],
            'vehiculos' => [],
        ];
    }
}
