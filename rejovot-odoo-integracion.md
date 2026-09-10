# Integración Laravel ↔ Odoo — Rejovot Autopartes

Brief para implementar la capa de datos de la web de Rejovot contra Odoo.
Todo lo que sigue fue verificado contra la instancia real vía RPC.

---

## 1. La instancia

| Dato | Valor |
|---|---|
| URL | `https://rejovot.blueorange.com.ar` |
| Versión | Odoo **15.0 Enterprise** (`15.0+e-20240924`) |
| Base de datos | `REJOVOT` |
| Endpoints externos | `/jsonrpc` y `/xmlrpc/2/` — ambos responden desde afuera |
| Productos | 43.450 (`product.template` = `product.product`, sin variantes) |
| Partners | 64.944 |
| Pedidos de venta | 184.454 |
| Asientos contables | 515.283 |
| Quants de stock | 154.157 |
| Tarifas | 3 → `1 Public Pricelist`, `14 Tarifa Pública BENEIBRAK`, `16 Promociones` |
| Depósitos | `1 REJOVOT (WH)`, `2 PRUEBA`, `3 Beneibrak S.A.`, `4 DIEGO`, `6/7 Mercadolibre…` |

Módulos relevantes instalados: `product_price_margin`, `product_pricelist_supplierinfo`,
`website_sale_available_stock`, `sale_invoice_by_item`, `bo_customization`,
`mercadolibre_product`, `l10n_ar_*`.

### Credenciales

No usar la password del usuario de gerencia. Crear un **usuario de servicio** y generar una
**API Key** en Ajustes → Usuarios → *Developer API Keys*. La key se usa en lugar de la password
en `login` y en `execute_kw`.

```env
ODOO_URL=https://rejovot.blueorange.com.ar
ODOO_DB=REJOVOT
ODOO_USER=api@rejovot.com.ar
ODOO_KEY=xxxxxxxxxxxxxxxx
ODOO_PRICELIST_ID=1
ODOO_WAREHOUSE_ID=1
ODOO_STOCK_LOCATION_ID=12
```

---

## 2. Reglas de oro

1. **`list_price` y `standard_price` están en 0 en todos los productos.** Si leés esos campos
   te va a dar todo cero. El precio real se resuelve por tarifa.
2. **Nunca recalcular precios en PHP.** El precio se pide a Odoo con
   `product.pricelist.price_get(pricelist_id, product_id, qty)` o leyendo el campo `price` de
   `product.product` con `context = {pricelist: X, quantity: n, partner: Y}`. Ambos devuelven
   lo mismo (verificado: producto `BS009.0868` → `82.664,24`).
3. **No consultar Odoo en cada request.** Con 43k productos el catálogo se sincroniza a la base
   local (job incremental por `write_date`) y sólo stock y precio del cliente logueado se piden
   en vivo, con caché corto (60–300 s).
4. Los precios que devuelve Odoo son **sin IVA**. Los impuestos del producto vienen en `taxes_id`.
5. La tarifa `Public Pricelist` usa items con `compute_price = formula`, `base = supplierinfo`
   y `price_discount` (ej. 7%), muchos con `date_start`/`date_end` → **esas son las "Ofertas"**.
   Para el toggle "Ofertas" filtrar `product.pricelist.item` con fechas vigentes.

---

## 3. Mapeo pantalla → Odoo

| Campo de la vista | Modelo | Campo |
|---|---|---|
| Código (`BS009.0868`) | `product.product` | `default_code` |
| Descripción | `product.template` | `name` |
| OEM | `product.template` | `oem_code` (custom, texto con varios códigos separados por espacio) |
| Rubro | `product.category` | `categ_id` → `complete_name` (669 categorías) |
| Imagen | — | `/web/image/product.template/{id}/image_512` |
| Costo | `product.supplierinfo` | `price` (filtrar por `product_tmpl_id`, tomar el proveedor real, no "REJOVOT AUTOPARTES S.A.") |
| Precio de lista | `product.pricelist` | `price_get(1, product_id, qty)` |
| Precio venta con markup | `product.product` | `lst_price_with_margin` (módulo `product_price_margin`) |
| Stock (semáforo) | `product.product` | `qty_available` / `virtual_available`, o `stock.quant` por ubicación |
| Cliente | `res.partner` | `name`, `vat`, `property_product_pricelist`, `property_payment_term_id`, `credit`, `credit_limit` |
| Carrito / Mis Pedidos | `sale.order` + `sale.order.line` | |
| Estado de la Cuenta | `account.move` / `account.move.line` | `amount_residual`, `invoice_date_due`, `move_type` |
| Info de Pagos | `account.payment` | |
| Márgenes | `product.template` | `lst_price_with_margin` vs `price_get` |

### Lo que NO existe en Odoo

Verificado: no hay modelos ni campos de vehículos, aplicaciones ni equivalencias.

- **Aplicaciones** (marca / modelo / versión / año de vehículo) → no existe nada.
- **Equivalencias** → no existe; sólo `oem_code` como texto plano.
- **Atributos técnicos** (línea, modelo, cantidad de dientes, ancho, perfil) → no cargados.
  El único `product.attribute` es "Brand" y los productos no tienen líneas de atributos.
- **Marca del producto** → no hay campo limpio. Hoy se infiere del prefijo del nombre
  (`VMG`, `OMER`, `BAIML`, `GACRI`) o del proveedor en `product.supplierinfo`.

Estas cuatro cosas hay que resolverlas con **tablas propias en la web** (recomendado) o creando
modelos custom en Odoo. Diseñar el schema local pensando en eso desde el arranque.

---

## 4. Cliente RPC

`config/odoo.php`

```php
<?php

return [
    'url' => env('ODOO_URL'),
    'db' => env('ODOO_DB'),
    'user' => env('ODOO_USER'),
    'key' => env('ODOO_KEY'),
    'pricelist_id' => (int) env('ODOO_PRICELIST_ID', 1),
    'warehouse_id' => (int) env('ODOO_WAREHOUSE_ID', 1),
    'stock_location_id' => (int) env('ODOO_STOCK_LOCATION_ID', 12),
    'timeout' => 30,
];
```

`app/Services/Odoo/OdooException.php`

```php
<?php

namespace App\Services\Odoo;

use Exception;

class OdooException extends Exception {}
```

`app/Services/Odoo/OdooClient.php`

```php
<?php

namespace App\Services\Odoo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OdooClient
{
    protected ?int $uid = null;

    public function __construct(protected array $config) {}

    public function uid(): int
    {
        if ($this->uid !== null) {
            return $this->uid;
        }

        $this->uid = Cache::remember('odoo:uid', 3600, function () {
            $uid = $this->rpc('common', 'login', [
                $this->config['db'],
                $this->config['user'],
                $this->config['key'],
            ]);

            if (! is_int($uid) || $uid <= 0) {
                throw new OdooException('Credenciales de Odoo inválidas');
            }

            return $uid;
        });

        return $this->uid;
    }

    public function call(string $model, string $method, array $args = [], array $kwargs = []): mixed
    {
        return $this->rpc('object', 'execute_kw', [
            $this->config['db'],
            $this->uid(),
            $this->config['key'],
            $model,
            $method,
            $args,
            (object) $kwargs,
        ]);
    }

    public function searchRead(string $model, array $domain, array $fields, array $options = []): array
    {
        return $this->call($model, 'search_read', [$domain, $fields], $options);
    }

    public function searchCount(string $model, array $domain): int
    {
        return $this->call($model, 'search_count', [$domain]);
    }

    public function read(string $model, array $ids, array $fields, array $context = []): array
    {
        return $this->call($model, 'read', [$ids, $fields], $context ? ['context' => $context] : []);
    }

    public function create(string $model, array $values): int
    {
        return $this->call($model, 'create', [$values]);
    }

    public function write(string $model, array $ids, array $values): bool
    {
        return $this->call($model, 'write', [$ids, $values]);
    }

    protected function rpc(string $service, string $method, array $args): mixed
    {
        $response = Http::timeout($this->config['timeout'])
            ->acceptJson()
            ->post(rtrim($this->config['url'], '/').'/jsonrpc', [
                'jsonrpc' => '2.0',
                'method' => 'call',
                'params' => compact('service', 'method', 'args'),
                'id' => uniqid(),
            ]);

        if ($response->failed()) {
            throw new OdooException("HTTP {$response->status()} desde Odoo");
        }

        $body = $response->json();

        if (isset($body['error'])) {
            throw new OdooException($body['error']['data']['message'] ?? $body['error']['message'] ?? 'Error de Odoo');
        }

        return $body['result'] ?? null;
    }
}
```

`app/Providers/AppServiceProvider.php` → `register()`

```php
$this->app->singleton(\App\Services\Odoo\OdooClient::class, fn () => new \App\Services\Odoo\OdooClient(config('odoo')));
```

---

## 5. Schema local

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('odoo_id')->unique();
    $table->unsignedBigInteger('odoo_tmpl_id')->index();
    $table->string('code')->nullable()->index();
    $table->string('name');
    $table->text('oem_codes')->nullable();
    $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
    $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
    $table->decimal('cost', 14, 2)->default(0);
    $table->decimal('list_price', 14, 2)->default(0);
    $table->decimal('sale_price', 14, 2)->default(0);
    $table->decimal('stock', 12, 2)->default(0);
    $table->boolean('active')->default(true);
    $table->boolean('published')->default(true);
    $table->timestamp('odoo_write_date')->nullable()->index();
    $table->timestamps();
    $table->fullText(['name', 'code', 'oem_codes']);
});

Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('odoo_id')->unique();
    $table->string('name');
    $table->string('complete_name')->nullable();
    $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
    $table->timestamps();
});

Schema::create('brands', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->string('slug')->unique();
    $table->timestamps();
});
```

Tablas propias (no vienen de Odoo):

```php
Schema::create('vehicles', function (Blueprint $table) {
    $table->id();
    $table->string('brand');
    $table->string('model');
    $table->string('version')->nullable();
    $table->unsignedSmallInteger('year_from')->nullable();
    $table->unsignedSmallInteger('year_to')->nullable();
    $table->string('engine')->nullable();
    $table->timestamps();
    $table->index(['brand', 'model']);
});

Schema::create('product_vehicle', function (Blueprint $table) {
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
    $table->primary(['product_id', 'vehicle_id']);
});

Schema::create('equivalences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->string('code')->index();
    $table->string('source')->nullable();
    $table->timestamps();
});

Schema::create('product_attributes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('value');
    $table->timestamps();
});
```

---

## 6. Sincronización del catálogo

`app/Services/Odoo/OdooCatalog.php`

```php
<?php

namespace App\Services\Odoo;

class OdooCatalog
{
    public function __construct(protected OdooClient $odoo) {}

    public function categories(): array
    {
        return $this->odoo->searchRead('product.category', [], ['name', 'complete_name', 'parent_id']);
    }

    public function productsPage(?string $since, int $offset, int $limit = 500): array
    {
        $domain = [['sale_ok', '=', true]];

        if ($since) {
            $domain[] = ['write_date', '>', $since];
        }

        return $this->odoo->searchRead('product.product', $domain, [
            'id',
            'product_tmpl_id',
            'default_code',
            'name',
            'oem_code',
            'categ_id',
            'qty_available',
            'lst_price_with_margin',
            'active',
            'website_published',
            'write_date',
        ], [
            'offset' => $offset,
            'limit' => $limit,
            'order' => 'write_date asc, id asc',
            'context' => ['active_test' => false],
        ]);
    }

    public function costs(array $tmplIds): array
    {
        $rows = $this->odoo->searchRead('product.supplierinfo', [
            ['product_tmpl_id', 'in', $tmplIds],
        ], ['product_tmpl_id', 'name', 'price', 'min_qty'], ['order' => 'sequence asc, min_qty asc']);

        $out = [];

        foreach ($rows as $row) {
            $tmplId = $row['product_tmpl_id'][0];
            $supplier = $row['name'][1] ?? '';

            if (str_contains(strtoupper($supplier), 'REJOVOT')) {
                continue;
            }

            $out[$tmplId] ??= (float) $row['price'];
        }

        return $out;
    }

    public function prices(array $productIds, int $pricelistId, ?int $partnerId = null, float $qty = 1): array
    {
        $context = ['pricelist' => $pricelistId, 'quantity' => $qty];

        if ($partnerId) {
            $context['partner'] = $partnerId;
        }

        $rows = $this->odoo->read('product.product', $productIds, ['price'], $context);

        return collect($rows)->pluck('price', 'id')->all();
    }

    public function stock(array $productIds): array
    {
        $rows = $this->odoo->read('product.product', $productIds, ['qty_available', 'virtual_available'], [
            'location' => config('odoo.stock_location_id'),
        ]);

        return collect($rows)->keyBy('id')->all();
    }

    public function offers(int $pricelistId): array
    {
        $now = now()->format('Y-m-d H:i:s');

        return $this->odoo->searchRead('product.pricelist.item', [
            ['pricelist_id', '=', $pricelistId],
            '|', ['date_start', '=', false], ['date_start', '<=', $now],
            '|', ['date_end', '=', false], ['date_end', '>=', $now],
            ['price_discount', '>', 0],
        ], ['product_tmpl_id', 'product_id', 'price_discount', 'date_start', 'date_end']);
    }
}
```

`app/Console/Commands/SyncOdooCatalog.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Services\Odoo\OdooCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncOdooCatalog extends Command
{
    protected $signature = 'odoo:sync-catalog {--full}';

    protected $description = 'Sincroniza categorías y productos desde Odoo';

    public function handle(OdooCatalog $catalog): int
    {
        $this->syncCategories($catalog);

        $since = $this->option('full')
            ? null
            : Product::max('odoo_write_date');

        $offset = 0;
        $total = 0;

        do {
            $rows = $catalog->productsPage($since, $offset, 500);

            if (! $rows) {
                break;
            }

            $tmplIds = collect($rows)->pluck('product_tmpl_id.0')->unique()->values()->all();
            $costs = $catalog->costs($tmplIds);
            $prices = $catalog->prices(collect($rows)->pluck('id')->all(), config('odoo.pricelist_id'));

            DB::transaction(function () use ($rows, $costs, $prices) {
                foreach ($rows as $row) {
                    $tmplId = $row['product_tmpl_id'][0];

                    Product::updateOrCreate(['odoo_id' => $row['id']], [
                        'odoo_tmpl_id' => $tmplId,
                        'code' => $row['default_code'] ?: null,
                        'name' => $row['name'],
                        'oem_codes' => $row['oem_code'] ?: null,
                        'category_id' => $row['categ_id'] ? Category::where('odoo_id', $row['categ_id'][0])->value('id') : null,
                        'cost' => $costs[$tmplId] ?? 0,
                        'list_price' => $prices[$row['id']] ?? 0,
                        'sale_price' => $row['lst_price_with_margin'] ?? 0,
                        'stock' => $row['qty_available'],
                        'active' => $row['active'],
                        'published' => $row['website_published'],
                        'odoo_write_date' => $row['write_date'],
                    ]);
                }
            });

            $offset += count($rows);
            $total += count($rows);
            $this->info("Sincronizados {$total}");
        } while (count($rows) === 500);

        return self::SUCCESS;
    }

    protected function syncCategories(OdooCatalog $catalog): void
    {
        $rows = $catalog->categories();

        foreach ($rows as $row) {
            Category::updateOrCreate(['odoo_id' => $row['id']], [
                'name' => $row['name'],
                'complete_name' => $row['complete_name'] ?? null,
            ]);
        }

        foreach ($rows as $row) {
            if ($row['parent_id']) {
                Category::where('odoo_id', $row['id'])->update([
                    'parent_id' => Category::where('odoo_id', $row['parent_id'][0])->value('id'),
                ]);
            }
        }
    }
}
```

`routes/console.php` o el scheduler:

```php
Schedule::command('odoo:sync-catalog')->everyThirtyMinutes()->withoutOverlapping();
```

Primera corrida: `php artisan odoo:sync-catalog --full` (43k productos, va a tardar; en hosting
compartido conviene partirlo en jobs de cola).

---

## 7. Precio y stock en vivo

Para el listado ya sincronizado alcanza con lo local. Cuando hay cliente seleccionado, el precio
depende de su tarifa (`res.partner.property_product_pricelist`) y hay que pedirlo a Odoo:

```php
class LivePricing
{
    public function __construct(protected OdooCatalog $catalog) {}

    public function forCustomer(array $productIds, ?int $partnerId, ?int $pricelistId = null): array
    {
        $pricelistId ??= config('odoo.pricelist_id');
        $key = 'odoo:price:'.$pricelistId.':'.($partnerId ?? 0).':'.md5(implode(',', $productIds));

        return Cache::remember($key, 300, fn () => $this->catalog->prices($productIds, $pricelistId, $partnerId));
    }
}
```

Semáforo de stock: rojo `qty <= 0`, amarillo `qty <= umbral`, verde el resto. Usar
`virtual_available` si querés contemplar lo comprometido en pedidos.

---

## 8. Clientes, pedidos y cuenta corriente

```php
public function customers(string $search, int $limit = 20): array
{
    return $this->odoo->searchRead('res.partner', [
        ['customer_rank', '>', 0],
        '|', ['name', 'ilike', $search], ['vat', 'ilike', $search],
    ], ['name', 'vat', 'email', 'phone', 'property_product_pricelist', 'property_payment_term_id', 'credit', 'credit_limit'], ['limit' => $limit]);
}

public function createOrder(int $partnerId, array $lines, ?int $pricelistId = null): int
{
    return $this->odoo->create('sale.order', [
        'partner_id' => $partnerId,
        'pricelist_id' => $pricelistId ?? config('odoo.pricelist_id'),
        'warehouse_id' => config('odoo.warehouse_id'),
        'order_line' => array_map(fn ($line) => [0, 0, [
            'product_id' => $line['product_id'],
            'product_uom_qty' => $line['qty'],
        ]], $lines),
    ]);
}

public function orders(int $partnerId, int $limit = 50): array
{
    return $this->odoo->searchRead('sale.order', [
        ['partner_id', '=', $partnerId],
    ], ['name', 'date_order', 'state', 'amount_untaxed', 'amount_total', 'invoice_status'], [
        'limit' => $limit,
        'order' => 'date_order desc',
    ]);
}

public function statement(int $partnerId): array
{
    return $this->odoo->searchRead('account.move', [
        ['partner_id', '=', $partnerId],
        ['move_type', 'in', ['out_invoice', 'out_refund']],
        ['state', '=', 'posted'],
    ], ['name', 'invoice_date', 'invoice_date_due', 'amount_total', 'amount_residual', 'payment_state'], [
        'order' => 'invoice_date desc',
    ]);
}
```

**Importante:** al crear el `sale.order` no mandes precios propios. Odoo aplica la tarifa del
partner y calcula todo. Si necesitás forzar un precio, mandá `price_unit` explícito en la línea,
pero por defecto conviene dejar que lo resuelva Odoo.

El carrito puede vivir local en la web y recién al confirmar crear el `sale.order` en estado
`draft`, para no ensuciar Odoo con carritos abandonados.

---

## 9. Orden sugerido de implementación

1. `OdooClient` + comando `odoo:ping` que haga `common.version` y `res.users.read` para validar la API key.
2. Migraciones + modelos + `odoo:sync-catalog --full`.
3. Listado con filtros locales (código, nombre, OEM, rubro) + paginación.
4. Selector de cliente contra `res.partner` y precio en vivo por tarifa.
5. Carrito local → `sale.order`.
6. Mis Pedidos + Estado de Cuenta.
7. Recién al final: tablas propias de vehículos, aplicaciones, equivalencias y atributos, con su
   importador de Excel.

## 10. Cosas a confirmar con el cliente

- De dónde salen las aplicaciones vehiculares y las equivalencias (¿Excel de proveedores?).
- Qué tarifa aplica a cada cliente y si el markup del 5% es fijo o configurable por usuario.
- Qué depósito cuenta para el stock público (hay 6+; `REJOVOT / WH/Stock` es el principal).
- Si el catálogo público debe mostrar sólo `website_published = true`.
