<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cada cliente tiene dos carritos: el principal y uno alternativo. Lo que ya
 * estaba cargado queda en el principal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('carrito', 20)->default('principal')->after('customer_id');
        });

        // El índice nuevo primero: el viejo también sostiene la clave foránea de customer_id.
        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['customer_id', 'carrito', 'codigo']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['customer_id', 'codigo']);
        });
    }

    public function down(): void
    {
        // Al volver a un solo carrito, lo del alternativo se pierde.
        DB::table('cart_items')->where('carrito', '!=', 'principal')->delete();

        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['customer_id', 'codigo']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['customer_id', 'carrito', 'codigo']);
            $table->dropColumn('carrito');
        });
    }
};
