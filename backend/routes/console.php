<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\AiModelSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\QuestionSeeder;
use App\Models\AiProviderUsage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('content:refresh-demo', function () {
    config(['ai.generation_enabled' => false]);

    $this->call('db:seed', ['--class' => CategorySeeder::class]);
    $this->call('db:seed', ['--class' => AiModelSeeder::class]);
    $this->call('db:seed', ['--class' => QuestionSeeder::class]);

    $this->info('Demo categorieen, vragen en AI-antwoorden zijn opnieuw gevuld.');
})->purpose('Refresh category questions and varied demo AI answers without external API calls');

Artisan::command('ai:usage {date?}', function (?string $date = null) {
    $date = $date ?: now()->toDateString();

    $rows = AiProviderUsage::query()
        ->whereDate('usage_date', $date)
        ->orderBy('provider')
        ->get(['provider', 'estimated_spend_usd', 'requests', 'failures']);

    if ($rows->isEmpty()) {
        $this->info("Geen AI-usage gevonden voor {$date}.");
        return;
    }

    $this->table(
        ['Provider', 'Estimated spend USD', 'Requests', 'Failures'],
        $rows->map(fn ($row) => [
            $row->provider,
            number_format((float) $row->estimated_spend_usd, 6),
            $row->requests,
            $row->failures,
        ])->all()
    );
})->purpose('Show estimated AI provider spend and failures for a date');
