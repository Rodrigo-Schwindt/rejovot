<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Espejo local del catálogo de Odoo. Con 43k productos no se puede consultar
 * el ERP en cada request: se sincroniza acá y sólo precio y stock del cliente
 * logueado se piden en vivo.
 */
return new class extends Migration
{
    public function up(): void
    {
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

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('odoo_id')->unique();
            $table->unsignedBigInteger('odoo_tmpl_id')->index();
            $table->string('code')->nullable()->index();
            $table->string('name');
            $table->text('oem_codes')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            // Costo del proveedor real; list_price es el precio de la tarifa pública.
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
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
