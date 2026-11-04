<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** La factura deja de ser obligatoria al cargar una garantía. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->string('factura_numero', 60)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->string('factura_numero', 60)->nullable(false)->default('')->change();
        });
    }
};
