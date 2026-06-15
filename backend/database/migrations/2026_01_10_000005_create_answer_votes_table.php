<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answer_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('answer_id')->constrained()->cascadeOnDelete();
            $table->string('voter_key');         // user id ("u:1") or anon uuid
            $table->tinyInteger('value');        // 1 = like, -1 = dislike
            $table->timestamps();

            $table->unique(['answer_id', 'voter_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_votes');
    }
};
