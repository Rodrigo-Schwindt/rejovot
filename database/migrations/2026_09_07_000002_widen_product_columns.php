<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * En Odoo hay nombres de hasta 492 caracteres y algún default_code cargado con
 * un texto largo. El sync trunca defensivamente, pero el nombre se guarda entero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropFullText(['name', 'code', 'oem_codes']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->text('name')->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->fullText(['name', 'code', 'oem_codes']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropFullText(['name', 'code', 'oem_codes']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('name')->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->fullText(['name', 'code', 'oem_codes']);
        });
    }
};
