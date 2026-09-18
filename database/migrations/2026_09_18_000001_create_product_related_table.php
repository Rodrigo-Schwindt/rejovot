<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Productos relacionados de Odoo: alternativos (product.template) y
 * accesorios (product.product). En tabla aparte para poder cruzarlos en la
 * búsqueda con una sola consulta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_related', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('related_id')->constrained('products')->cascadeOnDelete();
            // alternativo | accesorio
            $table->string('tipo', 20);
            $table->timestamps();

            $table->unique(['product_id', 'related_id', 'tipo']);
            $table->index('related_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_related');
    }
};
