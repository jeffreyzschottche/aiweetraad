<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'over-ons',
                'title' => 'Over AI Weet Raad',
                'meta_description' => 'Wat is AI Weet Raad en hoe werkt het? Lees er hier alles over.',
                'body' => <<<'HTML'
<p><strong>AI Weet Raad</strong> is dé plek waar je dagelijkse vragen stelt en meteen antwoord krijgt van meerdere AI-assistenten tegelijk. Geen eindeloos zoeken meer: stel je vraag en vergelijk de adviezen naast elkaar.</p>
<h2>Hoe werkt het?</h2>
<p>Je stelt een vraag — bijvoorbeeld over een vlek, een kwaaltje of een klusje in huis. ChatGPT, Claude, Gemini, Grok en DeepSeek geven elk hun eigen antwoord. Jij bepaalt vervolgens welk advies werkt door op “Werkt” of “Niet voor mij” te stemmen.</p>
<h2>Waarom meerdere AI's?</h2>
<p>Elke AI heeft een eigen stijl en invalshoek. Door antwoorden te vergelijken krijg je een vollediger beeld en kies je de aanpak die bij jou past.</p>
<h2>Waarvoor gebruik je het?</h2>
<p>Voor schoonmaken, vlekken, koken, huisdieren, gezondheid, tuin, kleding, elektronica en alle kleine problemen waar je snel een praktisch startpunt voor wilt.</p>
<h2>Stemmen maakt het beter</h2>
<p>De site kijkt niet alleen naar wat een AI zegt, maar ook naar wat bezoekers bruikbaar vinden. Likes en dislikes helpen om antwoorden en AI-modellen beter te vergelijken.</p>
<h2>Blijf zelf nadenken</h2>
<p>AI-antwoorden zijn handig als startpunt, maar niet feilloos. Controleer advies altijd zelf, zeker bij medische, juridische, financiële of veiligheidsgevoelige onderwerpen.</p>
<p>Heb je een goede vraag? Stel hem gerust — de community en de AI's helpen je graag verder.</p>
HTML,
            ],
            [
                'slug' => 'adverteren',
                'title' => 'Adverteren op AI Weet Raad',
                'meta_description' => 'Bereik een betrokken publiek dat actief op zoek is naar oplossingen.',
                'body' => <<<'HTML'
<p>Wil je adverteren op <strong>AI Weet Raad</strong>? Onze bezoekers zijn actief op zoek naar concrete oplossingen voor alledaagse vragen. Dat is precies het moment waarop een relevant product, dienst of merk waarde toevoegt.</p>
<h2>Advertentiemogelijkheden</h2>
<ul>
<li><strong>Leaderboard</strong> — een opvallende banner bovenaan elke pagina.</li>
<li><strong>In-content</strong> — een native blok tussen de antwoorden, precies waar de aandacht is.</li>
<li><strong>Sidebar</strong> — een meescrollende advertentie naast de inhoud.</li>
</ul>
<h2>Passende branches</h2>
<p>De beste match ligt bij schoonmaak, drogisterij, huis en tuin, huisdieren, keuken, duurzaamheid, verzekeringen, lokale diensten en praktische webshops.</p>
<h2>Waarom adverteren hier werkt</h2>
<p>Bezoekers komen met een concrete intentie: ze zoeken een oplossing. Daardoor past relevante zichtbaarheid beter bij hun moment dan een algemene banner op een brede nieuwssite.</p>
<h2>Campagnevormen</h2>
<p>We kunnen meedenken over vaste posities, tijdelijke campagnes, categoriegerichte zichtbaarheid, native content en testperiodes. De invulling hangt af van je doelgroep en gewenste bereik.</p>
<p>Interesse? Neem contact met ons op via de <a href="/contact">contactpagina</a> en we sturen je vrijblijvend de mogelijkheden, formaten en tarieven.</p>
HTML,
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacybeleid',
                'meta_description' => 'Hoe AI Weet Raad omgaat met jouw gegevens.',
                'body' => <<<'HTML'
<p>AI Weet Raad respecteert je privacy. Op deze pagina lees je welke gegevens we verzamelen en waarom.</p>
<h2>Welke gegevens verzamelen we?</h2>
<p>Als je een account aanmaakt, bewaren we je naam en e-mailadres. Stel je een vraag of markeer je een antwoord als werkend, dan kunnen we die activiteit aan je profiel koppelen.</p>
<h2>AI-verwerking</h2>
<p>Wanneer je een vraag stelt, kan de inhoud naar gebruikte AI-providers worden gestuurd om antwoorden te genereren. Deel daarom geen wachtwoorden, burgerservicenummers, betaalgegevens of andere gevoelige informatie.</p>
<h2>Cookies en advertenties</h2>
<p>We gebruiken functionele cookies en technische gegevens om de site veilig en bruikbaar te houden. Advertentiepartners kunnen eigen cookies gebruiken wanneer echte advertentietags worden toegevoegd. Je kunt cookies weigeren of verwijderen via je browserinstellingen.</p>
<h2>Je rechten</h2>
<p>Je hebt het recht om je gegevens in te zien, te corrigeren of te laten verwijderen. Neem hiervoor contact met ons op.</p>
HTML,
            ],
            [
                'slug' => 'voorwaarden',
                'title' => 'Algemene voorwaarden',
                'meta_description' => 'De gebruiksvoorwaarden van AI Weet Raad.',
                'body' => <<<'HTML'
<p>Door AI Weet Raad te gebruiken ga je akkoord met onderstaande voorwaarden.</p>
<h2>Aard van de antwoorden</h2>
<p>De antwoorden op deze site worden gegenereerd door AI-assistenten. Ze zijn bedoeld als algemene informatie en vormen geen professioneel, medisch, juridisch of financieel advies. Twijfel je? Raadpleeg altijd een deskundige.</p>
<h2>Gebruik van de site</h2>
<p>Je gebruikt de site op een respectvolle manier. Het is niet toegestaan om de site te misbruiken, te overbelasten of er onrechtmatige, schadelijke, beledigende, spamachtige of misleidende inhoud op te plaatsen.</p>
<h2>Accounts en limieten</h2>
<p>Voor nieuwe AI-vragen en stemmen op antwoorden kan een account vereist zijn. We kunnen daglimieten toepassen om misbruik en onnodige API-kosten te voorkomen.</p>
<h2>Beschikbaarheid</h2>
<p>We proberen AI Weet Raad stabiel te houden, maar garanderen niet dat de site, AI-providers of alle antwoorden altijd beschikbaar zijn.</p>
<h2>Aansprakelijkheid</h2>
<p>We doen ons best om correcte informatie te tonen, maar kunnen niet garanderen dat alle antwoorden juist of volledig zijn. Het opvolgen van advies is op eigen risico.</p>
HTML,
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(['slug' => $page['slug']], $page);
        }
    }
}
