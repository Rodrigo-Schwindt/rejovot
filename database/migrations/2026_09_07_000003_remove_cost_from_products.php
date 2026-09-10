<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El costo de Rejovot y el descuento que consigue del proveedor no pueden salir
 * nunca hacia el front: revelan cuánto gana con cada cliente. Se dejan de
 * sincronizar y se borran de la base.
 *
 * sale_price también se va: lst_price_with_margin y el precio de tarifa son el
 * mismo número, así que list_price alcanza.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['cost', 'sale_price']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('cost', 14, 2)->default(0);
            $table->decimal('sale_price', 14, 2)->default(0);
        });
    }
};
