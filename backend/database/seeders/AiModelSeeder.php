<?php

namespace Database\Seeders;

use App\Models\AiModel;
use Illuminate\Database\Seeder;

class AiModelSeeder extends Seeder
{
    public function run(): void
    {
        if (! AiModel::where('slug', 'openai')->exists()) {
            AiModel::where('slug', 'gpt')->update(['slug' => 'openai']);
        }

        if (! AiModel::where('slug', 'grok')->exists()) {
            AiModel::where('slug', 'mistral')->update(['slug' => 'grok']);
        }

        AiModel::whereIn('slug', ['gpt', 'mistral'])->update(['enabled' => false]);

        $models = [
            [
                'name' => 'Claude',
                'slug' => 'claude',
                'provider' => 'claude',
                'model_identifier' => 'claude-3-5-sonnet-latest',
                'tagline' => 'Doordacht en praktisch',
                'accent_color' => '#d97757',
                'logo_url' => '/images/claudelogo.png',
                'system_prompt' => 'Je bent Claude. Geef een doordacht, vriendelijk en stapsgewijs antwoord in het Nederlands. Houd het veilig en praktisch.',
                'enabled' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'OpenAI',
                'slug' => 'openai',
                'provider' => 'openai',
                'model_identifier' => 'gpt-4.1',
                'tagline' => 'Helder en gestructureerd',
                'accent_color' => '#10a37f',
                'logo_url' => '/images/chatgpt.png',
                'system_prompt' => 'Je bent een behulpzame assistent. Geef een helder, gestructureerd antwoord in het Nederlands met concrete stappen.',
                'enabled' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Gemini',
                'slug' => 'gemini',
                'provider' => 'gemini',
                'model_identifier' => 'gemini-1.5-pro',
                'tagline' => 'Vlot en to-the-point',
                'accent_color' => '#4285f4',
                'logo_url' => '/images/geminilogo.png',
                'system_prompt' => 'Je bent een vlotte assistent. Antwoord in het Nederlands, kort en to-the-point met de meest effectieve aanpak.',
                'enabled' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Grok',
                'slug' => 'grok',
                'provider' => 'grok',
                'model_identifier' => 'grok-4.3',
                'tagline' => 'Direct en eigenwijs',
                'accent_color' => '#1f2937',
                'logo_url' => '/images/groklogo.png',
                'system_prompt' => 'Je bent Grok. Geef een direct, nuchter en praktisch antwoord in het Nederlands.',
                'enabled' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'DeepSeek',
                'slug' => 'deepseek',
                'provider' => 'deepseek',
                'model_identifier' => 'deepseek-chat',
                'tagline' => 'Analytisch en precies',
                'accent_color' => '#111827',
                'logo_url' => '/images/deepseek.png',
                'system_prompt' => 'Je bent DeepSeek. Geef een analytisch, precies en praktisch antwoord in het Nederlands.',
                'enabled' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($models as $model) {
            AiModel::updateOrCreate(['slug' => $model['slug']], $model);
        }
    }
}
