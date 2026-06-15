<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->string('status')->default('completed')->after('body');
            $table->string('actual_provider')->nullable()->after('status');
            $table->string('actual_model')->nullable()->after('actual_provider');
            $table->decimal('estimated_cost_usd', 12, 6)->default(0)->after('actual_model');
            $table->text('error_message')->nullable()->after('estimated_cost_usd');
        });
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'actual_provider',
                'actual_model',
                'estimated_cost_usd',
                'error_message',
            ]);
        });
    }
};
