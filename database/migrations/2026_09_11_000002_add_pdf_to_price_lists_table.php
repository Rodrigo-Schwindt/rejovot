<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** La lista generada tiene dos archivos: el CSV que se baja y el PDF que se ve. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            $table->string('archivo_pdf')->nullable()->after('archivo');
            $table->unsignedBigInteger('tamano_pdf')->default(0)->after('tamano');
        });
    }

    public function down(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            $table->dropColumn(['archivo_pdf', 'tamano_pdf']);
        });
    }
};
