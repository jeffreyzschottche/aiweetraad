<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\AiModelSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\QuestionSeeder;
use App\Models\AiProviderUsage;
use App\Models\AiModel;
use App\Models\Question;
use App\Services\AI\AnswerGenerator;
use App\Services\Content\OmaWeetRaadImporter;
use App\Services\Content\OmaWeetRaadSourceAnswerGenerator;

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

Artisan::command('content:generate-ai-answers
    {--force : Vervang bestaande antwoorden}
    {--limit=0 : Maximaal aantal vragen, 0 is alles}
    {--question= : Alleen deze vraag-slug opnieuw genereren}
    {--dry-run : Toon wat er zou gebeuren zonder antwoorden te genereren}
    {--allow-stub-fallback : Sta offline fallback toe als alle echte providers falen}', function () {
    config([
        'ai.generation_enabled' => true,
        'ai.allow_stub_fallback' => (bool) $this->option('allow-stub-fallback'),
    ]);

    $providers = collect(config('ai.providers', []))
        ->map(fn (array $provider, string $name) => [
            'provider' => $name,
            'key' => empty($provider['key']) ? 'nee' : 'ja',
            'credit_usd' => (float) ($provider['credit_usd'] ?? 0),
            'spent_today_usd' => AiProviderUsage::spentToday($name) + (float) ($provider['spent_today_usd'] ?? 0),
        ]);

    $this->table(
        ['Provider', 'API key', 'Credit USD', 'Spent today USD'],
        $providers->map(fn ($row) => [
            $row['provider'],
            $row['key'],
            number_format($row['credit_usd'], 2),
            number_format($row['spent_today_usd'], 6),
        ])->values()->all()
    );

    $hasUsableProvider = $providers->contains(
        fn ($row) => $row['key'] === 'ja' && $row['credit_usd'] > $row['spent_today_usd']
    );

    if (! $hasUsableProvider && ! $this->option('allow-stub-fallback')) {
        $this->error('Geen provider heeft tegelijk een API-key en resterend budget. Vul je .env credits/keys of gebruik --allow-stub-fallback.');
        return 1;
    }

    $enabledModelCount = AiModel::enabled()->count();
    if ($enabledModelCount === 0) {
        $this->error('Er zijn geen actieve AI-modellen.');
        return 1;
    }

    $query = Question::query()
        ->where('status', 'published')
        ->withCount('answers')
        ->orderBy('id');

    if ($slug = $this->option('question')) {
        $query->where('slug', $slug);
    }

    $limit = (int) $this->option('limit');
    if ($limit > 0) {
        $query->limit($limit);
    }

    $questions = $query->get();

    if (! $this->option('force')) {
        $questions = $questions
            ->filter(fn (Question $question) =>
                $question->answers_count < $enabledModelCount
                || $question->answers()->where('status', 'failed')->exists()
            )
            ->values();
    }

    if ($questions->isEmpty()) {
        $this->info('Geen vragen gevonden om te genereren.');
        return 0;
    }

    $this->info(($this->option('dry-run') ? 'Dry-run: ' : '') . $questions->count() . ' vraag/vragen geselecteerd.');

    if ($this->option('dry-run')) {
        $questions->each(fn (Question $question) => $this->line('- ' . $question->slug . ' | ' . $question->title));
        return 0;
    }

    $generator = app(AnswerGenerator::class);
    $force = (bool) $this->option('force');
    $bar = $this->output->createProgressBar($questions->count());
    $bar->start();

    $created = 0;
    $failed = 0;

    foreach ($questions as $question) {
        $answers = collect($generator->generateForQuestion($question, $force));
        $created += $answers->count();
        $failed += $answers->where('status', 'failed')->count();
        $bar->advance();
    }

    $bar->finish();
    $this->newLine(2);
    $this->info("Klaar. {$created} antwoorden aangemaakt/vervangen, {$failed} failed.");

    if ($failed > 0) {
        $this->warn('Er zijn failed antwoorden. Check je provider keys, credits en logs.');
    }

    return 0;
})->purpose('Generate real AI answers for existing questions using configured provider keys and budgets');

Artisan::command('content:import-oma
    {--limit=100 : Maximaal aantal nieuwe vragen}
    {--pages=10 : Maximaal aantal bronpagina\'s om te bezoeken}
    {--source=https://omaweetraad.nl/ : Start-URL}
    {--dry-run : Toon import zonder database-writes}
    {--generate-ai : Genereer direct AI-antwoorden voor geimporteerde vragen}', function (OmaWeetRaadImporter $importer, AnswerGenerator $generator, OmaWeetRaadSourceAnswerGenerator $sourceGenerator) {
    $result = $importer->import(
        limit: (int) $this->option('limit'),
        dryRun: (bool) $this->option('dry-run'),
        sourceUrl: (string) $this->option('source'),
        maxPages: (int) $this->option('pages'),
        validateSourcePages: true,
    );

    $created = collect($result['created']);

    $this->info(($this->option('dry-run') ? 'Dry-run: ' : '') . $created->count() . ' vraag/vragen gevonden.');
    $this->line('Bezochte pagina\'s: ' . count($result['visited_pages']) . ', overgeslagen: ' . $result['skipped']);

    $created->take(20)->each(function ($question) {
        $title = is_array($question) ? $question['title'] : $question->title;
        $this->line('- ' . $title);
    });

    if (! $this->option('dry-run') && $this->option('generate-ai')) {
        $bar = $this->output->createProgressBar($created->count());
        $bar->start();

        $created->each(function (Question $question) use ($generator, $bar) {
            if ($question->source_name === 'omaweetraad.nl') {
                app(OmaWeetRaadSourceAnswerGenerator::class)->generateForQuestion($question, force: true);
            } else {
                $generator->generateForQuestion($question);
            }
            $bar->advance();
        });

        $bar->finish();
        $this->newLine();
    }

    return 0;
})->purpose('Import Oma Weet Raad topics as questions in batches');

Artisan::command('content:repair-oma-import
    {--answers : Vervang antwoorden door brongebaseerde Oma-antwoorden}
    {--categories : Herbereken categorieen}
    {--limit=0 : Maximaal aantal vragen, 0 is alles}', function (OmaWeetRaadImporter $importer, OmaWeetRaadSourceAnswerGenerator $sourceGenerator) {
    $query = Question::query()
        ->where('source_name', 'omaweetraad.nl')
        ->orderBy('id');

    $limit = (int) $this->option('limit');
    if ($limit > 0) {
        $query->limit($limit);
    }

    $questions = $query->get();
    $bar = $this->output->createProgressBar($questions->count());
    $bar->start();

    foreach ($questions as $question) {
        if ($this->option('categories')) {
            $question->forceFill([
                'category_id' => $sourceGenerator->categoryIdForQuestion($question)
                    ?: $importer->inferCategoryId($question->title, $question->source_url),
            ])->save();
        }

        if ($this->option('answers')) {
            $sourceGenerator->generateForQuestion($question, force: true);
        }

        $bar->advance();
    }

    $bar->finish();
    $this->newLine(2);
    $this->info('Oma-import hersteld voor ' . $questions->count() . ' vraag/vragen.');

    return 0;
})->purpose('Repair imported Oma Weet Raad categories and source-based answers');

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
