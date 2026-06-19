<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\AnswerVote;
use App\Models\Question;
use App\Models\User;
use App\Services\AI\AnswerGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class QuestionSeeder extends Seeder
{
    /**
     * A small set of generic, everyday demo questions so the site is populated.
     * The category slugs match CategorySeeder. Answers are generated (and
     * cached) via the AnswerGenerator using whatever driver is configured.
     */
    private array $questions = [
        'gezondheid' => [
            'Hoe kom ik snel van de hik af?',
            'Wat helpt tegen een droge keel in de winter?',
            'Hoe val ik makkelijker in slaap?',
            'Wanneer moet ik met hoofdpijn naar de huisarts?',
        ],
        'schoonmaken' => [
            'Hoe maak ik kalkaanslag op de kraan weg?',
            'Wat is de beste manier om ramen streeploos te wassen?',
            'Hoe krijg ik een muffe geur uit de koelkast?',
            'Hoe maak ik voegen in de badkamer weer schoon?',
        ],
        'eten-en-drinken' => [
            'Hoe bewaar ik verse kruiden langer?',
            'Hoe voorkom ik dat rijst aan elkaar plakt?',
            'Hoe weet ik of eieren nog goed zijn?',
            'Hoe red ik een soep die te zout is geworden?',
        ],
        'keuken' => [
            'Hoe ontkalk ik mijn waterkoker?',
            'Hoe slijp ik een bot keukenmes thuis?',
            'Hoe krijg ik aangebrande resten uit een pan?',
            'Hoe maak ik een snijplank weer fris?',
        ],
        'huis-en-tuin' => [
            'Hoe houd ik slakken weg uit mijn moestuin?',
            'Wanneer kan ik mijn gras het beste maaien?',
            'Hoe krijg ik groene aanslag van mijn tegels?',
            'Hoe geef ik kamerplanten water zonder wortelrot?',
        ],
        'huisdieren' => [
            'Hoe leer ik mijn kat van het aanrecht af te blijven?',
            'Hoe voorkom ik dat mijn hond in de tuin graaft?',
            'Wat kan ik doen als mijn hond bang is voor vuurwerk?',
            'Hoe laat ik mijn kat meer water drinken?',
        ],
        'uiterlijk-verzorging' => [
            'Wat helpt tegen droge handen in de winter?',
            'Hoe krijg ik statisch haar onder controle?',
            'Hoe voorkom ik scheerirritatie?',
            'Wat helpt tegen droge lippen?',
        ],
        'vlekken' => [
            'Hoe verwijder ik een rodewijnvlek uit een tafelkleed?',
            'Hoe krijg ik een grasvlek uit een spijkerbroek?',
            'Hoe haal ik koffievlekken uit een wit overhemd?',
            'Hoe verwijder ik vetvlekken uit een T-shirt?',
        ],
        'wassen-kledingonderhoud' => [
            'Hoe was ik een wollen trui zonder dat hij krimpt?',
            'Hoe krijg ik mijn witte was weer echt wit?',
            'Hoe voorkom ik dat zwarte kleding vaal wordt?',
            'Hoe haal ik zweetgeur uit sportkleding?',
        ],
        'computers-elektronica' => [
            'Hoe maak ik mijn laptop weer sneller?',
            'Hoe maak ik een schermafbeelding op mijn telefoon?',
            'Hoe verleng ik de batterijduur van mijn telefoon?',
            'Wat kan ik doen als mijn wifi steeds wegvalt?',
        ],
        'vervoer-auto' => [
            'Hoe verwijder ik ijs van mijn voorruit zonder krassen?',
            'Hoe vaak moet ik mijn bandenspanning controleren?',
            'Hoe krijg ik een muffe geur uit mijn auto?',
            'Wat moet ik checken voor een lange autorit?',
        ],
        'werk-en-inkomen' => [
            'Hoe schrijf ik een goede sollicitatiebrief?',
            'Hoe houd ik mijn administratie overzichtelijk?',
            'Hoe vraag ik netjes om salarisverhoging?',
            'Hoe maak ik een simpel maandbudget?',
        ],
        'hobby' => [
            'Hoe begin ik met aquarelleren als beginner?',
            'Hoe bewaar ik mijn breiwerk netjes?',
            'Hoe voorkom ik luchtbellen bij epoxy?',
            'Hoe organiseer ik mijn hobbyspullen zonder veel ruimte?',
        ],
        'ai-trucjes' => [
            'Welke oude truc helpt tegen zilver dat zwart is geworden?',
            'Hoe laat ik mijn huis snel fris ruiken zonder parfum?',
            'Hoe maak ik een verstopte afvoer op een milde manier vrij?',
            'Welke ouderwetse truc helpt tegen fruitvliegjes?',
        ],
        'duurzaamheid' => [
            'Hoe bespaar ik energie in huis zonder grote investering?',
            'Hoe scheid ik mijn afval het beste?',
            'Hoe gebruik ik minder water onder de douche?',
            'Hoe begin ik met spullen repareren in plaats van weggooien?',
        ],
        'diversen' => [
            'Hoe vouw ik een fitted hoeslaken netjes op?',
            'Hoe verwijder ik een vastzittende ritssluiting?',
            'Hoe krijg ik plakresten van een potje af?',
            'Hoe pak ik een koffer slim in voor een weekend weg?',
        ],
    ];

    public function run(): void
    {
        config(['ai.generation_enabled' => false]);

        $generator = app(AnswerGenerator::class);

        foreach ($this->questions as $categorySlug => $titles) {
            $category = Category::where('slug', $categorySlug)->first();
            if (! $category) {
                continue;
            }

            foreach ($titles as $title) {
                $slug = Str::slug($title) ?: 'vraag';
                $question = Question::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'title' => $title,
                        'category_id' => $category->id,
                        'user_id' => $this->demoUserId($title),
                        'body' => $this->questionBody($categorySlug, $title),
                        'status' => 'published',
                        'views' => random_int(20, 800),
                    ]
                );

                $generator->generateForQuestion($question, force: true);
                $this->applyDemoAnswers($question);
                $this->seedVotes($question);
            }
        }
    }

    private function questionBody(string $categorySlug, string $title): string
    {
        $context = [
            'gezondheid' => 'Ik zoek een veilige eerste aanpak voor een alledaagse klacht en wil weten wanneer ik beter hulp kan inschakelen.',
            'schoonmaken' => 'Ik wil dit thuis oplossen met normale schoonmaakmiddelen en zonder oppervlakken te beschadigen.',
            'eten-en-drinken' => 'Ik zoek een praktische keukentip die veilig is en makkelijk te controleren.',
            'keuken' => 'Ik wil dit apparaat of keukengerei schoon, veilig en bruikbaar houden zonder dure spullen.',
            'huis-en-tuin' => 'Ik zoek een aanpak die werkt in en om het huis, liefst zonder schade aan planten, tegels of dieren.',
            'huisdieren' => 'Ik wil dit op een diervriendelijke manier oplossen zonder straf of stress.',
            'uiterlijk-verzorging' => 'Ik zoek een milde routine of oplossing die ik thuis verantwoord kan proberen.',
            'vlekken' => 'De vlek is recent en ik wil voorkomen dat hij permanent in de stof trekt.',
            'wassen-kledingonderhoud' => 'Ik wil de kleding mooi houden en voorkomen dat kleur, pasvorm of stof beschadigt.',
            'computers-elektronica' => 'Ik wil eerst simpele checks doen voordat ik ga resetten of iets vervang.',
            'vervoer-auto' => 'Ik zoek een veilige, praktische aanpak die geen schade aan auto, lak, ruiten of onderdelen geeft.',
            'werk-en-inkomen' => 'Ik wil dit overzichtelijk aanpakken met concrete stappen en zonder belangrijke details te missen.',
            'hobby' => 'Ik ben geen expert en wil klein beginnen met materiaal dat betaalbaar en overzichtelijk blijft.',
            'ai-trucjes' => 'Ik wil weten of een slimme huis-tuin-en-keukenmethode veilig en zinvol is.',
            'duurzaamheid' => 'Ik wil een haalbare duurzame stap die weinig kost en in het dagelijks leven vol te houden is.',
            'diversen' => 'Ik zoek een simpele oplossing die ik meteen thuis kan proberen.',
        ];

        return ($context[$categorySlug] ?? $context['diversen'])
            . ' Vraag: ' . lcfirst(rtrim($title, '?')) . '.';
    }

    private function demoUserId(string $title): ?int
    {
        $demoTitles = [
            'Hoe verwijder ik een rodewijnvlek uit een tafelkleed?',
            'Hoe maak ik kalkaanslag op de kraan weg?',
            'Hoe bespaar ik energie in huis zonder grote investering?',
        ];

        if (! in_array($title, $demoTitles, true)) {
            return null;
        }

        return User::where('email', 'test@example.com')->value('id');
    }

    /**
     * Give the seeded answers some plausible like/dislike counts so the
     * leaderboard and sorting have something to show.
     */
    private function seedVotes(Question $question): void
    {
        foreach ($question->answers as $answer) {
            $up = random_int(0, 40);
            $down = random_int(0, 8);
            $answer->update(['upvotes' => $up, 'downvotes' => $down]);
        }

        $user = User::where('email', 'test@example.com')->first();
        if (! $user || ! $question->user_id) {
            return;
        }

        $answer = $question->answers()
            ->whereHas('aiModel', fn ($query) => $query->whereIn('slug', ['claude', 'openai', 'gemini', 'grok', 'deepseek']))
            ->inRandomOrder()
            ->first();

        if (! $answer) {
            return;
        }

        AnswerVote::updateOrCreate(
            ['answer_id' => $answer->id, 'voter_key' => 'u:' . $user->id],
            ['value' => 1]
        );
        $answer->increment('upvotes');
    }

    private function applyDemoAnswers(Question $question): void
    {
        $examples = [
            'hoe-verwijder-ik-een-rodewijnvlek-uit-een-tafelkleed' => [
                'claude' => "Dep eerst, wrijf niet: zo duw je de wijn niet dieper in de vezel.\n\n1. Strooi direct ruim zout of baking soda op de natte plek.\n2. Laat dit 10 minuten intrekken en zuig of klop het weg.\n3. Spoel de achterkant van de stof met koud water.\n4. Was daarna volgens het waslabel met een beetje vloeibaar wasmiddel op de vlek.\n\nGebruik geen heet water voordat de vlek weg is; warmte kan rode wijn juist fixeren.",
                'openai' => "Snel handelen werkt hier het best.\n\n1. Leg keukenpapier onder de vlek en dep de bovenkant droog.\n2. Giet voorzichtig bruiswater of koud water door de vlek heen.\n3. Breng een druppel afwasmiddel met koud water aan.\n4. Spoel uit en was het tafelkleed normaal.\n\nCheck de vlek voordat het kleed de droger in gaat. Is hij nog zichtbaar, herhaal dan eerst de behandeling.",
            ],
            'hoe-maak-ik-kalkaanslag-op-de-kraan-weg' => [
                'gemini' => "Maak een doek nat met natuurazijn en wikkel die om de kraan.\n\nLaat dit 20 tot 30 minuten zitten. Haal de doek weg, borstel randjes voorzichtig met een oude tandenborstel en spoel goed na. Droog daarna met een zachte doek, want juist opdrogende waterdruppels maken nieuwe kalkvlekken.",
                'grok' => "Azijn, doek, geduld. Meer is meestal niet nodig.\n\nWikkel een met azijn natgemaakte doek om de kalkplek, wacht een half uur, poets los met een tandenborstel en spoel na. Niet gebruiken op natuursteen of kwetsbare coatings; test bij twijfel eerst op een klein stukje.",
            ],
            'hoe-bespaar-ik-energie-in-huis-zonder-grote-investering' => [
                'openai' => "Begin met de maatregelen die niets of weinig kosten.\n\n1. Zet de verwarming 1 graad lager en verwarm alleen kamers die je gebruikt.\n2. Plaats tochtstrips bij deuren en ramen.\n3. Gebruik radiatorfolie achter radiatoren tegen buitenmuren.\n4. Was vaker op 30 graden en laat was drogen aan een rek.\n5. Zet apparaten volledig uit in plaats van standby.\n\nSamen leveren deze kleine stappen vaak al merkbaar verschil op.",
                'claude' => "Kijk vooral naar warmteverlies en sluipverbruik. Dat zijn de plekken waar je zonder verbouwing winst pakt.\n\nSluit gordijnen zodra het donker wordt, houd radiatoren vrij, ontlucht ze elk seizoen en gebruik een tijdschema voor verwarming. Controleer daarnaast opladers, gameconsoles en oude apparatuur op standby-verbruik.",
            ],
        ];

        if (! isset($examples[$question->slug])) {
            return;
        }

        $question->loadMissing('answers.aiModel');

        foreach ($question->answers as $answer) {
            $slug = $answer->aiModel?->slug;
            if ($slug && isset($examples[$question->slug][$slug])) {
                $answer->update(['body' => $examples[$question->slug][$slug]]);
            }
        }
    }
}
