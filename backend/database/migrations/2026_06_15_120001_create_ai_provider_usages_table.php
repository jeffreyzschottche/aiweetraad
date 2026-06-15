<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_provider_usages', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->date('usage_date');
            $table->decimal('estimated_spend_usd', 12, 6)->default(0);
            $table->unsignedInteger('requests')->default(0);
            $table->unsignedInteger('failures')->default(0);
            $table->timestamps();

            $table->unique(['provider', 'usage_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_provider_usages');
    }
};
