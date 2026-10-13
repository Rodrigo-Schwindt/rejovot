<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categorías de ecommerce de cada producto, como en Odoo: un producto puede
 * estar en varias (NOVEDADES + su marca). Los filtros del sitio usan esto;
 * products.brand_id sigue siendo «la» marca del producto (márgenes, SEO).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            // NOVEDADES es categoría pero no marca: no se asigna como brand_id.
            $table->boolean('es_marca')->default(true)->after('sort');
        });

        Schema::create('brand_product', function (Blueprint $table) {
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['brand_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_product');

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('es_marca');
        });
    }
};
