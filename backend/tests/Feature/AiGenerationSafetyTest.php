<?php

namespace Tests\Feature;

use App\Models\AiModel;
use App\Models\Question;
use App\Mail\AiBudgetAlertMail;
use App\Services\AI\AnswerGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AiGenerationSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_generation_without_budget_or_key_creates_failed_answer(): void
    {
        config([
            'ai.generation_enabled' => true,
            'ai.allow_stub_fallback' => false,
            'ai.providers.openai.key' => null,
            'ai.providers.openai.credit_usd' => 0,
            'ai.fallback_order' => [],
        ]);

        $model = AiModel::query()->create([
            'name' => 'OpenAI',
            'slug' => 'openai',
            'provider' => 'openai',
            'model_identifier' => 'gpt-test',
            'enabled' => true,
        ]);
        $question = Question::query()->create([
            'title' => 'Hoe test ik live AI fallback?',
            'slug' => 'hoe-test-ik-live-ai-fallback',
            'status' => 'published',
        ]);

        app(AnswerGenerator::class)->generateForQuestion($question);

        $answer = $question->answers()->firstOrFail();

        $this->assertSame($model->id, $answer->ai_model_id);
        $this->assertSame('failed', $answer->status);
        $this->assertNull($answer->actual_provider);
    }

    public function test_no_provider_available_sends_throttled_admin_alert(): void
    {
        Mail::fake();

        config([
            'ai.generation_enabled' => true,
            'ai.allow_stub_fallback' => false,
            'ai.alerts.enabled' => true,
            'ai.alerts.admin_email' => 'admin@example.com',
            'ai.alerts.throttle_minutes' => 60,
            'ai.providers.openai.key' => null,
            'ai.providers.openai.credit_usd' => 0,
            'ai.fallback_order' => [],
        ]);

        AiModel::query()->create([
            'name' => 'OpenAI',
            'slug' => 'openai',
            'provider' => 'openai',
            'model_identifier' => 'gpt-test',
            'enabled' => true,
        ]);

        foreach (['alert-test-een', 'alert-test-twee'] as $slug) {
            $question = Question::query()->create([
                'title' => 'Hoe test ik AI budget alert?',
                'slug' => $slug,
                'status' => 'published',
            ]);

            app(AnswerGenerator::class)->generateForQuestion($question);
        }

        Mail::assertSent(AiBudgetAlertMail::class, 1);
        Mail::assertSent(AiBudgetAlertMail::class, fn (AiBudgetAlertMail $mail) => $mail->hasTo('admin@example.com'));
    }

    public function test_stub_fallback_must_be_explicitly_enabled(): void
    {
        config([
            'ai.generation_enabled' => true,
            'ai.allow_stub_fallback' => true,
            'ai.providers.openai.key' => null,
            'ai.providers.openai.credit_usd' => 0,
            'ai.fallback_order' => [],
        ]);

        AiModel::query()->create([
            'name' => 'OpenAI',
            'slug' => 'openai',
            'provider' => 'openai',
            'model_identifier' => 'gpt-test',
            'enabled' => true,
        ]);
        $question = Question::query()->create([
            'title' => 'Hoe test ik expliciete stub fallback?',
            'slug' => 'hoe-test-ik-expliciete-stub-fallback',
            'status' => 'published',
        ]);

        app(AnswerGenerator::class)->generateForQuestion($question);

        $answer = $question->answers()->firstOrFail();

        $this->assertSame('fallback', $answer->status);
        $this->assertSame('stub', $answer->actual_provider);
    }
}
