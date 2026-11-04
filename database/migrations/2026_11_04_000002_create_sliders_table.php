<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Banners administrables que se suman al carrusel de Productos. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('etiqueta', 40)->nullable();
            $table->string('etiqueta_color', 7)->default('#FFFFFF');
            $table->string('etiqueta_fondo', 7)->default('#E11A22');
            $table->string('titulo', 120);
            $table->string('titulo_color', 7)->default('#FFFFFF');
            $table->string('descripcion', 400)->nullable();
            $table->string('descripcion_color', 7)->default('#FFFFFF');
            $table->string('fondo', 7)->default('#002B56');
            $table->json('imagenes')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sliders');
    }
};
