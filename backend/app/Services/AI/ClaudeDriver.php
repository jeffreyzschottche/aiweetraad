<?php

namespace App\Services\AI;

use App\Models\AiModel;
use App\Models\Question;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Real answers via the Anthropic Claude API. Only used when generation is
 * enabled and the model's provider is `claude`.
 */
class ClaudeDriver implements AnswerDriver
{
    public function generate(Question $question, AiModel $model): string
    {
        $config = config('ai.anthropic');

        if (empty($config['key'])) {
            throw new RuntimeException('Anthropic API key is missing.');
        }

        $system = $model->system_prompt
            ?: 'Je bent een behulpzame Nederlandse adviseur. Geef een kort, praktisch en stapsgewijs antwoord op de vraag van de gebruiker.';

        try {
            $response = Http::withHeaders([
                'x-api-key' => $config['key'],
                'anthropic-version' => $config['version'],
                'content-type' => 'application/json',
            ])->timeout(60)->post(rtrim($config['base_url'], '/') . '/v1/messages', [
                'model' => $model->model_identifier ?: $config['default_model'],
                'max_tokens' => $config['max_tokens'],
                'system' => $system,
                'messages' => [[
                    'role' => 'user',
                    'content' => $this->prompt($question),
                ]],
            ]);

            if (! $response->successful()) {
                throw new RuntimeException('Anthropic API error: ' . $response->status());
            }

            $text = collect($response->json('content', []))
                ->where('type', 'text')
                ->pluck('text')
                ->implode("\n");

            $text = trim($text);
            if ($text === '') {
                throw new RuntimeException('Anthropic API returned an empty response.');
            }

            return $text;
        } catch (\Throwable $e) {
            throw new RuntimeException('Anthropic request failed: ' . $e->getMessage(), previous: $e);
        }
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
