<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Interruptor del admin para mandar o no los mails de cada pedido. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact', function (Blueprint $table) {
            // Activado: es como funcionaba hasta ahora.
            $table->boolean('avisar_pedidos')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('contact', function (Blueprint $table) {
            $table->dropColumn('avisar_pedidos');
        });
    }
};
