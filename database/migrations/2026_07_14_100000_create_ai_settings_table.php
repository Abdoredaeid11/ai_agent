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
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // e.g. 'gemini', 'groq', 'openrouter', 'ollama'
            $table->string('model'); // e.g. 'gemini-2.0-flash', 'llama-3.3-70b-versatile'
            $table->boolean('is_active')->default(false);
            $table->integer('priority')->default(0); // lower number means higher priority
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
