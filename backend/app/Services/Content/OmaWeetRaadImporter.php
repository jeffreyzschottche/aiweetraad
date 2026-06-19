<?php

namespace App\Services\Content;

use App\Models\Category;
use App\Models\Question;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OmaWeetRaadImporter
{
    private const SOURCE_NAME = 'omaweetraad.nl';

    private array $skipTerms = [
        'actie',
        'adverteren',
        'accepteren',
        'categorieen',
        'categorieën',
        'contact',
        'cookies',
        'disclaimer',
        'inloggen',
        'nieuwsbrief',
        'nieuwe tip',
        'over oma',
        'oma\'s aan de top',
        'privacy statement',
        'registreren',
        'tip toevoegen',
        'zoeken',
    ];

    public function import(int $limit = 100, bool $dryRun = false, string $sourceUrl = 'https://omaweetraad.nl/', int $maxPages = 10, bool $validateSourcePages = false): array
    {
        $limit = max(1, $limit);
        $maxPages = max(1, $maxPages);
        $normalizedSourceUrl = $this->normalizeUrl($sourceUrl);
        $expectedCategorySlug = $this->categorySlugFromUrl($normalizedSourceUrl);
        $queue = [$normalizedSourceUrl];
        $visited = [];
        $seenHashes = [];
        $created = [];
        $skipped = 0;

        while ($queue !== [] && count($visited) < $maxPages && count($created) < $limit) {
            $url = array_shift($queue);
            if (! $url || isset($visited[$url])) {
                continue;
            }

            $visited[$url] = true;
            $html = $this->fetch($url);
            if ($html === null) {
                continue;
            }

            $document = $this->document($html);
            $links = $this->extractLinks($document, $url);

            foreach ($links as $link) {
                if (count($created) >= $limit) {
                    break 2;
                }

                if (count($queue) + count($visited) < $maxPages && ! isset($visited[$link['url']])) {
                    $queue[] = $link['url'];
                }

                if (! $link['has_tip_count']) {
                    $skipped++;
                    continue;
                }

                $questionTitle = $this->toQuestionTitle($link['text']);
                if ($questionTitle === null) {
                    $skipped++;
                    continue;
                }

                if ($validateSourcePages && ! $this->hasImportableSourcePage($link['url'])) {
                    $skipped++;
                    continue;
                }

                $sourcePage = $validateSourcePages ? $this->sourcePageData($link['url']) : null;
                if ($validateSourcePages && $sourcePage === null) {
                    $skipped++;
                    continue;
                }

                $sourceCategorySlug = $sourcePage ? $this->categorySlugFromBreadcrumbs($sourcePage['breadcrumbs']) : null;
                if ($expectedCategorySlug && $sourceCategorySlug && $sourceCategorySlug !== $expectedCategorySlug) {
                    $skipped++;
                    continue;
                }

                $categoryId = $sourcePage
                    ? $this->categoryIdFromBreadcrumbs($sourcePage['breadcrumbs'], create: ! $dryRun)
                    : null;

                $hash = sha1($link['url'] . '|' . Str::lower($questionTitle));
                if (isset($seenHashes[$hash]) || Question::where('source_hash', $hash)->exists()) {
                    $skipped++;
                    continue;
                }

                $seenHashes[$hash] = true;
                $payload = [
                    'title' => $questionTitle,
                    'slug' => Question::makeUniqueSlug($questionTitle),
                    'body' => $this->questionBody($questionTitle, $sourcePage),
                    'category_id' => $categoryId ?: $this->categoryIdFor($link['text'], $link['url'], $url),
                    'status' => 'published',
                    'answers_generated' => false,
                    'source_name' => self::SOURCE_NAME,
                    'source_url' => $link['url'],
                    'source_hash' => $hash,
                    'source_imported_at' => now(),
                ];

                $created[] = $dryRun ? $payload : Question::create($payload);
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'visited_pages' => array_keys($visited),
        ];
    }

    private function fetch(string $url): ?string
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

        return $response->body();
    }

    private function hasImportableSourcePage(string $url): bool
    {
        return $this->sourcePageData($url) !== null;
    }

    private function sourcePageData(string $url): ?array
    {
        $html = $this->fetch($url);
        if ($html === null) {
            return null;
        }

        $document = $this->document($html);
        $xpath = new DOMXPath($document);
        $breadcrumbs = $this->breadcrumbs($xpath);
        $hasTips = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " tip ")]')->length > 0;
        $hasDescription = $xpath->query('//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz") = "description"]/@content')->length > 0;

        if (! $hasTips && ! $hasDescription && $breadcrumbs === []) {
            return null;
        }

        return [
            'breadcrumbs' => $breadcrumbs,
            'intro' => $this->pageIntro($xpath),
            'description' => $this->pageDescription($xpath),
        ];
    }

    public function publicQuestionBody(string $title, ?string $sourceUrl = null): string
    {
        $sourcePage = $sourceUrl ? $this->sourcePageData($sourceUrl) : null;

        return $this->questionBody($title, $sourcePage);
    }

    private function pageDescription(DOMXPath $xpath): ?string
    {
        $node = $xpath->query('//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz") = "description"]/@content')->item(0);

        return $node ? $this->cleanPublicDescription((string) $node->nodeValue) : null;
    }

    private function pageIntro(DOMXPath $xpath): ?string
    {
        foreach ($xpath->query('//h1/following::p[position() <= 3]') as $node) {
            $text = $this->cleanPublicDescription((string) $node->textContent);
            if ($text) {
                return $text;
            }
        }

        return null;
    }

    private function cleanPublicDescription(?string $description): ?string
    {
        if (! $description) {
            return null;
        }

        $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = preg_replace('/\s+/u', ' ', trim($description));
        $lower = Str::lower($description ?? '');
        $seoFragments = [
            'beste oplossing',
            'ervaringen met',
            'lees grootmoeders tips',
            'oma weet raadt',
            'alle oma',
            'stuur ons je tip',
            'gouden randje',
        ];

        foreach ($seoFragments as $fragment) {
            if (str_contains($lower, $fragment)) {
                return null;
            }
        }

        $description = preg_replace('/\b(Oma Weet Raad|omaweetraad\.nl)\b\.?/iu', '', $description ?? '');
        $description = preg_replace('/\btips\s+van\s+Oma\b/iu', 'tips', $description ?? '');
        $description = preg_replace('/\bvan\s+grootmoeder\b/iu', '', $description ?? '');
        $description = preg_replace('/\bLees\s+grootmoeders\s+tips\.?/iu', '', $description ?? '');
        $description = preg_replace('/\bBekijk\s+alle\s+tips\.?/iu', '', $description ?? '');
        $description = trim((string) preg_replace('/\s+/u', ' ', $description ?? ''));
        $description = trim($description, " \t\n\r\0\x0B-–—.|");

        if (mb_strlen($description) < 30) {
            return null;
        }

        return rtrim($description, '.') . '.';
    }

    private function questionBody(string $questionTitle, ?array $sourcePage = null): string
    {
        $intro = $this->cleanPublicDescription($sourcePage['intro'] ?? null);
        if ($intro) {
            return $intro;
        }

        $description = $this->cleanPublicDescription($sourcePage['description'] ?? null);
        if ($description) {
            return $description;
        }

        return 'Praktische vraag over ' . Str::lower(rtrim($questionTitle, '?')) . ', met aandacht voor veilige en haalbare oplossingen.';
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

    private function document(string $html): DOMDocument
    {
        $document = new DOMDocument();

        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        return $document;
    }

    private function extractLinks(DOMDocument $document, string $baseUrl): array
    {
        $xpath = new DOMXPath($document);
        $links = [];

        foreach ($xpath->query('//a[@href]') as $node) {
            $rawText = $node->getAttribute('title') ?: ($node->textContent ?? '');
            $hasTipCount = preg_match('/\b\d+\s+(tips?|reacties?)\b/iu', $rawText) === 1;
            $text = $this->cleanText($rawText);
            $url = $this->normalizeUrl($node->getAttribute('href'), $baseUrl);

            if ($text === null || $url === null || ! str_starts_with($url, 'https://omaweetraad.nl/')) {
                continue;
            }

            $links[$url . '|' . Str::lower($text)] = [
                'text' => $text,
                'url' => $url,
                'has_tip_count' => $hasTipCount,
            ];
        }

        return array_values($links);
    }

    private function cleanText(string $text): ?string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text));
        $text = preg_replace('/^\d+\.\s*/u', '', $text ?? '');
        $text = preg_replace('/\s+\d+\s+(tips?|reacties?)$/iu', '', $text ?? '');
        $text = trim((string) $text);

        if (mb_strlen($text) < 4 || mb_strlen($text) > 120) {
            return null;
        }

        $lower = Str::lower($text);
        foreach ($this->skipTerms as $term) {
            if ($lower === $term || str_contains($lower, $term)) {
                return null;
            }
        }

        return $text;
    }

    private function toQuestionTitle(string $text): ?string
    {
        $text = $this->cleanText($text);
        if ($text === null) {
            return null;
        }

        $text = rtrim($text, '.?!');
        if ($this->isCategoryLabel($text)) {
            return null;
        }

        $lower = Str::lower($text);

        if (preg_match('/^(hoe|wat|waarom|wanneer|welke|waar|kan|kun|is|zijn)\b/iu', $text)) {
            return Str::ucfirst($text) . '?';
        }

        if (preg_match('/\b(verwijderen|bestrijden|behandelen|schoonmaken|stoppen|oplossen|voorkomen)$/iu', $lower)) {
            return 'Hoe kan ik ' . $lower . '?';
        }

        if (preg_match('/^(.*)\b(tegen|bij)\b(.*)$/iu', $lower)) {
            return 'Wat helpt ' . $lower . '?';
        }

        return 'Wat helpt bij ' . $lower . '?';
    }

    public function inferCategoryId(string $text, ?string $sourceUrl = null, ?string $contextUrl = null): ?int
    {
        return $this->categoryIdFor($text, $sourceUrl, $contextUrl);
    }

    private function categoryIdFor(string $text, ?string $sourceUrl = null, ?string $contextUrl = null): ?int
    {
        $haystack = Str::lower(implode(' ', array_filter([
            $text,
            $this->urlPathWords($sourceUrl),
            $this->urlPathWords($contextUrl),
        ])));

        $category = Category::query()
            ->get(['id', 'name', 'slug'])
            ->first(function (Category $category) use ($haystack) {
                return str_contains($haystack, Str::lower($category->name))
                    || str_contains($haystack, Str::lower(str_replace('-', ' ', $category->slug)));
            });

        if ($category) {
            return $category->id;
        }

        foreach ($this->categoryKeywords() as $categorySlug => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, $keyword)) {
                    return Category::where('slug', $categorySlug)->value('id');
                }
            }
        }

        return Category::where('slug', 'diversen')->value('id');
    }

    private function categoryIdFromBreadcrumbs(array $breadcrumbs, bool $create = true): ?int
    {
        foreach ($breadcrumbs as $crumb) {
            if (Str::lower($crumb) === 'home') {
                continue;
            }

            $mappedSlug = $this->localCategorySlug($crumb);
            if ($mappedSlug && $id = Category::where('slug', $mappedSlug)->value('id')) {
                return $id;
            }

            if (! $create) {
                continue;
            }

            $name = html_entity_decode($crumb, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $slug = $mappedSlug ?: Str::slug($name);
            if ($slug === '') {
                continue;
            }

            $defaults = $this->categoryDefaults($slug, $name);

            return Category::firstOrCreate(
                ['slug' => $slug],
                $defaults + ['sort_order' => (int) Category::max('sort_order') + 1],
            )->id;
        }

        return null;
    }

    private function categoryDefaults(string $slug, string $sourceName): array
    {
        if ($slug === 'ai-trucjes') {
            return [
                'name' => 'AI-trucjes',
                'description' => 'Slimme huis-, tuin- en keukenoplossingen in een AI-jasje.',
                'icon' => '✨',
                'color' => '#b45309',
            ];
        }

        return [
            'name' => Str::headline($sourceName),
            'description' => 'Praktische vragen en oplossingen rond dit onderwerp.',
            'icon' => '💡',
            'color' => '#6366f1',
        ];
    }

    private function categorySlugFromBreadcrumbs(array $breadcrumbs): ?string
    {
        foreach ($breadcrumbs as $crumb) {
            if (Str::lower($crumb) === 'home') {
                continue;
            }

            return $this->localCategorySlug($crumb) ?: Str::slug($crumb);
        }

        return null;
    }

    private function categorySlugFromUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($path === '') {
            return null;
        }

        $firstSegment = Str::before($path, '/');

        return $this->localCategorySlug($firstSegment);
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
            'omas-oudste-trucjes' => 'ai-trucjes',
            'ai-trucjes' => 'ai-trucjes',
            'uiterlijk-verzorging' => 'uiterlijk-verzorging',
            'vervoer-auto' => 'vervoer-auto',
            'duurzaamheid' => 'duurzaamheid',
            default => null,
        };
    }

    private function urlPathWords(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($path === '') {
            return null;
        }

        return str_replace(['-', '/'], ' ', $path);
    }

    private function categoryKeywords(): array
    {
        return [
            'gezondheid' => [
                'aambeien', 'aften', 'afvallen', 'bartholin', 'blaasontsteking', 'boeren', 'borstontsteking',
                'brandend maagzuur', 'buikpijn', 'buikkrampen', 'cyste', 'diarree', 'droge keel', 'droge mond',
                'ganglion', 'griep', 'hik', 'hooikoorts', 'hoest', 'intimiteit', 'kaak', 'kater',
                'keel', 'koortslip', 'kriebelhoest', 'kraaltje in neus', 'leefstijl', 'maag', 'menstruatie', 'migraine',
                'misselijk', 'oorontsteking', 'oorpijn', 'oorsuizen', 'overgeven', 'pijn', 'processierups', 'psoriasis',
                'raynaud', 'reflux', 'ringworm', 'schaafwond', 'schurft', 'schimmelinfectie',
                'stomen', 'talgklieren', 'verkoud', 'verslikken', 'verstopt oor', 'verstopte neus', 'wormen', 'wratten', 'zweren',
            ],
            'vlekken' => ['vlek', 'bloedvlek'],
            'schoonmaken' => ['gootsteen', 'ontkalken', 'schoonmaak', 'verstopte wc', 'wc'],
            'huis-en-tuin' => [
                'draaigatje', 'heermoes', 'kauwen', 'klussen', 'meeldauw', 'mieren', 'ongedierte',
                'oorwormen', 'planten', 'repareren', 'schutting', 'tuin', 'vliegende mieren',
                'waterslag',
            ],
            'huisdieren' => ['hond', 'kat', 'kattenharen', 'vlooien'],
            'eten-en-drinken' => ['bereiden', 'bewaren', 'dieet', 'opwarmen'],
            'hobby' => ['instrumenten', 'muziek'],
            'uiterlijk-verzorging' => [
                'glimmende neus', 'haar', 'handen', 'huid', 'jeuk', 'kalknagels', 'make up', 'nagels', 'rode adertjes',
                'rimpels', 'schimmelnagels', 'schrale lippen',
            ],
            'duurzaamheid' => ['besparen', 'hergebruik'],
        ];
    }

    private function isCategoryLabel(string $text): bool
    {
        $lower = Str::lower($text);

        return Category::query()
            ->get(['name', 'slug'])
            ->contains(function (Category $category) use ($lower) {
                return $lower === Str::lower($category->name)
                    || $lower === Str::lower(str_replace('-', ' ', $category->slug));
            });
    }

    private function normalizeUrl(string $url, ?string $baseUrl = null): ?string
    {
        $url = trim($url);
        if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')) {
            return null;
        }

        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        } elseif (str_starts_with($url, '/')) {
            $url = 'https://omaweetraad.nl' . $url;
        } elseif (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $base = $baseUrl ? rtrim(dirname($baseUrl), '/') : 'https://omaweetraad.nl';
            $url = $base . '/' . ltrim($url, '/');
        }

        $parts = parse_url($url);
        if (($parts['host'] ?? '') !== 'omaweetraad.nl') {
            return null;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return 'https://omaweetraad.nl' . $path . $query;
    }
}
