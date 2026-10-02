<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Carrito compartido por cliente.
 *
 * Antes vivía en la sesión del navegador: el vendedor y el cliente, o el mismo
 * cliente en dos dispositivos, tenían cada uno su carrito. Ahora es uno solo
 * por cliente y lo ven todos los que operan esa cuenta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('codigo', 64);
            $table->unsignedInteger('cantidad');
            $table->timestamps();

            $table->unique(['customer_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
