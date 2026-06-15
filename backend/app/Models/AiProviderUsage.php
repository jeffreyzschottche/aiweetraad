<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AiProviderUsage extends Model
{
    protected $fillable = [
        'provider',
        'usage_date',
        'estimated_spend_usd',
        'requests',
        'failures',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'estimated_spend_usd' => 'decimal:6',
    ];

    public static function spentToday(string $provider): float
    {
        return (float) static::query()
            ->where('provider', $provider)
            ->whereDate('usage_date', now()->toDateString())
            ->value('estimated_spend_usd');
    }

    public static function recordSpend(string $provider, float $estimatedCostUsd): void
    {
        static::bump($provider, max(0, $estimatedCostUsd), 1, 0);
    }

    public static function recordFailure(string $provider): void
    {
        static::bump($provider, 0, 0, 1);
    }

    private static function bump(string $provider, float $cost, int $requests, int $failures): void
    {
        DB::transaction(function () use ($provider, $cost, $requests, $failures) {
            $usage = static::query()
                ->where('provider', $provider)
                ->whereDate('usage_date', now()->toDateString())
                ->lockForUpdate()
                ->first();

            if (! $usage) {
                $usage = static::query()->create([
                    'provider' => $provider,
                    'usage_date' => now()->toDateString(),
                    'estimated_spend_usd' => 0,
                    'requests' => 0,
                    'failures' => 0,
                ]);
            }

            $usage->forceFill([
                'estimated_spend_usd' => (float) $usage->estimated_spend_usd + $cost,
                'requests' => $usage->requests + $requests,
                'failures' => $usage->failures + $failures,
            ])->save();
        });
    }
}
