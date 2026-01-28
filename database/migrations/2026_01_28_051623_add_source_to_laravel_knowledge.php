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
            $table->string('source')->nullable(); // laravel-11, laravel-12, livewire
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laravel_knowledge', function (Blueprint $table) {
            //
        });
    }
};
