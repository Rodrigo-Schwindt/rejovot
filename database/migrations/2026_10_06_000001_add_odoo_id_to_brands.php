<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las marcas pasan a salir de las categorías de ecommerce de Odoo
 * (product.public.category) en vez de deducirse del nombre del producto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->unsignedBigInteger('odoo_id')->nullable()->unique()->after('id');
            // Orden de Odoo: si un producto tiene dos categorías, gana la primera.
            $table->unsignedInteger('sort')->default(0)->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['odoo_id', 'sort']);
        });
    }
};
