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
        Schema::table('laravel_knowledge', function (Blueprint $table) {
            // Menambahkan index FULLTEXT pada kolom content
            DB::statement('ALTER TABLE laravel_knowledge ADD FULLTEXT fulltext_index (content)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge', function (Blueprint $table) {
            //
        });
    }
};
