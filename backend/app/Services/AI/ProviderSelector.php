<?php

namespace App\Services\AI;

use App\Models\AiModel;
use App\Models\AiProviderUsage;
use App\Models\Question;

class ProviderSelector
{
    public function __construct(private ProviderAccountStatusService $accounts)
    {
    }

    public function candidatesFor(AiModel $model, Question $question): array
    {
        if (! config('ai.generation_enabled')) {
            return [[
                'provider' => 'stub',
                'model' => $model->model_identifier,
                'estimated_cost_usd' => 0.0,
                'is_fallback' => false,
            ]];
        }

        $providers = collect([$model->provider])
            ->merge(config('ai.fallback_order', []))
            ->unique()
            ->values();

        $inputTokens = $this->estimateTokens($this->prompt($question));
        $outputTokens = (int) config('ai.expected_output_tokens', 700);
        $candidates = [];

        foreach ($providers as $provider) {
            if ($provider === 'stub') {
                if ((bool) config('ai.allow_stub_fallback', false)) {
                    $candidates[] = [
                        'provider' => 'stub',
                        'model' => $model->model_identifier,
                        'estimated_cost_usd' => 0.0,
                        'is_fallback' => $provider !== $model->provider,
                    ];
                }

                continue;
            }

            $config = config("ai.providers.$provider");
            if (empty($config['key']) || empty($config['models'])) {
                continue;
            }

            if (! $this->accounts->usableForRouting($provider)) {
                continue;
            }

            foreach ($this->orderedModels($config['models'], $model, $provider) as $candidateModel) {
                $cost = $this->estimateCost($candidateModel, $inputTokens, $outputTokens);
                $candidates[] = [
                    'provider' => $provider,
                    'model' => $candidateModel['model'],
                    'estimated_cost_usd' => $cost,
                    'is_fallback' => $provider !== $model->provider,
                ];
            }
        }

        return $candidates;
    }

    public function recordSpend(string $provider, float $estimatedCostUsd): void
    {
        if ($provider === 'stub' || $estimatedCostUsd <= 0) {
            return;
        }

        AiProviderUsage::recordSpend($provider, $estimatedCostUsd);
    }

    public function recordFailure(string $provider): void
    {
        if ($provider === 'stub') {
            return;
        }

        AiProviderUsage::recordFailure($provider);
    }

    private function orderedModels(array $models, AiModel $requestedModel, string $provider): array
    {
        if ($requestedModel->provider !== $provider || empty($requestedModel->model_identifier)) {
            return $models;
        }

        usort($models, function (array $a, array $b) use ($requestedModel) {
            return ($b['model'] === $requestedModel->model_identifier) <=> ($a['model'] === $requestedModel->model_identifier);
        });

        return $models;
    }

    private function estimateCost(array $model, int $inputTokens, int $outputTokens): float
    {
        return (($inputTokens / 1000000) * (float) $model['input_per_million'])
            + (($outputTokens / 1000000) * (float) $model['output_per_million']);
    }

    private function estimateTokens(string $text): int
    {
        return max(1, (int) ceil(mb_strlen($text) / 4));
    }

    private function prompt(Question $question): string
    {
        return trim($question->title . "\n\n" . ($question->body ?? ''));
    }

}
