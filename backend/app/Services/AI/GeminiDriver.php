<?php

namespace App\Services\AI;

use App\Models\AiModel;
use App\Models\Question;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiDriver implements AnswerDriver
{
    public function generate(Question $question, AiModel $model): string
    {
        $config = config('ai.gemini');

        if (empty($config['key'])) {
            throw new RuntimeException('Gemini API key is missing.');
        }

        $modelName = $model->model_identifier ?: $config['default_model'];
        $system = $model->system_prompt
            ?: 'Je bent een behulpzame Nederlandse adviseur. Geef een kort, praktisch en stapsgewijs antwoord.';

        $response = Http::timeout(60)
            ->post(rtrim($config['base_url'], '/') . "/v1beta/models/{$modelName}:generateContent?key={$config['key']}", [
                'systemInstruction' => [
                    'parts' => [['text' => $system]],
                ],
                'contents' => [[
                    'role' => 'user',
                    'parts' => [['text' => $this->prompt($question)]],
                ]],
                'generationConfig' => [
                    'maxOutputTokens' => (int) $config['max_tokens'],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini API error: ' . $response->status());
        }

        $text = trim(collect($response->json('candidates.0.content.parts', []))
            ->pluck('text')
            ->implode("\n"));

        if ($text === '') {
            throw new RuntimeException('Gemini API returned an empty response.');
        }

        return $text;
    }

    private function prompt(Question $question): string
    {
        $prompt = $question->title;
        if ($question->body) {
            $prompt .= "\n\nExtra context: " . $question->body;
        }

        return $prompt;
    }
}
