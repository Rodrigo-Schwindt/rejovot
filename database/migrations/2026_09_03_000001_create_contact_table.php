<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact', function (Blueprint $table) {
            $table->id();
            // Espejo de los datos dinamicos (contact_info_items) para consumo rapido.
            $table->string('direction_adm')->nullable();
            $table->string('phone_amd', 50)->nullable();
            $table->string('mail_adm')->nullable();
            $table->string('wssp', 50)->nullable();
            $table->string('maps_adm')->nullable();
            $table->text('frame_adm')->nullable();
            $table->string('facebook')->nullable();
            $table->string('insta')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('youtube')->nullable();
            // Logos: 1 = header, 2 = admin / login, 3 = footer.
            $table->string('icono_1')->nullable();
            $table->string('icono_2')->nullable();
            $table->string('icono_3')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact');
    }
};
