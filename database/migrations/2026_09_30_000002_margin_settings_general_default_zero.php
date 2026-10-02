<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** El margen general arranca en 0: lo define cada cliente desde Márgenes. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('margin_settings', function (Blueprint $table) {
            $table->decimal('general', 8, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('margin_settings', function (Blueprint $table) {
            $table->decimal('general', 8, 2)->default(5)->change();
        });
    }
};
