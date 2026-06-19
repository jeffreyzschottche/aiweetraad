<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacy = DB::table('categories')->where('slug', 'omas-oudste-trucjes')->first();
        $current = DB::table('categories')->where('slug', 'ai-trucjes')->first();

        if ($legacy && $current && $legacy->id !== $current->id) {
            DB::table('questions')
                ->where('category_id', $legacy->id)
                ->update(['category_id' => $current->id]);

            DB::table('categories')->where('id', $legacy->id)->delete();
        }

        DB::table('categories')
            ->where('slug', 'omas-oudste-trucjes')
            ->update(['slug' => 'ai-trucjes']);

        DB::table('categories')
            ->where('slug', 'ai-trucjes')
            ->update([
                'name' => 'AI-trucjes',
                'description' => 'Slimme huis-, tuin- en keukenoplossingen in een AI-jasje.',
                'icon' => '✨',
                'color' => '#b45309',
            ]);
    }

    public function down(): void
    {
        DB::table('categories')
            ->where('slug', 'ai-trucjes')
            ->update([
                'name' => "Oma's Oudste Trucjes",
                'slug' => 'omas-oudste-trucjes',
                'description' => 'Slimme huis-, tuin- en keukenoplossingen van vroeger.',
                'icon' => '🧺',
                'color' => '#b45309',
            ]);
    }
};
