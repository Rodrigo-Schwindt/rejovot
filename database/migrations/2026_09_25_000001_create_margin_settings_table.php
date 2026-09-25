<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Márgenes y descuento de cada cliente.
 *
 * Antes vivían en la sesión del navegador: dos vendedores en la misma máquina
 * compartían los valores y el mismo cliente veía otros desde otra computadora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('margin_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->decimal('general', 8, 2)->default(5);
            $table->decimal('descuento', 8, 2)->default(0);
            // Margen propio por marca y por rubro, indexado por clave.
            $table->json('marcas')->nullable();
            $table->json('familias')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('margin_settings');
    }
};
