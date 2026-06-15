<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // e.g. "Claude"
            $table->string('slug')->unique();
            $table->string('provider')->default('stub');  // stub | claude | openai | gemini
            $table->string('model_identifier')->nullable(); // e.g. claude-opus-4-8
            $table->string('tagline')->nullable();
            $table->string('accent_color')->default('#6366f1');
            $table->text('system_prompt')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
