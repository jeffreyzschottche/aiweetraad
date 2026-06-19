<?php

namespace App\Services\AI;

use App\Models\AiModel;
use App\Models\Answer;
use App\Models\Question;

class AnswerGenerator
{
    public function __construct(
        private StubDriver $stub,
        private ClaudeDriver $claude,
        private OpenAiDriver $openai,
        private GeminiDriver $gemini,
        private DeepSeekDriver $deepseek,
        private GrokDriver $grok,
        private ProviderSelector $selector,
        private AiBudgetAlertService $alerts,
    ) {
    }

    /**
     * Generate (and persist/cache) one answer per enabled AI model for the
     * given question. Idempotent: skips models that already have an answer,
     * so it doubles as the cache layer. Returns the created answers.
     */
    public function generateForQuestion(Question $question, bool $force = false): array
    {
        $models = AiModel::enabled()->orderBy('sort_order')->get();
        $created = [];

        foreach ($models as $model) {
            $answer = $this->generateForQuestionModel($question, $model, $force);
            if ($answer) {
                $created[] = $answer;
            }
        }

        $question->forceFill(['answers_generated' => true])->save();

        return $created;
    }

    public function generateForQuestionModel(Question $question, AiModel $model, bool $force = false): ?Answer
    {
        $existing = Answer::where('question_id', $question->id)
            ->where('ai_model_id', $model->id)
            ->first();

        if ($existing && ! $force) {
            return null;
        }

        if ($existing && $force) {
            $existing->delete();
        }

        $generated = $this->generateAnswerData($question, $model);

        return Answer::create([
            'question_id' => $question->id,
            'ai_model_id' => $model->id,
            'is_ai' => true,
            'body' => $generated['body'],
            'status' => $generated['status'],
            'actual_provider' => $generated['actual_provider'],
            'actual_model' => $generated['actual_model'],
            'estimated_cost_usd' => $generated['estimated_cost_usd'],
            'error_message' => $generated['error_message'],
        ]);
    }

    private function generateAnswerData(Question $question, AiModel $model): array
    {
        $lastError = null;
        $candidates = $this->selector->candidatesFor($model, $question);

        if ($candidates === []) {
            $message = 'Geen beschikbare provider met API-key en budget voor dit model.';
            $this->alerts->notifyNoProviderAvailable($question, $model, $message);

            return $this->failedAnswer($message, $question, $model);
        }

        foreach ($candidates as $candidate) {
            try {
                $runtimeModel = $model->replicate();
                $runtimeModel->forceFill([
                    'provider' => $candidate['provider'],
                    'model_identifier' => $candidate['model'],
                    'system_prompt' => $this->systemPromptForCandidate($model, $candidate),
                ]);

                $body = $this->driverFor($candidate['provider'])->generate($question, $runtimeModel);
                $this->selector->recordSpend($candidate['provider'], (float) $candidate['estimated_cost_usd']);

                return [
                    'body' => $body,
                    'status' => ($candidate['is_fallback'] ?? false) ? 'fallback' : 'completed',
                    'actual_provider' => $candidate['provider'],
                    'actual_model' => $candidate['model'],
                    'estimated_cost_usd' => (float) $candidate['estimated_cost_usd'],
                    'error_message' => null,
                ];
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                $this->selector->recordFailure($candidate['provider']);
                report($e);
            }
        }

        $message = $lastError ?: 'Alle providers faalden voor dit antwoord.';
        $this->alerts->notifyAllCandidatesFailed($question, $model, $message);

        return $this->failedAnswer($message, $question, $model);
    }

    private function failedAnswer(string $message, Question $question, AiModel $model): array
    {
        $fallbackAllowed = (bool) config('ai.allow_stub_fallback', false);

        if ($fallbackAllowed) {
            return [
                'body' => $this->stub->generate($question, $model),
                'status' => 'fallback',
                'actual_provider' => 'stub',
                'actual_model' => $model->model_identifier,
                'estimated_cost_usd' => 0,
                'error_message' => $message,
            ];
        }

        return [
            'body' => 'Sorry, het is nu te druk om dit AI-antwoord te maken. Probeer je aanvraag later opnieuw.',
            'status' => 'failed',
            'actual_provider' => null,
            'actual_model' => null,
            'estimated_cost_usd' => 0,
            'error_message' => $message,
        ];
    }

    private function systemPromptForCandidate(AiModel $requestedModel, array $candidate): string
    {
        $basePrompt = $requestedModel->system_prompt
            ?: 'Je bent een behulpzame Nederlandse adviseur. Geef een kort, praktisch en stapsgewijs antwoord.';

        if (! ($candidate['is_fallback'] ?? false)) {
            return $basePrompt;
        }

        $requestedName = $requestedModel->name ?: $requestedModel->slug;
        $technicalProvider = $candidate['provider'];

        return trim($basePrompt . "\n\n" . implode(' ', [
            "Je draait technisch via {$technicalProvider}, maar dit antwoord wordt getoond onder de AI-tab {$requestedName}.",
            "Volg daarom het profiel, de toon en de antwoordstijl van {$requestedName}.",
            "Noem niet dat je een fallback-provider bent en noem geen interne providerroutering.",
        ]));
    }

    private function driverFor(string $provider): AnswerDriver
    {
        if (! config('ai.generation_enabled')) {
            return $this->stub;
        }

        return match ($provider) {
            'claude' => $this->claude,
            'openai' => $this->openai,
            'gemini' => $this->gemini,
            'deepseek' => $this->deepseek,
            'grok' => $this->grok,
            default => $this->stub,
        };
    }
}
