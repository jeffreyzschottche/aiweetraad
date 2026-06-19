<?php

namespace App\Services\AI;

use App\Models\AiModel;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class OpenAiBatchAnswerService
{
    public function create(array $questionIds, bool $force = false): array
    {
        $config = config('ai.openai');
        if (empty($config['key'])) {
            throw new RuntimeException('OpenAI API key is missing.');
        }

        $models = AiModel::enabled()
            ->whereIn('slug', ['claude', 'openai', 'gemini', 'grok'])
            ->orderBy('sort_order')
            ->get();

        $questions = Question::query()
            ->whereIn('id', $questionIds)
            ->where('status', 'published')
            ->orderBy('id')
            ->get();

        $lines = [];

        foreach ($questions as $question) {
            foreach ($models as $model) {
                if (! $force && Answer::where('question_id', $question->id)->where('ai_model_id', $model->id)->exists()) {
                    continue;
                }

                $runtimeModel = $model->replicate();
                $runtimeModel->forceFill([
                    'provider' => 'openai',
                    'model_identifier' => config('ai.openai.default_model'),
                    'system_prompt' => $this->systemPrompt($model),
                ]);

                $lines[] = json_encode([
                    'custom_id' => $this->customId($question->id, $model->id),
                    'method' => 'POST',
                    'url' => '/v1/chat/completions',
                    'body' => [
                        'model' => $runtimeModel->model_identifier,
                        'messages' => [
                            ['role' => 'system', 'content' => $runtimeModel->system_prompt],
                            ['role' => 'user', 'content' => $this->prompt($question)],
                        ],
                        'max_completion_tokens' => (int) config('ai.openai.max_tokens', 900),
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        if ($lines === []) {
            throw new RuntimeException('Geen OpenAI batchregels om te versturen.');
        }

        $path = 'ai-batches/openai-' . now()->format('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.jsonl';
        Storage::disk('local')->put($path, implode("\n", $lines) . "\n");
        $fullPath = Storage::disk('local')->path($path);

        $file = Http::withToken($config['key'])
            ->attach('file', fopen($fullPath, 'r'), basename($path))
            ->post(rtrim($config['base_url'], '/') . '/v1/files', [
                'purpose' => 'batch',
            ]);

        if (! $file->successful()) {
            throw new RuntimeException('OpenAI file upload failed: HTTP ' . $file->status() . ' ' . $file->body());
        }

        $batch = Http::withToken($config['key'])
            ->post(rtrim($config['base_url'], '/') . '/v1/batches', [
                'input_file_id' => $file->json('id'),
                'endpoint' => '/v1/chat/completions',
                'completion_window' => '24h',
                'metadata' => [
                    'kind' => 'aiweetraad-answer-generation',
                    'local_path' => $path,
                ],
            ]);

        if (! $batch->successful()) {
            throw new RuntimeException('OpenAI batch create failed: HTTP ' . $batch->status() . ' ' . $batch->body());
        }

        return [
            'batch_id' => $batch->json('id'),
            'input_file_id' => $file->json('id'),
            'local_path' => $path,
            'requests' => count($lines),
        ];
    }

    public function status(string $batchId): array
    {
        $config = config('ai.openai');
        $response = Http::withToken($config['key'])
            ->get(rtrim($config['base_url'], '/') . '/v1/batches/' . $batchId);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI batch status failed: HTTP ' . $response->status() . ' ' . $response->body());
        }

        return $response->json();
    }

    public function collect(string $batchId, bool $force = true): array
    {
        $config = config('ai.openai');
        $batch = $this->status($batchId);
        if (($batch['status'] ?? null) !== 'completed') {
            return [
                'status' => $batch['status'] ?? 'unknown',
                'created' => 0,
                'failed' => 0,
            ];
        }

        $outputFileId = $batch['output_file_id'] ?? null;
        if (! $outputFileId) {
            throw new RuntimeException('OpenAI batch heeft geen output_file_id.');
        }

        $response = Http::withToken($config['key'])
            ->get(rtrim($config['base_url'], '/') . '/v1/files/' . $outputFileId . '/content');

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI batch output download failed: HTTP ' . $response->status() . ' ' . $response->body());
        }

        $created = 0;
        $failed = 0;

        foreach (preg_split('/\r?\n/', trim($response->body())) as $line) {
            if ($line === '') {
                continue;
            }

            $row = json_decode($line, true);
            [$questionId, $modelId] = $this->parseCustomId((string) ($row['custom_id'] ?? ''));
            $body = trim((string) data_get($row, 'response.body.choices.0.message.content', ''));

            if (! $questionId || ! $modelId || $body === '') {
                $failed++;
                continue;
            }

            if ($force) {
                Answer::where('question_id', $questionId)->where('ai_model_id', $modelId)->delete();
            }

            Answer::create([
                'question_id' => $questionId,
                'ai_model_id' => $modelId,
                'is_ai' => true,
                'body' => $body,
                'status' => 'completed',
                'actual_provider' => 'openai-batch',
                'actual_model' => (string) data_get($row, 'response.body.model', config('ai.openai.default_model')),
                'estimated_cost_usd' => 0,
                'error_message' => null,
            ]);

            Question::whereKey($questionId)->update(['answers_generated' => true]);
            $created++;
        }

        return [
            'status' => 'completed',
            'created' => $created,
            'failed' => $failed,
        ];
    }

    private function customId(int $questionId, int $modelId): string
    {
        return "question:{$questionId}:model:{$modelId}";
    }

    private function parseCustomId(string $customId): array
    {
        if (! preg_match('/^question:(\d+):model:(\d+)$/', $customId, $matches)) {
            return [null, null];
        }

        return [(int) $matches[1], (int) $matches[2]];
    }

    private function systemPrompt(AiModel $model): string
    {
        $basePrompt = $model->system_prompt
            ?: 'Je bent een behulpzame Nederlandse adviseur. Geef een kort, praktisch en stapsgewijs antwoord.';

        if ($model->slug === 'openai') {
            return $basePrompt;
        }

        $name = $model->name ?: $model->slug;

        return trim($basePrompt . "\n\n" . implode(' ', [
            "Je draait technisch via OpenAI Batch, maar dit antwoord wordt getoond onder de AI-tab {$name}.",
            "Volg daarom het profiel, de toon en de antwoordstijl van {$name}.",
            "Noem geen fallback, batchverwerking of interne providerroutering.",
        ]));
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
