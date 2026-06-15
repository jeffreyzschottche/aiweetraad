<?php

namespace App\Services\AI;

use App\Models\AiModel;
use App\Models\Question;

/**
 * Generates plausible, persona-flavoured answers without calling any external
 * API. Used when AI generation is disabled or for the `stub` provider so the
 * site is fully populated and demoable offline / for free.
 */
class StubDriver implements AnswerDriver
{
    public function generate(Question $question, AiModel $model): string
    {
        $question->loadMissing('category');

        $topic = rtrim($question->title, '?');
        $categorySlug = $question->category?->slug ?? 'diversen';
        $persona = $this->persona($model->slug);
        $steps = $this->steps($question, $model, $categorySlug);

        $intro = str_replace('{topic}', $topic, $persona['intro']);
        $outro = $persona['outro'];
        $focus = $this->categoryFocus($categorySlug);

        $list = collect($steps)
            ->map(fn ($s, $i) => ($i + 1) . '. ' . $s)
            ->implode("\n");

        return $intro . "\n\n" . $focus . "\n\n" . $list . "\n\n" . $outro;
    }

    private function persona(string $slug): array
    {
        $personas = [
            'claude' => [
                'intro' => 'Goede vraag. Laten we "{topic}" rustig aanpakken, met aandacht voor veiligheid en wat praktisch haalbaar is.',
                'outro' => 'Werkt dit niet meteen, kies dan de mildste vervolgstap en forceer niets. Bij twijfel is stoppen vaak verstandiger dan harder proberen.',
            ],
            'openai' => [
                'intro' => 'Hier is een heldere aanpak voor "{topic}". Ik heb het opgesplitst zodat je het direct kunt volgen.',
                'outro' => 'Tip: noteer wat wel en niet werkte. Dan kun je een volgende poging gerichter doen in plaats van opnieuw te gokken.',
            ],
            'gemini' => [
                'intro' => 'Voor "{topic}" zou ik vooral kijken naar de snelste route én een veilig alternatief als dat niet werkt.',
                'outro' => 'Lukt het zo niet? Vergelijk dan de oorzaak met een vergelijkbaar probleem in dezelfde categorie; vaak wijst dat naar de juiste vervolgstap.',
            ],
            'grok' => [
                'intro' => 'Kort en eerlijk: voor "{topic}" wil je vooral geen ingewikkeld gedoe.',
                'outro' => 'Werkt dit niet, dan is de oorzaak waarschijnlijk net anders dan hij lijkt. Pas dan één ding tegelijk aan.',
            ],
            'deepseek' => [
                'intro' => 'Laten we "{topic}" logisch ontleden en de meest waarschijnlijke aanpak kiezen.',
                'outro' => 'Controleer na elke stap of het resultaat verbetert; zo voorkom je onnodig proberen.',
            ],
        ];

        return $personas[$slug] ?? $personas['claude'];
    }

    private function categoryFocus(string $categorySlug): string
    {
        return match ($categorySlug) {
            'gezondheid' => 'Let op signalen die om professionele hulp vragen: heftige pijn, benauwdheid, koorts die aanhoudt of klachten die snel erger worden.',
            'vlekken', 'wassen-kledingonderhoud' => 'Belangrijk: test altijd eerst op een onopvallende plek en gebruik geen hitte zolang je niet zeker weet dat de vlek weg is.',
            'huisdieren' => 'Kijk ook naar stress, verveling en routine. Bij plots ander gedrag of pijnsignalen is een dierenarts de veiligste route.',
            'computers-elektronica' => 'Maak eerst een back-up of noteer instellingen voordat je iets verwijdert, reset of loskoppelt.',
            'werk-en-inkomen' => 'Houd bewijs, data en bedragen netjes bij. Dat voorkomt later discussie of dubbel werk.',
            default => 'Begin klein, controleer tussendoor en schaal pas op als de eerste stap geen risico of schade geeft.',
        };
    }

    private function steps(Question $question, AiModel $model, string $categorySlug): array
    {
        $pool = $this->categorySteps($categorySlug);

        $seed = crc32($question->slug . ':' . $model->slug);
        $count = 4 + ($seed % 3); // 4–6 steps

        $rotated = array_merge(
            array_slice($pool, $seed % count($pool)),
            array_slice($pool, 0, $seed % count($pool))
        );

        return array_slice($rotated, 0, $count);
    }

    private function categorySteps(string $categorySlug): array
    {
        $steps = [
            'gezondheid' => [
                'Drink wat water en kijk of rust, warmte of frisse lucht de klacht binnen korte tijd vermindert.',
                'Vermijd meteen zware middelen; begin met slaap, hydratatie en prikkelvermindering.',
                'Noteer wanneer de klacht begon, wat je hebt gegeten of gedaan en of er koorts of pijn bij zit.',
                'Gebruik alleen middelen volgens de bijsluiter en combineer geen medicijnen zonder het te checken.',
                'Bel de huisarts bij aanhoudende, terugkerende of snel erger wordende klachten.',
                'Bij acute benauwdheid, hevige pijn op de borst of neurologische klachten bel je direct spoedhulp.',
            ],
            'schoonmaken' => [
                'Haal los vuil eerst weg met een droge doek, stofzuiger of zachte borstel.',
                'Begin met lauw water en een mild schoonmaakmiddel voordat je agressievere middelen gebruikt.',
                'Laat een middel kort inwerken, maar laat het niet opdrogen op kwetsbare oppervlakken.',
                'Werk van schoon naar vuil en spoel je doek regelmatig uit.',
                'Droog na met een schone doek om strepen, kalkrandjes of nieuwe geurtjes te voorkomen.',
                'Meng nooit bleek, ammoniak, azijn of ontkalker met elkaar.',
            ],
            'vlekken' => [
                'Dep de vlek van buiten naar binnen; wrijven duwt vuil dieper in de vezel.',
                'Leg keukenpapier of een doek onder de stof zodat de vlek kan doorslaan.',
                'Gebruik koud of lauw water bij eiwitrijke vlekken en vermijd heet water tot de vlek weg is.',
                'Breng een klein beetje geschikt middel aan en laat het rustig inwerken.',
                'Spoel goed uit en was daarna volgens het waslabel.',
                'Controleer de vlek voordat het item de droger in gaat.',
            ],
            'keuken' => [
                'Koppel apparaten los of zet warmtebronnen uit voordat je schoonmaakt of onderhoud doet.',
                'Verwijder losse resten en werk met een zachte spons of borstel.',
                'Gebruik natuurazijn of citroenzuur alleen op materialen die daartegen kunnen.',
                'Spoel alles wat met eten in aanraking komt grondig na.',
                'Laat onderdelen volledig drogen voordat je ze terugplaatst.',
                'Plan klein onderhoud regelmatig, dan voorkom je hardnekkige aanslag of slijtage.',
            ],
            'huis-en-tuin' => [
                'Bekijk eerst de plek: zon, vocht, bodem, schaduw en gebruik bepalen meestal de oplossing.',
                'Begin met handmatig weghalen, snoeien of schoonmaken voordat je middelen gebruikt.',
                'Werk op een droog moment als regen of wind het resultaat kan verstoren.',
                'Bescherm planten, dieren en afvoerputten wanneer je schoonmaak- of bestrijdingsmiddelen gebruikt.',
                'Herhaal liever een milde behandeling dan één zware ingreep.',
                'Controleer na een week of de oorzaak echt minder is geworden.',
            ],
            'huisdieren' => [
                'Kijk wanneer het gedrag ontstaat: na eten, bij bezoek, door verveling of op vaste plekken.',
                'Beloon gewenst gedrag direct en maak ongewenst gedrag zo onaantrekkelijk mogelijk.',
                'Zorg voor beweging, verrijking en een voorspelbare routine.',
                'Gebruik geen straf die angst of pijn veroorzaakt; dat maakt gedrag vaak erger.',
                'Maak plekken goed schoon als geur of gewoonte een rol speelt.',
                'Neem contact op met een dierenarts of gedragstherapeut bij plots gedrag of stresssignalen.',
            ],
            'wassen-kledingonderhoud' => [
                'Lees het waslabel en sorteer op kleur, materiaal en vervuiling.',
                'Behandel vlekken vooraf met een mild middel dat past bij de stof.',
                'Gebruik niet te veel wasmiddel; resten maken textiel juist grauw of stug.',
                'Kies een lage temperatuur voor kwetsbare kleding en centrifugeer voorzichtig.',
                'Laat wol, zijde en delicate items plat of aan de lucht drogen.',
                'Bewaar kleding droog, schoon en met genoeg ruimte tegen kreuk en geur.',
            ],
            'computers-elektronica' => [
                'Herstart het apparaat en controleer of updates of opslagruimte het probleem verklaren.',
                'Maak eerst een back-up van belangrijke bestanden.',
                'Verwijder tijdelijke bestanden, ongebruikte apps of extensies stap voor stap.',
                'Controleer kabels, opladers, wifi en batterij voordat je software ingewikkeld maakt.',
                'Voer één wijziging per keer uit zodat je weet wat effect had.',
                'Reset pas als je zeker weet dat je data veilig is.',
            ],
            'eten-en-drinken' => [
                'Controleer geur, kleur, textuur en houdbaarheid voordat je iets gebruikt.',
                'Bewaar kwetsbare producten koel, droog of luchtdicht afhankelijk van het product.',
                'Werk schoon en voorkom kruisbesmetting tussen rauw en bereid eten.',
                'Proef en pas zout, zuur, zoet of vet in kleine stappen aan.',
                'Laat warm eten niet lang buiten de koelkast staan.',
                'Label restjes met datum zodat je later niet hoeft te gokken.',
            ],
            'werk-en-inkomen' => [
                'Schrijf eerst op wat het doel is en welke deadline of verplichting erbij hoort.',
                'Verzamel documenten, bedragen, contactgegevens en eerdere afspraken op één plek.',
                'Maak een korte versie voor snel overzicht en een detailversie voor controle.',
                'Gebruik vaste mappen of labels zodat je documenten later terugvindt.',
                'Controleer bedragen, datums en namen voordat je iets verstuurt.',
                'Vraag hulp als regels, contracten of financiële gevolgen onduidelijk zijn.',
            ],
            'hobby' => [
                'Begin met basismateriaal in plaats van meteen dure spullen te kopen.',
                'Oefen eerst een kleine proef voordat je aan het echte werk begint.',
                'Werk met goed licht, genoeg ruimte en materiaal dat schoon en droog blijft.',
                'Maak foto’s of notities van je stappen zodat je fouten kunt terugvinden.',
                'Bewaar half werk stofvrij, vlak of op spanning afhankelijk van de techniek.',
                'Rond af met een kleine verbetering in plaats van eindeloos perfectioneren.',
            ],
            'omas-oudste-trucjes' => [
                'Kies een milde huis-tuin-en-keukenmethode en test die klein.',
                'Gebruik azijn, soda, zout of citroen alleen op materialen die daartegen kunnen.',
                'Geef het middel tijd om te werken, maar laat het niet onnodig lang zitten.',
                'Spoel of poets na zodat er geen resten achterblijven.',
                'Herhaal rustig als het effect deels werkt.',
                'Stop als materiaal verkleurt, dof wordt of anders reageert dan verwacht.',
            ],
            'uiterlijk-verzorging' => [
                'Gebruik eerst een milde aanpak en vermijd meerdere nieuwe producten tegelijk.',
                'Reinig zacht en dep droog in plaats van hard te wrijven.',
                'Hydrateer of bescherm de huid of het haar afhankelijk van droogte, kou of zon.',
                'Test een product eerst klein als je snel reageert op verzorging.',
                'Geef een routine een paar dagen tot weken voordat je conclusies trekt.',
                'Stop bij irritatie, pijn of verergering en vraag professioneel advies.',
            ],
            'vervoer-auto' => [
                'Zorg eerst dat je veilig staat en goed zicht hebt voordat je iets probeert.',
                'Controleer handleiding, waarschuwingslampjes en basiszaken zoals bandenspanning of vloeistoffen.',
                'Gebruik het juiste hulpmiddel en forceer geen kwetsbare onderdelen.',
                'Maak schoon zonder scherpe materialen die lak, glas of rubbers beschadigen.',
                'Test na afloop rustig of het probleem echt weg is.',
                'Ga naar een garage bij remmen, stuurgedrag, motorproblemen of twijfel over veiligheid.',
            ],
            'duurzaamheid' => [
                'Begin met de maatregel die je direct kunt volhouden zonder grote aankoop.',
                'Meet of schat eerst waar de meeste verspilling zit: warmte, stroom, water of afval.',
                'Hergebruik, repareer of leen voordat je nieuw koopt.',
                'Maak gedrag makkelijk met vaste plekken, timers of routines.',
                'Vergelijk de kosten met de besparing over meerdere maanden.',
                'Kies de stap die zowel duurzaam als praktisch blijft in je dagelijks leven.',
            ],
        ];

        return $steps[$categorySlug] ?? [
            'Maak het probleem concreet: wat gebeurt er, sinds wanneer en wat heb je al geprobeerd?',
            'Begin met de minst risicovolle oplossing en werk pas daarna naar zwaardere stappen.',
            'Verzamel de spullen of informatie die je nodig hebt voordat je begint.',
            'Test je aanpak klein en controleer het resultaat.',
            'Pas één ding tegelijk aan zodat je weet wat werkt.',
            'Stop bij schade, twijfel of een onveilig gevoel en vraag iemand met expertise mee te kijken.',
        ];
    }
}
