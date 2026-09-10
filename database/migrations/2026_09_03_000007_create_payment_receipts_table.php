<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->decimal('importe', 14, 2);
            $table->string('banco');
            $table->string('sucursal');
            $table->string('facturas_canceladas')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('archivo');
            $table->string('archivo_original')->nullable();
            // pendiente | procesado
            $table->string('estado', 20)->default('pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_receipts');
    }
};
