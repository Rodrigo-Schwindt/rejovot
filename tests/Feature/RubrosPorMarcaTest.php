<?php

namespace Tests\Feature;

use App\Livewire\Vistas\Productos\ProductosPage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalogo\CatalogoLocal;
use App\Services\Catalogo\RefrescoEnVivo;
use App\Services\Margenes\Margenes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RubrosPorMarcaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Esquema aislado en SQLite: las migraciones de producción usan FULLTEXT de MySQL.
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('odoo_id');
            $table->string('name');
            $table->string('slug');
            $table->integer('sort')->default(0);
            $table->boolean('es_marca')->default(true);
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('odoo_id');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('odoo_id');
            $table->unsignedBigInteger('odoo_tmpl_id');
            $table->string('code');
            $table->string('name');
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->string('type')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('published')->default(true);
            $table->boolean('oculto')->default(false);
            $table->boolean('destacado')->default(false);
            $table->timestamps();
        });
        Schema::create('brand_product', function (Blueprint $table) {
            $table->unsignedBigInteger('brand_id');
            $table->unsignedBigInteger('product_id');
        });

        $vmg = Brand::create(['odoo_id' => 1, 'name' => 'VMG', 'slug' => 'vmg']);
        $gacri = Brand::create(['odoo_id' => 2, 'name' => 'GACRI', 'slug' => 'gacri']);
        $novedades = Brand::create(['odoo_id' => 3, 'name' => 'NOVEDADES', 'slug' => 'novedades', 'es_marca' => false]);

        foreach (['Refrigeración', 'Suspensión', 'Oculto', 'Inactivo', 'Sin publicar', 'Sin productos'] as $i => $nombre) {
            Category::create(['odoo_id' => $i + 1, 'name' => $nombre]);
        }

        $this->producto(1, $vmg, 1)->categoriasWeb()->attach($novedades);
        $this->producto(2, $gacri, 2);
        $this->producto(3, $vmg, 3, ['oculto' => true]);
        $this->producto(4, $vmg, 4, ['active' => false]);
        $this->producto(5, $vmg, 5, ['published' => false]);

        $this->mock(RefrescoEnVivo::class)->shouldReceive('codigos')->andReturn(false);
    }

    public function test_los_rubros_respetan_la_categoria_web_y_la_publicacion(): void
    {
        $catalogo = app(CatalogoLocal::class);

        $this->assertSame(['Refrigeración', 'Suspensión'], $catalogo->filtros()['rubros']);
        $this->assertSame(['Refrigeración'], $catalogo->filtros('VMG')['rubros']);
        $this->assertSame(['Suspensión'], $catalogo->filtros('GACRI')['rubros']);
        $this->assertSame(['Refrigeración'], $catalogo->filtros('NOVEDADES')['rubros']);
        $this->assertSame([], $catalogo->filtros('Sin productos')['rubros']);
    }

    public function test_cambiar_marca_limpia_el_rubro_incompatible_y_actualiza_las_opciones(): void
    {
        $page = new ProductosPage;
        $page->rubro = 'Refrigeración';
        $page->marca = 'VMG';
        $this->datos($page);
        $this->assertSame('Refrigeración', $page->rubro);

        $page->marca = 'GACRI';
        $page->updated('marca');
        $datos = $this->datos($page);

        $this->assertSame('', $page->rubro);
        $this->assertSame(1, $page->getPage());
        $this->assertSame(['Suspensión'], $datos['opciones']['rubros']);
        $this->assertSame(['P2'], array_column($datos['productos'], 'codigo'));

        $page->marca = '';
        $page->updated('marca');
        $this->assertSame(['Refrigeración', 'Suspensión'], $this->datos($page)['opciones']['rubros']);
    }

    public function test_una_combinacion_incompatible_inicial_no_deja_el_listado_vacio(): void
    {
        $page = new ProductosPage;
        $page->marca = 'VMG';
        $page->rubro = 'Suspensión';
        $page->paginators['page'] = 3;

        $datos = $this->datos($page);

        $this->assertSame('', $page->rubro);
        $this->assertSame(1, $page->getPage());
        $this->assertSame(['P1'], array_column($datos['productos'], 'codigo'));
    }

    private function datos(ProductosPage $page): array
    {
        return $page->render(app(CatalogoLocal::class), app(Margenes::class), app(RefrescoEnVivo::class))->getData();
    }

    private function producto(int $id, Brand $marca, int $rubro, array $atributos = []): Product
    {
        $producto = Product::create($atributos + [
            'odoo_id' => $id,
            'odoo_tmpl_id' => $id,
            'code' => 'P'.$id,
            'name' => 'Producto '.$id,
            'brand_id' => $marca->id,
            'category_id' => $rubro,
        ]);
        $producto->categoriasWeb()->attach($marca);

        return $producto;
    }
}
