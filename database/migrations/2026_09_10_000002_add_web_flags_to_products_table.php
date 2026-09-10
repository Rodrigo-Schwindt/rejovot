<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Banderas propias de la web y ofertas vigentes.
 *
 * `oculto` y `destacado` los maneja el admin y el sync de Odoo no los pisa.
 * El descuento sale de las reglas de tarifa (product.pricelist.item).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('oculto')->default(false)->after('published')->index();
            $table->boolean('destacado')->default(false)->after('oculto')->index();

            $table->decimal('discount_percent', 6, 2)->nullable()->after('destacado');
            $table->timestamp('discount_from')->nullable()->after('discount_percent');
            $table->timestamp('discount_to')->nullable()->after('discount_from');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['oculto', 'destacado', 'discount_percent', 'discount_from', 'discount_to']);
        });
    }
};
