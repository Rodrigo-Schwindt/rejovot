<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Casilla a la que llegan los comprobantes de pago; se edita en Cuenta corriente. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact', function (Blueprint $table) {
            $table->string('mail_comprobantes')->nullable()->after('mail_adm');
        });
    }

    public function down(): void
    {
        Schema::table('contact', function (Blueprint $table) {
            $table->dropColumn('mail_comprobantes');
        });
    }
};
