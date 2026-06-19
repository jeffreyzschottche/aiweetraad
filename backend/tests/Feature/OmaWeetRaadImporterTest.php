<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Question;
use App\Services\Content\OmaWeetRaadImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OmaWeetRaadImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_importer_creates_questions_from_oma_links_and_deduplicates(): void
    {
        Category::query()->create([
            'name' => 'Diversen',
            'slug' => 'diversen',
            'sort_order' => 1,
        ]);

        Http::fake([
            'omaweetraad.nl/*' => Http::response('
                <html>
                    <body>
                        <a href="/gezondheid/hoesten">Hoesten 55 tips</a>
                        <a href="/schoonmaken/kalkaanslag-verwijderen">Kalkaanslag verwijderen 12 tips</a>
                        <a href="/login">Inloggen</a>
                    </body>
                </html>
            '),
        ]);

        $importer = app(OmaWeetRaadImporter::class);

        $firstRun = $importer->import(limit: 10, dryRun: false, sourceUrl: 'https://omaweetraad.nl/', maxPages: 1);
        $secondRun = $importer->import(limit: 10, dryRun: false, sourceUrl: 'https://omaweetraad.nl/', maxPages: 1);

        $this->assertCount(2, $firstRun['created']);
        $this->assertCount(0, $secondRun['created']);
        $this->assertSame(2, Question::count());
        $this->assertDatabaseHas('questions', [
            'title' => 'Wat helpt bij hoesten?',
            'source_name' => 'omaweetraad.nl',
            'answers_generated' => false,
        ]);
        $this->assertDatabaseHas('questions', [
            'title' => 'Hoe kan ik kalkaanslag verwijderen?',
            'source_name' => 'omaweetraad.nl',
        ]);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://omaweetraad.nl/');
    }

    public function test_importer_keeps_category_crawls_inside_matching_breadcrumbs(): void
    {
        $schoonmaken = Category::query()->create([
            'name' => 'Schoonmaken',
            'slug' => 'schoonmaken',
            'sort_order' => 1,
        ]);

        Category::query()->create([
            'name' => 'Huis en Tuin',
            'slug' => 'huis-en-tuin',
            'sort_order' => 2,
        ]);

        Http::fake([
            'https://omaweetraad.nl/schoonmaken' => Http::response('
                <html>
                    <body>
                        <a href="/kalkaanslag-verwijderen">Kalkaanslag verwijderen 12 tips</a>
                        <a href="/mieren-in-huis">Mieren in huis 7 tips</a>
                    </body>
                </html>
            '),
            'https://omaweetraad.nl/kalkaanslag-verwijderen' => Http::response($this->topicHtml('Schoonmaken')),
            'https://omaweetraad.nl/mieren-in-huis' => Http::response($this->topicHtml('Huis en Tuin')),
        ]);

        $result = app(OmaWeetRaadImporter::class)->import(
            limit: 10,
            dryRun: false,
            sourceUrl: 'https://omaweetraad.nl/schoonmaken',
            maxPages: 1,
            validateSourcePages: true,
        );

        $this->assertCount(1, $result['created']);
        $this->assertDatabaseHas('questions', [
            'title' => 'Hoe kan ik kalkaanslag verwijderen?',
            'category_id' => $schoonmaken->id,
        ]);
        $this->assertDatabaseMissing('questions', [
            'title' => 'Wat helpt bij mieren in huis?',
        ]);
    }

    public function test_importer_creates_unknown_source_categories_from_breadcrumbs(): void
    {
        Http::fake([
            'https://omaweetraad.nl/' => Http::response('
                <html>
                    <body>
                        <a href="/fietsbel-repareren">Fietsbel repareren 4 tips</a>
                    </body>
                </html>
            '),
            'https://omaweetraad.nl/fietsbel-repareren' => Http::response($this->topicHtml('Reparaties')),
        ]);

        app(OmaWeetRaadImporter::class)->import(
            limit: 10,
            dryRun: false,
            sourceUrl: 'https://omaweetraad.nl/',
            maxPages: 1,
            validateSourcePages: true,
        );

        $category = Category::where('slug', 'reparaties')->first();

        $this->assertNotNull($category);
        $this->assertDatabaseHas('questions', [
            'title' => 'Wat helpt bij fietsbel repareren?',
            'category_id' => $category->id,
        ]);
    }

    private function topicHtml(string $category): string
    {
        return '
            <html>
                <head><meta name="description" content="Een praktisch onderwerp"></head>
                <body>
                    <ul class="breadcrumbs">
                        <li><span itemprop="name">Home</span></li>
                        <li><span itemprop="name">' . e($category) . '</span></li>
                    </ul>
                    <h1>Onderwerp</h1>
                </body>
            </html>
        ';
    }
}
