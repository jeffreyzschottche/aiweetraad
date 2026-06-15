<?php

namespace App\Services\AI;

use App\Models\AiModel;
use App\Models\Question;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiDriver implements AnswerDriver
{
    public function generate(Question $question, AiModel $model): string
    {
        $config = config('ai.openai');

        if (empty($config['key'])) {
            throw new RuntimeException('OpenAI API key is missing.');
        }

        $system = $model->system_prompt
            ?: 'Je bent een behulpzame Nederlandse adviseur. Geef een kort, praktisch en stapsgewijs antwoord.';

        $response = Http::withToken($config['key'])
            ->timeout(60)
            ->post(rtrim($config['base_url'], '/') . '/v1/chat/completions', [
                'model' => $model->model_identifier ?: $config['default_model'],
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $this->prompt($question)],
                ],
                'max_completion_tokens' => (int) $config['max_tokens'],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI API error: ' . $response->status());
        }

        $text = trim((string) $response->json('choices.0.message.content', ''));
        if ($text === '') {
            throw new RuntimeException('OpenAI API returned an empty response.');
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
