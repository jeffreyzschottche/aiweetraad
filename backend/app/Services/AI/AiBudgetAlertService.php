<?php

namespace App\Services\AI;

use App\Mail\AiBudgetAlertMail;
use App\Models\AiModel;
use App\Models\AiProviderUsage;
use App\Models\Question;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class AiBudgetAlertService
{
    public function notifyNoProviderAvailable(Question $question, AiModel $model, string $reason): void
    {
        $this->sendOnce('no-provider-available', $question, $model, $reason);
    }

    public function notifyAllCandidatesFailed(Question $question, AiModel $model, string $reason): void
    {
        $this->sendOnce('all-candidates-failed', $question, $model, $reason);
    }

    private function sendOnce(string $type, Question $question, AiModel $model, string $reason): void
    {
        if (! (bool) config('ai.alerts.enabled', true)) {
            return;
        }

        $adminEmail = config('ai.alerts.admin_email');
        if (! is_string($adminEmail) || trim($adminEmail) === '') {
            return;
        }

        $cacheKey = 'ai-budget-alert:' . $type;
        $ttl = now()->addMinutes(max(1, (int) config('ai.alerts.throttle_minutes', 60)));

        if (! Cache::add($cacheKey, now()->toIso8601String(), $ttl)) {
            return;
        }

        Mail::to($adminEmail)->send(new AiBudgetAlertMail(
            'AI provider alert: ' . config('app.name'),
            $this->body($type, $question, $model, $reason),
        ));
    }

    private function body(string $type, Question $question, AiModel $model, string $reason): string
    {
        $providers = collect(config('ai.providers', []))
            ->map(function (array $provider, string $name) {
                $credit = (float) ($provider['credit_usd'] ?? 0);
                $spent = AiProviderUsage::spentToday($name) + (float) ($provider['spent_today_usd'] ?? 0);

                return sprintf(
                    '- %s: key=%s, credit=$%.2f, spent_today=$%.6f, remaining=$%.6f',
                    $name,
                    empty($provider['key']) ? 'missing' : 'present',
                    $credit,
                    $spent,
                    max(0, $credit - $spent),
                );
            })
            ->implode("\n");

        return implode("\n\n", [
            'AI generation needs attention.',
            'Type: ' . $type,
            'Reason: ' . $reason,
            'Question: #' . $question->id . ' ' . $question->title,
            'Requested model: ' . $model->slug . ' (' . $model->provider . ')',
            'Provider status:',
            $providers,
        ]);
    }
}
