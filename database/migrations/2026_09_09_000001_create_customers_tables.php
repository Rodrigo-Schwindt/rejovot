<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clientes y vendedores, espejo de res.partner y res.users de Odoo.
 *
 * El descuento del cliente define "Tu precio". El margen de reventa se guarda
 * como referencia: el que manda es el que el cliente configura en la web.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salespeople', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('odoo_id')->unique();
            $table->string('name');
            $table->string('login')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('odoo_id')->unique();
            $table->string('name')->index();
            $table->string('vat')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();

            $table->foreignId('salesperson_id')->nullable()->constrained('salespeople')->nullOnDelete();

            $table->unsignedBigInteger('pricelist_odoo_id')->nullable();
            // Descuento del cliente sobre la lista pública.
            $table->decimal('price_discount', 6, 2)->default(0);
            // Margen de reventa cargado en Odoo; hoy no se aplica.
            $table->decimal('price_margin', 6, 2)->default(0);

            $table->decimal('credit', 14, 2)->default(0);
            $table->decimal('credit_limit', 14, 2)->default(0);

            // Usuario de portal: son los que hoy ya entran al sistema.
            $table->string('portal_login')->nullable();
            $table->boolean('has_portal')->default(false);

            $table->boolean('active')->default(true);
            $table->timestamp('odoo_write_date')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
        Schema::dropIfExists('salespeople');
    }
};
