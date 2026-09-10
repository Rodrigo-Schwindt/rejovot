<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_info_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contact')->cascadeOnDelete();
            $table->string('type', 40);
            $table->text('value')->nullable();
            $table->boolean('is_fixed')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_info_items');
    }
};
