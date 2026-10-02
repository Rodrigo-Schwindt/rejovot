<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Casilla a la que llegan los reclamos; se edita en Admin → Reclamos. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact', function (Blueprint $table) {
            $table->string('mail_reclamos')->nullable()->after('mail_comprobantes');
        });
    }

    public function down(): void
    {
        Schema::table('contact', function (Blueprint $table) {
            $table->dropColumn('mail_reclamos');
        });
    }
};
