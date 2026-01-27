<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawl_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique(); // URL harus unik agar tidak double
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->text('last_error')->nullable(); // Untuk mencatat kalau ada yang gagal
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawl_tasks');
    }
};