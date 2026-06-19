<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('source_name')->nullable()->after('views');
            $table->string('source_url', 1000)->nullable()->after('source_name');
            $table->string('source_hash')->nullable()->unique()->after('source_url');
            $table->timestamp('source_imported_at')->nullable()->after('source_hash');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropUnique(['source_hash']);
            $table->dropColumn([
                'source_name',
                'source_url',
                'source_hash',
                'source_imported_at',
            ]);
        });
    }
};
