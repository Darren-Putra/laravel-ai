<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('laravel_knowledge', function (Blueprint $table) {
            $table->id();
            $table->string('url')->nullable(); // Sumber (misal: docs/eloquent)
            $table->string('title')->nullable(); // Judul halaman
            $table->longText('content'); // Isi teks dokumentasi
            $table->json('vector')->nullable(); // Koordinat angka dari AI
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laravel_knowledge');
    }
};
