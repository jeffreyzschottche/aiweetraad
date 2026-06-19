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
use App\Services\AI\OpenAiBatchAnswerService;
use App\Services\AI\ProviderAccountStatusService;
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
    {--source-name= : Alleen vragen met deze source_name opnieuw genereren}
    {--dry-run : Toon wat er zou gebeuren zonder antwoorden te genereren}
    {--allow-stub-fallback : Sta offline fallback toe als alle echte providers falen}', function () {
    config([
        'ai.generation_enabled' => true,
        'ai.allow_stub_fallback' => (bool) $this->option('allow-stub-fallback'),
    ]);

    $accountStatus = app(ProviderAccountStatusService::class);
    $providers = collect($accountStatus->all());
    $providerStatusRow = function (array $row): array {
        $models = collect(config("ai.providers.{$row['provider']}.models", []))
            ->pluck('model')
            ->filter()
            ->implode(', ');

        $live = match (true) {
            $row['balance_supported'] => $row['balance'] === null
                ? 'saldo onbekend'
                : number_format((float) $row['balance'], 4) . ' ' . ($row['currency'] ?? ''),
            $row['cost_today_usd'] !== null => 'admin kosten vandaag $' . number_format((float) $row['cost_today_usd'], 6),
            default => 'niet beschikbaar',
        };

        return [
            $row['provider'],
            $row['key_present'] ? 'ja' : 'nee',
            $models ?: '-',
            trim($live),
            $row['source'] . ($row['error'] ? ' (' . $row['error'] . ')' : ''),
            number_format((float) $row['spent_today_usd'], 6),
        ];
    };

    $this->table(
        ['Provider', 'API key', 'Model(len)', 'Provider saldo/kosten', 'Statusbron', 'Lokale spend vandaag USD'],
        $providers->map(fn (array $row) => $providerStatusRow($row))->values()->all()
    );

    $hasUsableProvider = $providers->contains(
        fn (array $row) => $row['key_present'] && $row['is_available']
    );

    if (! $hasUsableProvider && ! $this->option('allow-stub-fallback')) {
        $this->error('Geen provider heeft een bruikbare API-key/saldo. Check provider billing/keys of gebruik --allow-stub-fallback voor demo-antwoorden.');
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

    if ($sourceName = $this->option('source-name')) {
        $query->where('source_name', $sourceName);
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

    $runStartSpend = collect(config('ai.providers', []))
        ->keys()
        ->mapWithKeys(fn (string $provider) => [$provider => AiProviderUsage::spentToday($provider)]);

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

    $providersAfter = collect($accountStatus->all());
    $this->table(
        ['Provider', 'API key', 'Model(len)', 'Provider saldo/kosten', 'Statusbron', 'Lokale spend vandaag USD'],
        $providersAfter->map(fn (array $row) => $providerStatusRow($row))->values()->all()
    );

    $this->table(
        ['Provider', 'Lokale kosten deze run USD', 'Lokale spend vandaag USD'],
        $providersAfter
            ->keys()
            ->map(fn (string $provider) => [
                $provider,
                number_format(max(0, AiProviderUsage::spentToday($provider) - (float) ($runStartSpend[$provider] ?? 0)), 6),
                number_format(AiProviderUsage::spentToday($provider), 6),
            ])
            ->values()
            ->all()
    );

    return 0;
})->purpose('Generate real AI answers for existing questions using configured provider keys and budgets');

Artisan::command('content:generate-ai-answers-batch
    {--limit=100 : Maximaal aantal vragen}
    {--question= : Alleen deze vraag-slug meenemen}
    {--source-name= : Alleen vragen met deze source_name meenemen}
    {--force : Vervang bestaande OpenAI-batch antwoorden bij collect}
    {--dry-run : Toon selectie zonder batch te starten}', function (OpenAiBatchAnswerService $batchService) {
    $query = Question::query()
        ->where('status', 'published')
        ->orderBy('id');

    if ($slug = $this->option('question')) {
        $query->where('slug', $slug);
    }

    if ($sourceName = $this->option('source-name')) {
        $query->where('source_name', $sourceName);
    }

    $limit = max(1, (int) $this->option('limit'));
    $questions = $query->limit($limit)->get(['id', 'slug', 'title']);

    if ($questions->isEmpty()) {
        $this->info('Geen vragen gevonden voor batch.');
        return 0;
    }

    $this->info(($this->option('dry-run') ? 'Dry-run: ' : '') . $questions->count() . ' vraag/vragen geselecteerd voor OpenAI Batch.');
    $questions->take(20)->each(fn (Question $question) => $this->line('- ' . $question->slug . ' | ' . $question->title));

    if ($this->option('dry-run')) {
        return 0;
    }

    $result = $batchService->create($questions->pluck('id')->all(), force: (bool) $this->option('force'));

    $this->info('OpenAI batch gestart.');
    $this->line('Batch ID: ' . $result['batch_id']);
    $this->line('Input file ID: ' . $result['input_file_id']);
    $this->line('Lokale input: ' . $result['local_path']);
    $this->line('Requests: ' . $result['requests']);
    $this->newLine();
    $this->line('Status checken: php artisan content:ai-batch-status ' . $result['batch_id']);
    $this->line('Resultaten ophalen: php artisan content:collect-ai-batch ' . $result['batch_id']);

    return 0;
})->purpose('Submit OpenAI Batch API answer generation for offline bulk jobs');

Artisan::command('content:import-oma-batch
    {--limit=100 : Maximaal aantal nieuwe vragen}
    {--pages=150 : Maximaal aantal bronpagina\'s om te bezoeken}
    {--source=https://omaweetraad.nl/ : Start-URL}
    {--skip-deepseek : Genereer de DeepSeek-tab niet direct synchroon}
    {--no-wait : Alleen importeren en batch starten, niet wachten/collecten}
    {--poll=60 : Aantal seconden tussen batch statuschecks}
    {--timeout=1440 : Maximaal aantal minuten wachten op OpenAI Batch}
    {--dry-run : Toon import en batchselectie zonder database/API-writes}', function (
        OmaWeetRaadImporter $importer,
        OpenAiBatchAnswerService $batchService,
        AnswerGenerator $generator
    ) {
        $result = $importer->import(
            limit: (int) $this->option('limit'),
            dryRun: (bool) $this->option('dry-run'),
            sourceUrl: (string) $this->option('source'),
            maxPages: (int) $this->option('pages'),
            validateSourcePages: true,
        );

        $created = collect($result['created']);

        $this->info(($this->option('dry-run') ? 'Dry-run: ' : '') . $created->count() . ' vraag/vragen geïmporteerd.');
        $this->line('Bezochte pagina\'s: ' . count($result['visited_pages']) . ', overgeslagen: ' . $result['skipped']);

        $created->take(20)->each(function ($question) {
            $title = is_array($question) ? $question['title'] : $question->title;
            $this->line('- ' . $title);
        });

        if ($created->isEmpty()) {
            $this->warn('Geen nieuwe vragen geïmporteerd; er wordt geen batch gestart.');
            return 0;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry-run: OpenAI Batch zou worden gestart voor ' . $created->count() . ' vraag/vragen.');
            return 0;
        }

        $batch = $batchService->create($created->pluck('id')->all(), force: true);

        $this->info('OpenAI batch gestart voor de nieuwe import.');
        $this->line('Batch ID: ' . $batch['batch_id']);
        $this->line('Input file ID: ' . $batch['input_file_id']);
        $this->line('Lokale input: ' . $batch['local_path']);
        $this->line('Requests: ' . $batch['requests']);

        if (! $this->option('skip-deepseek')) {
            $deepSeek = AiModel::where('slug', 'deepseek')->where('enabled', true)->first();
            if ($deepSeek) {
                $this->info('DeepSeek-antwoorden direct genereren...');
                $bar = $this->output->createProgressBar($created->count());
                $bar->start();

                $deepSeekCreated = 0;
                foreach ($created as $question) {
                    if ($generator->generateForQuestionModel($question, $deepSeek, force: true)) {
                        $deepSeekCreated++;
                    }
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine(2);
                $this->info("DeepSeek klaar. {$deepSeekCreated} antwoord(en) opgeslagen.");
            } else {
                $this->warn('DeepSeek-model is niet actief; DeepSeek-tab is overgeslagen.');
            }
        }

        $this->newLine();
        $this->line('Status checken: php artisan content:ai-batch-status ' . $batch['batch_id']);
        $this->line('Resultaten ophalen: php artisan content:collect-ai-batch ' . $batch['batch_id']);

        if ($this->option('no-wait')) {
            return 0;
        }

        $pollSeconds = max(10, (int) $this->option('poll'));
        $deadline = now()->addMinutes(max(1, (int) $this->option('timeout')));
        $this->newLine();
        $this->info("Wachten op OpenAI Batch. Poll elke {$pollSeconds}s tot {$deadline->format('Y-m-d H:i:s')}.");

        while (now()->lessThan($deadline)) {
            $status = $batchService->status($batch['batch_id']);
            $state = (string) ($status['status'] ?? 'unknown');
            $counts = $status['request_counts'] ?? [];
            $this->line(sprintf(
                '[%s] status=%s completed=%s/%s failed=%s',
                now()->format('H:i:s'),
                $state,
                $counts['completed'] ?? 0,
                $counts['total'] ?? 0,
                $counts['failed'] ?? 0,
            ));

            if ($state === 'completed') {
                $result = $batchService->collect($batch['batch_id'], force: true);
                $this->info("OpenAI Batch opgehaald. {$result['created']} antwoorden opgeslagen, {$result['failed']} mislukt/overgeslagen.");
                return 0;
            }

            if (in_array($state, ['failed', 'expired', 'cancelled'], true)) {
                $this->error('OpenAI Batch is gestopt met status: ' . $state);
                return 1;
            }

            sleep($pollSeconds);
        }

        $this->warn('Timeout bereikt. Batch loopt mogelijk nog. Je kunt later alsnog collecten: php artisan content:collect-ai-batch ' . $batch['batch_id']);

        return 0;
    })->purpose('Import Oma topics and submit OpenAI Batch answer generation in one command');

Artisan::command('content:ai-batch-status {batch_id}', function (OpenAiBatchAnswerService $batchService, string $batch_id) {
    $status = $batchService->status($batch_id);

    $this->table(
        ['Batch ID', 'Status', 'Output file', 'Errors file'],
        [[
            $status['id'] ?? $batch_id,
            $status['status'] ?? 'unknown',
            $status['output_file_id'] ?? '-',
            $status['error_file_id'] ?? '-',
        ]]
    );

    if (isset($status['request_counts'])) {
        $this->table(
            ['Total', 'Completed', 'Failed'],
            [[
                $status['request_counts']['total'] ?? 0,
                $status['request_counts']['completed'] ?? 0,
                $status['request_counts']['failed'] ?? 0,
            ]]
        );
    }

    return 0;
})->purpose('Check OpenAI Batch API status');

Artisan::command('content:collect-ai-batch
    {batch_id : OpenAI batch id}
    {--no-force : Bestaande antwoorden niet verwijderen}', function (OpenAiBatchAnswerService $batchService, string $batch_id) {
    $result = $batchService->collect($batch_id, force: ! (bool) $this->option('no-force'));

    if ($result['status'] !== 'completed') {
        $this->warn('Batch is nog niet klaar. Status: ' . $result['status']);
        return 0;
    }

    $this->info("Batch verwerkt. {$result['created']} antwoorden opgeslagen, {$result['failed']} mislukt/overgeslagen.");

    return 0;
})->purpose('Download and persist OpenAI Batch API answer results');

Artisan::command('content:import-oma
    {--limit=100 : Maximaal aantal nieuwe vragen}
    {--pages=10 : Maximaal aantal bronpagina\'s om te bezoeken}
    {--source=https://omaweetraad.nl/ : Start-URL}
    {--dry-run : Toon import zonder database-writes}
    {--generate-ai : Genereer direct echte AI-antwoorden voor geimporteerde vragen}
    {--source-answers : Gebruik opgeschoonde bron-antwoorden in plaats van echte AI-antwoorden}', function (OmaWeetRaadImporter $importer, AnswerGenerator $generator, OmaWeetRaadSourceAnswerGenerator $sourceGenerator) {
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
            if ($this->option('source-answers') && $question->source_name === 'omaweetraad.nl') {
                app(OmaWeetRaadSourceAnswerGenerator::class)->generateForQuestion($question, force: true);
            } else {
                $generator->generateForQuestion($question, force: true);
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
    {--bodies : Vervang publieke vraagtekst door een neutrale beschrijving}
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

        if ($this->option('bodies')) {
            $question->forceFill([
                'body' => $importer->publicQuestionBody($question->title, $question->source_url),
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
