<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCanonicalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_oma_trucjes_category_is_never_returned_publicly(): void
    {
        Category::query()->create([
            'name' => "Oma's Oudste Trucjes",
            'slug' => 'omas-oudste-trucjes',
            'description' => 'Oude tekst',
            'icon' => '🧺',
            'color' => '#b45309',
            'sort_order' => 1,
        ]);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'AI-trucjes')
            ->assertJsonPath('data.0.slug', 'ai-trucjes')
            ->assertJsonPath('data.0.icon', '✨');

        $this->getJson('/api/v1/categories/ai-trucjes')
            ->assertOk()
            ->assertJsonPath('data.name', 'AI-trucjes')
            ->assertJsonPath('data.slug', 'ai-trucjes');
    }
}
