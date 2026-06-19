<?php

namespace App\Services\Content;

use App\Models\AiModel;
use App\Models\Answer;
use App\Models\Category;
use App\Models\Question;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OmaWeetRaadSourceAnswerGenerator
{
    public function generateForQuestion(Question $question, bool $force = false): array
    {
        if ($question->source_name !== 'omaweetraad.nl' || ! $question->source_url) {
            return [];
        }

        $source = $this->sourceData($question->source_url);
        if ($source === null) {
            return [];
        }

        if ($categoryId = $this->categoryIdFromSource($source)) {
            $question->forceFill(['category_id' => $categoryId])->save();
        }

        $created = [];

        foreach (AiModel::enabled()->orderBy('sort_order')->get() as $model) {
            $existing = Answer::where('question_id', $question->id)
                ->where('ai_model_id', $model->id)
                ->first();

            if ($existing && ! $force) {
                continue;
            }

            if ($existing && $force) {
                $existing->delete();
            }

            $created[] = Answer::create([
                'question_id' => $question->id,
                'ai_model_id' => $model->id,
                'is_ai' => true,
                'body' => $this->answerBody($question, $model, $source),
                'status' => 'completed',
                'actual_provider' => 'source',
                'actual_model' => 'omaweetraad-source',
                'estimated_cost_usd' => 0,
                'error_message' => null,
            ]);
        }

        $question->forceFill(['answers_generated' => true])->save();

        return $created;
    }

    public function categoryIdForQuestion(Question $question): ?int
    {
        if ($question->source_name !== 'omaweetraad.nl' || ! $question->source_url) {
            return null;
        }

        $source = $this->sourceData($question->source_url);

        return $source ? $this->categoryIdFromSource($source) : null;
    }

    private function sourceData(string $url): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => config('app.name', 'AiWeetRaad') . ' content importer (+https://aiweetraad.nl)',
                'Accept' => 'text/html,application/xhtml+xml',
            ])->timeout(20)->get($url);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $response->body(), LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);

        $description = $this->metaContent($xpath, 'description')
            ?: $this->firstText($xpath, '//main//p');

        return [
            'heading' => $this->firstText($xpath, '//h1') ?: '',
            'description' => $description,
            'breadcrumbs' => $this->breadcrumbs($xpath),
            'tips' => $this->tips($xpath),
        ];
    }

    private function answerBody(Question $question, AiModel $model, array $source): string
    {
        $tips = collect($source['tips'])
            ->filter(fn (array $tip) => $this->isUsefulTip($tip))
            ->take(4)
            ->values();
        $topic = rtrim($question->title, '?');
        $description = $this->cleanDescription($source['description'] ?? null);
        $category = collect($source['breadcrumbs'])->slice(1)->first();

        if ($this->isHealthSource($source)) {
            return $this->healthAnswerBody($model, $topic, $category);
        }

        if ($tips->isEmpty()) {
            return implode("\n\n", [
                $this->opening($model->slug, $topic, $category),
                $description ?: 'De bronpagina bevatte geen tips die betrouwbaar genoeg waren om letterlijk over te nemen.',
                'Kies daarom een voorzichtige aanpak: begin mild, voorkom agressieve middelen en stop als de klacht of schade erger wordt.',
            ]);
        }

        $lines = $tips
            ->map(function (array $tip, int $index) use ($model) {
                $title = $this->normaliseTipTitle($tip['title']);
                $body = $this->normaliseTipBody($tip['body']);

                return match ($model->slug) {
                    'gemini' => ($index + 1) . '. ' . $title . ': ' . $this->shorten($body, 180),
                    'grok' => ($index + 1) . '. ' . $title . '. ' . $this->shorten($body, 160),
                    'deepseek' => ($index + 1) . '. Methode: ' . $title . '. Toepassing: ' . $this->shorten($body, 190),
                    default => ($index + 1) . '. ' . $title . "\n   " . $this->shorten($body, 190),
                };
            })
            ->implode("\n");

        $parts = [
            $this->opening($model->slug, $topic, $category),
            $description ?: 'Ik heb de bruikbare tips uit de bron opgeschoond en samengevat.',
            "Praktische aanpak:\n" . $lines,
        ];

        $parts[] = $this->closing($model->slug);

        return implode("\n\n", array_filter($parts));
    }

    private function opening(string $modelSlug, string $topic, ?string $category = null): string
    {
        $context = $category ? ' binnen ' . Str::lower($category) : '';

        return match ($modelSlug) {
            'claude' => 'Voor "' . $topic . '" is dit de praktische lijn op basis van de bruikbare bron-tips' . $context . '.',
            'gemini' => 'Kort antwoord voor "' . $topic . '": begin met een milde, logische stap en bouw rustig op.',
            'grok' => 'Voor "' . $topic . '": houd het simpel en gebruik eerst de minst ingrijpende optie.',
            'deepseek' => 'Analyse van "' . $topic . '": kies een methode op basis van oorzaak, plek en risico.',
            default => 'Voor "' . $topic . '" kun je deze opgeschoonde aanpak gebruiken.',
        };
    }

    private function closing(string $modelSlug): string
    {
        return match ($modelSlug) {
            'claude' => 'Test bij twijfel klein en kies de mildste aanpak die werkt.',
            'gemini' => 'Werkt de eerste tip niet, probeer dan pas de volgende optie.',
            'grok' => 'Niet forceren: werkt het niet, stop en kies een andere aanpak.',
            'deepseek' => 'Controleer na elke stap of het probleem echt minder wordt; zo voorkom je onnodige schade.',
            default => 'Controleer het resultaat voordat je opschaalt naar een zwaardere methode.',
        };
    }

    private function healthAnswerBody(AiModel $model, string $topic, ?string $category): string
    {
        $isEyeTopic = Str::contains(Str::lower($topic), ['oog', 'ogen', 'wallen']);

        if ($isEyeTopic) {
            $steps = [
                'Koel rustig: leg 10 tot 15 minuten een schoon, koud kompres op gesloten ogen. Gebruik geen ijs direct op de huid.',
                'Laat het oog met rust: niet wrijven en geen olie, citroensap, melk, zalf of andere huis-tuin-middelen in of rond het oog smeren.',
                'Kijk naar de oorzaak: na huilen, slecht slapen of milde irritatie trekt zwelling vaak vanzelf weg. Bij allergie kun je apotheekadvies vragen.',
                'Neem contact op met een arts bij pijn, slechter zien, pus, koorts, letsel, benauwdheid, snelle toename of zwelling aan één kant.',
            ];
        } else {
            $steps = [
                'Begin met de mildste stap: rust, voldoende drinken en vermijd middelen die de klacht kunnen verergeren.',
                'Gebruik geen agressieve of twijfelachtige huis-tuin-middelen op huid, slijmvliezen of open wondjes.',
                'Vraag apotheek- of huisartsadvies als je medicijnen gebruikt, zwanger bent, een kind behandelt of de klacht terugkomt.',
                'Bel een arts bij heftige pijn, benauwdheid, koorts, snelle verslechtering, neurologische klachten of klachten die niet verbeteren.',
            ];
        }

        return implode("\n\n", [
            $this->opening($model->slug, $topic, $category),
            'De bron bevat ervaringsreacties. Voor gezondheidsklachten toon ik daarom alleen veilige, algemene stappen en geen twijfelachtige tips.',
            "Veilige aanpak:\n" . collect($steps)
                ->map(fn (string $step, int $index) => ($index + 1) . '. ' . $step)
                ->implode("\n"),
            'Gebruik dit als praktische eerste check, niet als medische diagnose.',
        ]);
    }

    private function tips(DOMXPath $xpath): array
    {
        $tips = [];

        foreach ($xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " tip ")]') as $node) {
            $title = $this->firstText($xpath, './/h3', $node);
            $body = $this->firstText($xpath, './/div[contains(concat(" ", normalize-space(@class), " "), " break-words ")]', $node);

            $title = preg_replace('/^#\d+\.\s*/u', '', $title ?? '');
            $title = $this->cleanText((string) $title);
            $body = $this->cleanText((string) $body);

            if ($title && $body && ! str_contains(Str::lower($title), 'gerelateerde onderwerpen')) {
                $tips[] = [
                    'title' => $title,
                    'body' => $body,
                ];
            }
        }

        return $tips;
    }

    private function cleanDescription(?string $description): ?string
    {
        if (! $description) {
            return null;
        }

        $lower = Str::lower($description);
        $seoFragments = [
            'beste oplossing',
            'ervaringen met',
            'lees grootmoeders tips',
            'oma weet raad',
        ];

        foreach ($seoFragments as $fragment) {
            if (str_contains($lower, $fragment)) {
                return null;
            }
        }

        return $this->shorten(rtrim($description, '.'), 180);
    }

    private function isUsefulTip(array $tip): bool
    {
        $text = Str::lower(($tip['title'] ?? '') . ' ' . ($tip['body'] ?? ''));

        if (mb_strlen((string) ($tip['body'] ?? '')) < 35) {
            return false;
        }

        $reject = [
            'sperti',
            'aambeienzalf',
            'gewoon drogen met water',
            'citroensap',
            'amandelolie',
            'melk',
            'dak je oog',
            'wiord',
            'lijkt herboren',
        ];

        foreach ($reject as $term) {
            if (str_contains($text, $term)) {
                return false;
            }
        }

        return true;
    }

    private function isHealthSource(array $source): bool
    {
        return collect($source['breadcrumbs'] ?? [])
            ->map(fn (string $crumb) => Str::slug($crumb))
            ->contains('gezondheid');
    }

    private function normaliseTipTitle(string $title): string
    {
        $title = rtrim($this->cleanText($title) ?? '', '.');

        return Str::ucfirst($title);
    }

    private function normaliseTipBody(string $body): string
    {
        $body = $this->cleanText($body) ?? '';
        $body = preg_replace('/\s*!+\s*/u', '. ', $body) ?? $body;
        $body = preg_replace('/\s+/u', ' ', $body) ?? $body;

        return rtrim($body, '.');
    }

    private function breadcrumbs(DOMXPath $xpath): array
    {
        $items = [];

        foreach ($xpath->query('//ul[contains(@class, "breadcrumbs")]//*[@itemprop="name"]') as $node) {
            $text = $this->cleanText($node->textContent ?? '');
            if ($text) {
                $items[] = $text;
            }
        }

        return $items;
    }

    private function categoryIdFromSource(array $source): ?int
    {
        $breadcrumbs = collect($source['breadcrumbs'] ?? [])
            ->reject(fn (string $crumb) => Str::lower($crumb) === 'home')
            ->values();

        foreach ($breadcrumbs as $crumb) {
            $slug = $this->localCategorySlug($crumb);
            if ($slug && $id = Category::where('slug', $slug)->value('id')) {
                return $id;
            }
        }

        return null;
    }

    private function localCategorySlug(string $sourceCategory): ?string
    {
        $normalized = Str::slug(html_entity_decode($sourceCategory, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return match ($normalized) {
            'tuin', 'huis-en-tuin' => 'huis-en-tuin',
            'vlekken' => 'vlekken',
            'gezondheid' => 'gezondheid',
            'diversen' => 'diversen',
            'keuken' => 'keuken',
            'schoonmaken' => 'schoonmaken',
            'werk-en-inkomen' => 'werk-en-inkomen',
            'huisdieren' => 'huisdieren',
            'wassen-kledingonderhoud' => 'wassen-kledingonderhoud',
            'computers-elektronica' => 'computers-elektronica',
            'eten-en-drinken' => 'eten-en-drinken',
            'hobby' => 'hobby',
            'omas-oudste-trucjes' => 'omas-oudste-trucjes',
            'uiterlijk-verzorging' => 'uiterlijk-verzorging',
            'vervoer-auto' => 'vervoer-auto',
            'duurzaamheid' => 'duurzaamheid',
            default => null,
        };
    }

    private function metaContent(DOMXPath $xpath, string $name): ?string
    {
        $node = $xpath->query('//meta[@name="' . $name . '"]/@content')->item(0);

        return $node ? $this->cleanText($node->nodeValue) : null;
    }

    private function firstText(DOMXPath $xpath, string $query, ?\DOMNode $context = null): ?string
    {
        $node = $xpath->query($query, $context)->item(0);

        return $node ? $this->cleanText($node->textContent ?? '') : null;
    }

    private function cleanText(string $text): ?string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text));
        $text = trim((string) $text);

        return $text === '' ? null : $text;
    }

    private function shorten(string $text, int $limit): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text . '.';
        }

        return rtrim(mb_substr($text, 0, $limit), " \t\n\r\0\x0B.,;:") . '...';
    }
}
