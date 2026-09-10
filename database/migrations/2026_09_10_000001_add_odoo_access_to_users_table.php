<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clientes y vendedores entran con su usuario de Odoo: la contraseña se valida
 * contra el ERP y nunca se guarda acá. El usuario local sólo mantiene la sesión
 * y a quién representa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('odoo_uid')->nullable()->unique()->after('role');
            $table->foreignId('customer_id')->nullable()->after('odoo_uid')->constrained('customers')->nullOnDelete();
            $table->foreignId('salesperson_id')->nullable()->after('customer_id')->constrained('salespeople')->nullOnDelete();
            $table->timestamp('last_login_at')->nullable()->after('salesperson_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignKey('customer_id');
            $table->dropConstrainedForeignKey('salesperson_id');
            $table->dropColumn(['odoo_uid', 'last_login_at']);
        });
    }
};
