<?php

namespace App\Services\AI;

use App\Models\AiProviderUsage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ProviderAccountStatusService
{
    public function all(): array
    {
        return collect(config('ai.providers', []))
            ->keys()
            ->mapWithKeys(fn (string $provider) => [$provider => $this->status($provider)])
            ->all();
    }

    public function status(string $provider): array
    {
        $config = config("ai.providers.$provider", []);
        $keyPresent = ! empty($config['key']);

        $status = [
            'provider' => $provider,
            'key_present' => $keyPresent,
            'balance_supported' => false,
            'is_available' => $keyPresent,
            'balance' => null,
            'currency' => null,
            'cost_today_usd' => null,
            'source' => $keyPresent ? 'api-key' : 'missing-key',
            'error' => null,
            'spent_today_usd' => AiProviderUsage::spentToday($provider),
        ];

        if (! $keyPresent) {
            return $status;
        }

        return match ($provider) {
            'openai' => $this->openAiStatus($status),
            'deepseek' => $this->deepSeekStatus($status),
            default => $status,
        };
    }

    public function usableForRouting(string $provider): bool
    {
        $status = $this->status($provider);

        if (! $status['key_present']) {
            return false;
        }

        if ($status['balance_supported']) {
            return (bool) $status['is_available'];
        }

        return true;
    }

    private function deepSeekStatus(array $base): array
    {
        return Cache::remember('ai-provider-status:deepseek', now()->addMinute(), function () use ($base) {
            $config = config('ai.deepseek');

            try {
                $response = Http::withToken($config['key'])
                    ->timeout(10)
                    ->get(rtrim($config['base_url'], '/') . '/user/balance');
            } catch (\Throwable $e) {
                return [
                    ...$base,
                    'balance_supported' => true,
                    'is_available' => true,
                    'source' => 'balance-api-error',
                    'error' => $e->getMessage(),
                ];
            }

            if (! $response->successful()) {
                return [
                    ...$base,
                    'balance_supported' => true,
                    'is_available' => true,
                    'source' => 'balance-api-error',
                    'error' => 'HTTP ' . $response->status(),
                ];
            }

            $balances = collect($response->json('balance_infos', []));
            $usd = $balances->firstWhere('currency', 'USD');
            $balance = $usd ?: $balances->first();
            $amount = $balance ? (float) ($balance['total_balance'] ?? 0) : null;
            $currency = $balance['currency'] ?? null;

            return [
                ...$base,
                'balance_supported' => true,
                'is_available' => (bool) $response->json('is_available', false) && ($amount === null || $amount > 0),
                'balance' => $amount,
                'currency' => $currency,
                'source' => 'balance-api',
                'error' => null,
            ];
        });
    }

    private function openAiStatus(array $base): array
    {
        $config = config('ai.openai');
        if (empty($config['admin_key'])) {
            return $base;
        }

        return Cache::remember('ai-provider-status:openai', now()->addMinute(), function () use ($base, $config) {
            try {
                $response = Http::withToken($config['admin_key'])
                    ->timeout(10)
                    ->get(rtrim($config['base_url'], '/') . '/v1/organization/costs', [
                        'start_time' => now()->startOfDay()->timestamp,
                        'bucket_width' => '1d',
                    ]);
            } catch (\Throwable $e) {
                return [
                    ...$base,
                    'source' => 'admin-costs-api-error',
                    'error' => $e->getMessage(),
                ];
            }

            if (! $response->successful()) {
                return [
                    ...$base,
                    'source' => 'admin-costs-api-error',
                    'error' => 'HTTP ' . $response->status(),
                ];
            }

            $cost = collect($response->json('data', []))
                ->flatMap(fn (array $bucket) => $bucket['results'] ?? [])
                ->sum(fn (array $result) => (float) data_get($result, 'amount.value', 0));

            return [
                ...$base,
                'cost_today_usd' => $cost,
                'source' => 'admin-costs-api-delayed',
                'error' => null,
            ];
        });
    }
}
