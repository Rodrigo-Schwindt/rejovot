<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Distingue las listas que sube el admin de la que se genera desde Odoo. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            // manual | odoo
            $table->string('origen', 20)->default('manual')->after('formato');
            $table->timestamp('generada_at')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            $table->dropColumn(['origen', 'generada_at']);
        });
    }
};
