<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reclamos de los clientes sobre sus compras. Viven sólo en la web: si
 * terminan en nota de crédito, la nota se hace en Odoo como siempre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            // Quién lo cargó: el propio cliente o su vendedor.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha');
            $table->string('factura_numero', 60);
            $table->date('factura_fecha')->nullable();
            // Si la factura se eligió de las suyas en Odoo, su id (si fue texto libre, null).
            $table->unsignedBigInteger('factura_odoo_id')->nullable();
            // enviado | rechazado | cerrado | nota_credito
            $table->string('estado', 20)->default('enviado')->index();
            $table->text('respuesta')->nullable();
            $table->timestamp('respondido_at')->nullable();
            $table->timestamps();
        });

        Schema::create('claim_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('claims')->cascadeOnDelete();
            $table->string('codigo', 120)->index();
            $table->string('nombre')->nullable();
            $table->unsignedInteger('cantidad');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        Schema::create('claim_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('claims')->cascadeOnDelete();
            // En el disco privado: son fotos de un cliente, no se publican.
            $table->string('archivo');
            $table->string('archivo_original')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_photos');
        Schema::dropIfExists('claim_items');
        Schema::dropIfExists('claims');
    }
};
