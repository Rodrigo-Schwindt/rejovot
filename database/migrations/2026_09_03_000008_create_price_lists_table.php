<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion');
            // pdf | excel | csv
            $table->string('formato', 20);
            $table->string('archivo');
            $table->string('archivo_original')->nullable();
            $table->unsignedBigInteger('tamano')->default(0);
            $table->string('vigencia')->nullable();
            $table->text('notas')->nullable();
            $table->boolean('publicada')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_lists');
    }
};
