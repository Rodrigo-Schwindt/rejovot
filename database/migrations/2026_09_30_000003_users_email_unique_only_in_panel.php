<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El mail deja de ser único en toda la tabla: un admin del panel puede tener
 * el mismo correo que su usuario de Odoo (vendedor/cliente) y son dos filas.
 * Entre usuarios del panel lo controla la validación; los del sitio son
 * únicos por odoo_uid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_email_index');
            $table->unique('email');
        });
    }
};
