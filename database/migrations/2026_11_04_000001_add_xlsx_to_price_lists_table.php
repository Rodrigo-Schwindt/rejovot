<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** La lista generada también se baja en Excel, además del CSV. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            $table->string('archivo_xlsx')->nullable()->after('archivo_pdf');
            $table->unsignedBigInteger('tamano_xlsx')->default(0)->after('tamano_pdf');
        });
    }

    public function down(): void
    {
        Schema::table('price_lists', function (Blueprint $table) {
            $table->dropColumn(['archivo_xlsx', 'tamano_xlsx']);
        });
    }
};
