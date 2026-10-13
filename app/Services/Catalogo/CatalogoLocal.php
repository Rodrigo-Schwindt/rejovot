<?php

namespace App\Services\Catalogo;

use App\Contracts\CatalogoRepository;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo real: lee la copia local que sincroniza `odoo:sync-catalog`.
 *
 * Sólo se publican los productos de Rejovot activos y marcados para la web.
 * Precios: `list_price` es la lista pública (lst_price_with_margin). El precio
 * del cliente y su descuento se resolverán en vivo cuando haya login.
 */
class CatalogoLocal implements CatalogoRepository
{
    /** Subconsulta con los ids que coinciden con el texto buscado. */
    private ?Builder $coincidencias = null;

    /** Lo que se escribió en el buscador, para ordenar por relevancia. */
    private string $textoBuscado = '';

    /** Palabras que no aportan a la búsqueda («disco de freno» = «disco freno»). */
    private const RELLENO = ['de', 'del', 'la', 'las', 'el', 'los', 'y', 'e', 'o', 'para', 'con', 'sin', 'por', 'en', 'a', 'al', 'un', 'una', 'x'];

    /** Lo que todavía no tiene datos en Odoo se sirve de la implementación demo. */
    public function __construct(private CatalogoDemo $demo)
    {
    }

    /**
     * Slides del banner: los productos que el admin marcó como destacados.
     *
     * Ojo con el precio: las reglas de descuento de Odoo no bajan el precio que
     * devuelve la API, así que el precio con descuento se calcula acá a partir
     * de la lista y del porcentaje de la tarifa; la lista queda como tachado.
     */
    public function ofertas(): array
    {
        return Product::with('brand')
            ->publicables()
            ->where('destacado', true)
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(function (Product $p) {
                $lista = (float) $p->list_price;
                $descuento = $p->en_oferta ? (float) $p->discount_percent : null;

                return [
                    'codigo' => $p->code ?? (string) $p->odoo_id,
                    'nombre' => $p->name,
                    'descuento' => $descuento,
                    'precio' => $descuento ? round($lista * (1 - $descuento / 100), 2) : $lista,
                    'precio_ant' => $descuento ? $lista : null,
                    'imagen' => $p->imagen_url,
                    'hasta' => $descuento ? $p->discount_to : null,
                ];
            })
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

    public function filtros(string $marca = ''): array
    {
        return [
            // Las categorías de ecommerce de Odoo, todas y en su orden (NOVEDADES
            // primero), tengan o no productos publicados hoy.
            'marcas' => Brand::whereNotNull('odoo_id')
                ->orderBy('sort')
                ->pluck('name')
                ->all(),
            // Para Márgenes: sólo marcas de verdad y con algo publicado; lo demás
            // no se puede comprar ni tiene sentido ponerle margen.
            'marcas_margen' => Brand::where('es_marca', true)
                ->whereHas('products', fn (Builder $q) => $q->publicables())
                ->orderBy('name')
                ->pluck('name')
                ->all(),
            // La misma relación de ecommerce que usa el filtro del listado.
            'rubros' => Category::whereHas('products', fn (Builder $q) => $q->publicables()
                ->when($marca !== '', fn (Builder $q) => $q->whereHas(
                    'categoriasWeb', fn (Builder $q) => $q->where('name', $marca),
                )))
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

    public function relacionados(string $codigo): array
    {
        $producto = Product::where('code', $codigo)->first();

        if (! $producto) {
            return [];
        }

        return $producto->relacionados()
            ->with(['category', 'brand'])
            ->publicables()
            // Los alternativos primero: son los que reemplazan al producto buscado.
            ->orderByRaw("FIELD(product_related.tipo, 'alternativo', 'accesorio')")
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => array_merge($this->comoArray($p), ['relacion' => $p->pivot->tipo]))
            ->all();
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
            $this->buscarTexto($query, $texto);
        }

        // Código del producto (referencia interna), distinto del código OEM.
        if (! empty($filtros['codigo'])) {
            $this->buscarTexto($query, trim($filtros['codigo']), soloCodigo: true);
        }

        if (! empty($filtros['tipo'])) {
            // El combo trabaja con el nombre visible; en base está la clave de Odoo.
            $clave = array_search($filtros['tipo'], Product::TIPOS, true);

            if ($clave === false) {
                return $query->whereRaw('1 = 0');
            }

            $query->where('type', $clave);
        }

        // Por categoría de ecommerce: incluye NOVEDADES, que no es la marca de nadie.
        if (! empty($filtros['marca'])) {
            $query->whereHas('categoriasWeb', fn (Builder $q) => $q->where('name', $filtros['marca']));
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

        if ($this->coincidencias) {
            $sub = $this->coincidencias->toSql();
            $bindings = $this->coincidencias->getBindings();

            // De qué producto buscado viene, para poder decirlo en la fila.
            $query->select('products.*')
                ->selectRaw(
                    '(SELECT CONCAT(pr.tipo, "|", origen.code)
                        FROM product_related pr
                        JOIN products origen ON origen.id = pr.product_id
                       WHERE pr.related_id = products.id
                         AND pr.product_id IN (' . $sub . ')
                       LIMIT 1) AS relacion_origen',
                    $bindings,
                )
                ->orderByRaw('CASE WHEN products.id IN (' . $sub . ') THEN 0 ELSE 1 END')
                ->addBinding($bindings, 'order');
        }

        // Más relevante primero: el código exacto, después la frase tal cual.
        if ($this->textoBuscado !== '') {
            $query->orderByRaw(
                'CASE WHEN products.code = ? THEN 0 WHEN products.name LIKE ? THEN 1 ELSE 2 END',
                [$this->textoBuscado, '%' . $this->escaparLike($this->textoBuscado) . '%'],
            );
        }

        return $query->orderBy('name');
    }

    /**
     * Busca por texto y suma los productos relacionados de lo que coincide.
     *
     * El cliente pidió que al buscar un código o un nombre aparezcan también
     * los alternativos y accesorios que Odoo le cargó a ese producto. Va todo
     * en una sola consulta: las coincidencias son una subconsulta.
     */
    private function buscarTexto(Builder $query, string $texto, bool $soloCodigo = false): void
    {
        $coincidencias = Product::query()->publicables();

        if ($soloCodigo) {
            // Filtro «Código»: sólo el código, con o sin puntos, barras o guiones.
            $coincidencias->where(fn (Builder $q) => $this->enCodigo($q, $texto));
        } else {
            // Buscador libre: cada palabra tiene que estar, en cualquier orden y
            // en cualquier campo («disco corsa» encuentra «DISCO FRENO CORSA»).
            foreach ($this->terminos($texto) as $termino) {
                $coincidencias->where(fn (Builder $q) => $this->enCualquierCampo($q, $termino));
            }

            $this->textoBuscado = $texto;
        }

        $coincidencias->select('id');

        $relacionados = DB::table('product_related')
            ->whereIn('product_id', (clone $coincidencias))
            ->select('related_id');

        $query->where(function (Builder $q) use ($coincidencias, $relacionados) {
            $q->whereIn('id', (clone $coincidencias))
                ->orWhereIn('id', $relacionados);
        });

        // Primero lo que coincide de verdad; después lo que se sumó por relación.
        $this->coincidencias = (clone $coincidencias);
    }

    /**
     * Palabras del buscador, sin relleno y sin la «s» del plural
     * («discos» → «disco», «cables» → «cabl»): así encuentra singular y plural.
     *
     * @return array<int, string>
     */
    private function terminos(string $texto): array
    {
        $palabras = preg_split('/[\s,;]+/u', mb_strtolower(trim($texto)), -1, PREG_SPLIT_NO_EMPTY);

        return collect($palabras)
            ->reject(fn (string $p) => in_array($p, self::RELLENO, true))
            ->map(function (string $p) {
                if (preg_match('/\d/', $p) || mb_strlen($p) < 4) {
                    return $p;
                }

                return match (true) {
                    mb_strlen($p) >= 5 && str_ends_with($p, 'es') => mb_substr($p, 0, -2),
                    str_ends_with($p, 's') => mb_substr($p, 0, -1),
                    default => $p,
                };
            })
            ->unique()
            ->values()
            ->all();
    }

    /** Una palabra en el nombre, el código, los OEM, la marca o el rubro. */
    private function enCualquierCampo(Builder $q, string $termino): void
    {
        $like = '%' . $this->escaparLike($termino) . '%';

        $q->where('name', 'like', $like)
            ->orWhere('oem_codes', 'like', $like)
            ->orWhereIn('brand_id', Brand::where('name', 'like', $like)->select('id'))
            ->orWhereIn('category_id', Category::where('name', 'like', $like)->select('id'))
            ->orWhere('code', 'like', $like);

        // Si tiene números puede ser un código escrito con otros separadores
        // («6246ff0321» / «6246/FF.0321»). Sólo ahí: comparar así es más lento.
        if (preg_match('/\d/', $termino) && ($limpio = $this->sinSeparadores($termino)) !== '') {
            $q->orWhereRaw($this->columnaSinSeparadores('code') . ' LIKE ?', ['%' . $limpio . '%'])
                ->orWhereRaw($this->columnaSinSeparadores('oem_codes') . ' LIKE ?', ['%' . $limpio . '%']);
        }
    }

    /** El código, escrito con o sin puntos, barras, guiones o espacios. */
    private function enCodigo(Builder $q, string $texto): void
    {
        $q->where('code', 'like', '%' . $this->escaparLike($texto) . '%');

        if (($limpio = $this->sinSeparadores($texto)) !== '') {
            $q->orWhereRaw($this->columnaSinSeparadores('code') . ' LIKE ?', ['%' . $limpio . '%']);
        }
    }

    private function sinSeparadores(string $texto): string
    {
        return $this->escaparLike((string) preg_replace('/[\s.\/\-_]+/', '', mb_strtolower($texto)));
    }

    private function columnaSinSeparadores(string $columna): string
    {
        return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE({$columna}, '.', ''), '/', ''), '-', ''), ' ', ''))";
    }

    private function escaparLike(string $texto): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $texto);
    }

    /** Traduce el modelo al formato que ya consumen las vistas. */
    private function comoArray(Product $p): array
    {
        $lista = (float) $p->list_price;
        $relacion = $p->relacion_origen ? explode('|', $p->relacion_origen, 2) : [];

        return [
            'codigo' => $p->code ?? (string) $p->odoo_id,
            'nombre' => $p->name,
            'descripcion' => $p->name,
            'marca' => $p->brand?->name ?? '',
            'rubro' => $p->category?->name ?? '',
            'oem' => $p->oem_codes ?? '',
            // IVA del producto en Odoo: la mayoría 21%, los FISPA 10,5%.
            'iva' => (float) $p->tax_percent,
            // Viene de la búsqueda: si la fila entró por ser alternativo o
            // accesorio de otro producto, acá está de cuál.
            'relacion' => $relacion[0] ?? null,
            'relacion_de' => $relacion[1] ?? null,
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
            'imagenes' => $p->imagenes,
            'aplicaciones' => [],
            'atributos' => [],
            'vehiculos' => [],
        ];
    }
}
