<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEO: título editable por sección y metadata propia por producto.
 *
 * La de los productos se genera sola con los datos de Odoo; estos campos son
 * sólo para pisarla a mano en alguno. Vacíos, se usa la automática.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metadata', function (Blueprint $table) {
            $table->string('title', 120)->nullable()->after('section');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('seo_title', 120)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->string('seo_keywords', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('metadata', function (Blueprint $table) {
            $table->dropColumn('title');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'seo_description', 'seo_keywords']);
        });
    }
};
