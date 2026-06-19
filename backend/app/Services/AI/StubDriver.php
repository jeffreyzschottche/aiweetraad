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
        $specific = $this->specificAdvice($question->slug);
        $persona = $this->persona($model->slug);
        $steps = $specific['model_steps'][$model->slug]
            ?? (isset($specific['steps']) ? $this->modelSpecificSteps($specific['steps'], $model->slug) : null)
            ?? $this->steps($question, $model, $categorySlug);

        $intro = str_replace('{topic}', $topic, $persona['intro']);
        $outro = $persona['outro'];
        $focus = isset($specific['focus'])
            ? $this->modelSpecificFocus($specific['focus'], $model->slug)
            : $this->categoryFocus($categorySlug);

        $list = collect($steps)
            ->map(fn ($s, $i) => ($i + 1) . '. ' . $s)
            ->implode("\n");

        return $intro . "\n\n" . $focus . "\n\n" . $list . "\n\n" . $outro;
    }

    private function modelSpecificFocus(string $focus, string $modelSlug): string
    {
        return match ($modelSlug) {
            'claude' => $focus . ' Kies daarbij steeds de mildste stap die nog logisch is.',
            'gemini' => $focus . ' Begin met de snelste veilige optie en houd een alternatief achter de hand.',
            'grok' => $focus . ' Houd het simpel: eerst losweken of voorbereiden, daarna pas sterker ingrijpen.',
            'deepseek' => $focus . ' Bepaal eerst welk type oorzaak of materiaal je hebt; daarna kies je pas de methode.',
            default => $focus,
        };
    }

    private function modelSpecificSteps(array $steps, string $modelSlug): array
    {
        $steps = array_values($steps);
        $pick = fn (int $index, ?string $fallback = null) => $steps[$index] ?? $fallback;
        $last = $steps[array_key_last($steps)] ?? 'Controleer het resultaat voordat je verdergaat.';

        return match ($modelSlug) {
            'claude' => array_values(array_filter([
                $pick(0),
                $pick(1),
                'Test de aanpak rustig en klein als er kans is op schade of irritatie.',
                $pick(2),
                $pick(3),
                'Stop als het materiaal, je huid of de situatie verkeerd reageert en kies dan een mildere route.',
            ])),
            'gemini' => array_values(array_filter([
                'Start met de snelste veilige optie: ' . lcfirst((string) $pick(0, 'begin klein.')),
                'Als dat niet genoeg is: ' . lcfirst((string) $pick(1, 'probeer de volgende milde stap.')),
                'Alternatief: ' . lcfirst((string) $pick(2, 'gebruik een andere veilige methode.')),
                $pick(3),
                'Rond af met controle: ' . lcfirst($last),
            ])),
            'grok' => array_values(array_filter([
                'Niet moeilijker maken dan nodig: ' . lcfirst((string) $pick(0, 'begin met de simpelste stap.')),
                $pick(1),
                $pick(2),
                'Gebruik pas iets sterkers als de simpele aanpak niets doet.',
                'Maak het af: ' . lcfirst($last),
            ])),
            'deepseek' => array_values(array_filter([
                'Bepaal eerst de oorzaak: gaat het om vuil, vocht, vet, gewoonte, slijtage of planning?',
                'Kies daarna de minst risicovolle stap: ' . lcfirst((string) $pick(0, 'begin klein.')),
                'Controleer het effect voordat je opschaalt.',
                'Werkt stap één niet, ga door naar: ' . lcfirst((string) $pick(1, 'een tweede veilige methode.')),
                $pick(2),
                'Evalueer na afloop of de oorzaak ook is weggenomen, anders komt het probleem terug.',
            ])),
            default => $steps,
        };
    }

    private function specificAdvice(string $slug): ?array
    {
        $advice = [
            'hoe-kom-ik-snel-van-de-hik-af' => [
                'focus' => 'Hik is meestal onschuldig en gaat vanzelf over. Het doel is je ademhaling en middenrif even te resetten.',
                'steps' => [
                    'Adem rustig in, houd je adem 10 tot 15 seconden vast en adem langzaam uit.',
                    'Drink een glas koud water met kleine slokjes achter elkaar.',
                    'Probeer een theelepel suiker rustig op je tong te laten smelten.',
                    'Eet of drink even niets scherps, koolzuurhoudends of heel warms.',
                    'Ga rechtop zitten en adem een minuut langzaam via je buik.',
                    'Bel de huisarts als de hik langer dan 48 uur aanhoudt of steeds terugkomt.',
                ],
            ],
            'wat-helpt-tegen-een-droge-keel-in-de-winter' => [
                'focus' => 'Een droge keel in de winter komt vaak door droge lucht, mondademhaling of irritatie door verkoudheid.',
                'steps' => [
                    'Drink verspreid over de dag kleine slokken water of lauwe thee.',
                    'Zet de verwarming niet te hoog en ventileer kort maar regelmatig.',
                    'Gebruik eventueel een luchtbevochtiger of hang een bakje water bij de radiator.',
                    'Zuig op een suikervrij keelpastilletje om speekselproductie te stimuleren.',
                    'Vermijd rook, alcohol en heel pittig eten zolang je keel geïrriteerd is.',
                    'Neem contact op met de huisarts bij koorts, benauwdheid, slikproblemen of klachten langer dan een week.',
                ],
            ],
            'hoe-val-ik-makkelijker-in-slaap' => [
                'focus' => 'Makkelijker inslapen draait vooral om ritme, licht, temperatuur en je hoofd op tijd uit de actiestand halen.',
                'steps' => [
                    'Sta elke dag rond dezelfde tijd op, ook na een mindere nacht. Dat zet je biologische klok beter vast.',
                    'Dim het licht en stop 45 tot 60 minuten voor bed met fel schermgebruik of zet minimaal nachtmodus aan.',
                    'Houd je slaapkamer koel, donker en rustig; meestal werkt 16 tot 18 graden beter dan een warme kamer.',
                    'Drink na de middag geen cafeïne meer en neem alcohol niet als slaapmiddel, want dat maakt slaap onrustiger.',
                    'Doe een vaste wind-down: douchen, tandenpoetsen, spullen klaarleggen en 10 minuten lezen of rustige muziek.',
                    'Lig je langer dan 20 tot 30 minuten wakker? Ga even uit bed en doe iets saais bij gedimd licht tot je slaperig wordt.',
                ],
                'model_steps' => [
                    'openai' => [
                        'Kies één vaste wektijd en houd die een week vol, ook als je slecht hebt geslapen.',
                        'Zet 60 minuten voor bed je telefoon weg of gebruik alleen nog rustige audio.',
                        'Maak een vaste volgorde: licht dimmen, tandenpoetsen, kleding klaarleggen, 10 minuten lezen.',
                        'Houd de kamer koel en donker; een warme kamer maakt inslapen vaak lastiger.',
                        'Drink na 14:00 liever geen koffie, energydrink of sterke zwarte thee meer.',
                        'Als je blijft piekeren: schrijf drie losse gedachten op papier en leg dat naast je bed weg.',
                    ],
                    'claude' => [
                        'Begin met een rustige avondroutine die je lichaam herkent, niet met een nieuw trucje pas in bed.',
                        'Dim licht ruim op tijd, want fel licht houdt je brein wakkerder dan je merkt.',
                        'Doe iets kalms zonder doel, zoals lezen, rustige muziek of ademhalingsoefeningen.',
                        'Sta op als je na 20 tot 30 minuten gefrustreerd wakker ligt; keer terug zodra je slaperig wordt.',
                        'Gebruik melatonine of slaapmiddelen niet zomaar langdurig zonder arts of apotheker.',
                        'Zoek hulp als slapeloosheid weken aanhoudt, je functioneren raakt of samengaat met paniek of somberheid.',
                    ],
                    'gemini' => [
                        'Pak eerst de snelle winst: koele kamer, donkerte, geen meldingen en geen fel scherm in bed.',
                        'Probeer de 4-7-8 ademhaling: 4 tellen in, 7 vasthouden, 8 uit, een paar rondes rustig.',
                        'Neem een warme douche 60 tot 90 minuten voor bed; daarna koelt je lichaam af en word je slaperiger.',
                        'Eet laat op de avond niet zwaar en drink niet veel vlak voor bed.',
                        'Gebruik je bed alleen voor slaap en ontspanning, niet voor werk of eindeloos scrollen.',
                        'Herhaal dezelfde aanpak minstens een week voordat je beoordeelt of het werkt.',
                    ],
                    'grok' => [
                        'Stop met doomscrollen in bed. Dat is meestal de grootste boosdoener.',
                        'Maak je kamer donker, koel en saai; slapen lukt slechter in een mini-bioscoop met meldingen.',
                        'Geen cafeïne laat op de dag. Ook “ik kan daar tegen” is vaak zelfbedrog.',
                        'Lig je wakker? Blijf niet boos naar het plafond staren; ga even uit bed en doe iets saais.',
                        'Zet morgen alvast klaar wat je nodig hebt, zodat je hoofd minder lijstjes blijft maken.',
                        'Als je dit wekenlang hebt, ga niet stoer doorploeteren maar bespreek het met je huisarts.',
                    ],
                    'deepseek' => [
                        'Splits het probleem op: ben je niet slaperig, pieker je, of word je juist vaak wakker?',
                        'Bij niet slaperig zijn: vaste wektijd, ochtendlicht en minder cafeïne na de middag.',
                        'Bij piekeren: schrijf taken en zorgen vóór bed kort op, inclusief één eerstvolgende actie.',
                        'Bij vaak wakker worden: let op alcohol, warmte, geluid en veel drinken vlak voor bed.',
                        'Meet een week je bedtijd, wektijd, cafeïne en schermtijd; dan zie je sneller de echte trigger.',
                        'Verander één factor tegelijk, anders weet je niet waardoor je slaap verbetert.',
                    ],
                ],
            ],
            'wanneer-moet-ik-met-hoofdpijn-naar-de-huisarts' => [
                'focus' => 'Hoofdpijn is vaak onschuldig, maar sommige signalen moet je serieus nemen.',
                'steps' => [
                    'Bel met spoed bij plotselinge, extreem heftige hoofdpijn die anders voelt dan normaal.',
                    'Zoek hulp bij uitvalsverschijnselen, verwardheid, scheve mond, krachtsverlies of problemen met praten.',
                    'Neem contact op als hoofdpijn ontstaat na een val of klap op het hoofd.',
                    'Maak een afspraak als hoofdpijn steeds vaker terugkomt of je dagelijkse leven belemmert.',
                    'Noteer frequentie, plek, duur, triggers, medicijngebruik en bijkomende klachten.',
                    'Gebruik pijnstillers niet te vaak; veelvuldig gebruik kan juist hoofdpijn onderhouden.',
                ],
            ],
            'hoe-maak-ik-kalkaanslag-op-de-kraan-weg' => [
                'focus' => 'Kalk los je meestal veilig op met zuur, maar kwetsbare coatings en natuursteen moet je vermijden.',
                'steps' => [
                    'Wikkel een doek met schoonmaakazijn om de kalkplek.',
                    'Laat dit 20 tot 30 minuten zitten, niet urenlang op kwetsbare kranen.',
                    'Poets randjes los met een zachte tandenborstel.',
                    'Spoel grondig na met water zodat er geen azijn achterblijft.',
                    'Droog de kraan met een microvezeldoek om nieuwe kalkvlekken te voorkomen.',
                    'Gebruik geen schuurspons of agressieve ontkalker op zwarte, gouden of matte kranen zonder handleiding te checken.',
                ],
            ],
            'wat-is-de-beste-manier-om-ramen-streeploos-te-wassen' => [
                'focus' => 'Strepen ontstaan vooral door te veel sop, vieze doeken of wassen in felle zon.',
                'steps' => [
                    'Was ramen op een bewolkt moment zodat het water niet te snel opdroogt.',
                    'Gebruik lauw water met een klein drupje afwasmiddel of glasreiniger.',
                    'Maak eerst kozijnen en randen schoon, anders loopt vuil terug over het glas.',
                    'Trek het raam in banen droog met een schone trekker.',
                    'Veeg de trekker na elke baan af met een doek.',
                    'Droog randen na met een droge microvezeldoek of zeem.',
                ],
            ],
            'hoe-krijg-ik-een-muffe-geur-uit-de-koelkast' => [
                'focus' => 'Een muffe koelkast vraagt om bron verwijderen, schoonmaken en daarna geur absorberen.',
                'steps' => [
                    'Haal producten eruit en gooi bedorven of open verpakkingen weg.',
                    'Maak planken, rubbers en lades schoon met warm water en soda of mild afwasmiddel.',
                    'Droog alles goed, vooral de rubbers en hoekjes.',
                    'Controleer het afvoergaatje achterin en maak het voorzichtig vrij.',
                    'Zet een bakje baking soda of gemalen koffie een nacht in de koelkast.',
                    'Bewaar sterk ruikende producten voortaan afgesloten.',
                ],
            ],
            'hoe-maak-ik-voegen-in-de-badkamer-weer-schoon' => [
                'focus' => 'Voegen worden vies door zeepresten, kalk en schimmel. Begin mild en ventileer goed.',
                'steps' => [
                    'Maak de voegen nat en borstel los vuil weg.',
                    'Breng een pasta van baking soda en water aan op de voegen.',
                    'Laat 10 tot 15 minuten intrekken en borstel met een oude tandenborstel.',
                    'Spoel goed na en droog de tegels.',
                    'Gebruik bij schimmel een geschikt schimmelmiddel en ventileer ruim.',
                    'Voorkom terugkeer door na het douchen te ventileren en wanden droog te trekken.',
                ],
            ],
            'hoe-bewaar-ik-verse-kruiden-langer' => [
                'focus' => 'Verse kruiden blijven langer goed als je ze behandelt als kwetsbare blaadjes of als een klein bosje bloemen.',
                'steps' => [
                    'Was kruiden pas vlak voor gebruik, want nat bewaren versnelt bederf.',
                    'Wikkel zachte kruiden zoals koriander en peterselie losjes in vochtig keukenpapier.',
                    'Bewaar ze in een afgesloten bakje of zakje in de koelkast.',
                    'Zet basilicum liever buiten de koelkast in een glas water op het aanrecht.',
                    'Knip bruine of slappe delen weg zodat de rest langer goed blijft.',
                    'Vries restjes fijngehakt in met olie of water in een ijsblokjesvorm.',
                ],
            ],
            'hoe-voorkom-ik-dat-rijst-aan-elkaar-plakt' => [
                'focus' => 'Plakkerige rijst komt vaak door overtollig zetmeel, te veel water of roeren tijdens het koken.',
                'steps' => [
                    'Spoel rijst in een zeef tot het water grotendeels helder is.',
                    'Gebruik de juiste verhouding water; vaak ongeveer 1 deel rijst op 1,5 tot 2 delen water.',
                    'Breng aan de kook, zet laag en laat met deksel rustig garen.',
                    'Roer niet tijdens het koken, want dat maakt zetmeel los.',
                    'Laat de rijst na het koken 10 minuten met deksel staan.',
                    'Maak los met een vork in plaats van met een lepel te drukken.',
                ],
            ],
            'hoe-weet-ik-of-eieren-nog-goed-zijn' => [
                'focus' => 'Eieren kun je beoordelen met datum, geur en eventueel de drijftest.',
                'steps' => [
                    'Check eerst de houdbaarheidsdatum en bewaaradvies.',
                    'Leg het ei in een glas koud water: blijft het plat liggen, dan is het meestal vers.',
                    'Gaat het rechtop staan, gebruik het dan liever goed verhit.',
                    'Drijft het ei boven, gooi het weg.',
                    'Breek twijfelgevallen apart in een kommetje en ruik eraan.',
                    'Gebruik geen ei dat vies ruikt, verkleurd is of vreemd slijmerig oogt.',
                ],
            ],
            'hoe-red-ik-een-soep-die-te-zout-is-geworden' => [
                'focus' => 'Te zoute soep los je op door verdunnen, balanceren of extra ongezouten ingrediënten toe te voegen.',
                'steps' => [
                    'Voeg ongezouten bouillon, water of room toe en proef opnieuw.',
                    'Doe extra groenten, bonen, pasta, rijst of aardappelblokjes erbij.',
                    'Laat een rauwe aardappel 15 minuten meekoken en haal hem eruit; dit helpt soms beperkt.',
                    'Voeg een klein beetje zuur toe, zoals citroen of azijn, om zout minder vlak te laten smaken.',
                    'Maak de soep niet zoeter als hoofdoplossing; dat maskeert zout maar maakt hem snel vreemd.',
                    'Serveer met ongezouten brood, rijst of aardappels als hij nog net iets te zout is.',
                ],
            ],
            'hoe-ontkalk-ik-mijn-waterkoker' => [
                'focus' => 'Een waterkoker ontkalk je met mild zuur en daarna vooral goed naspoelen.',
                'steps' => [
                    'Vul de waterkoker half met water en voeg een scheut schoonmaakazijn of citroensap toe.',
                    'Breng kort aan de kook en laat 15 tot 30 minuten staan.',
                    'Giet leeg en spoel meerdere keren met schoon water.',
                    'Kook daarna één volle kan water en gooi die weg.',
                    'Herhaal bij dikke kalk, maar schuur het verwarmingselement niet.',
                    'Ontkalk vaker als je in een gebied met hard water woont.',
                ],
            ],
            'hoe-slijp-ik-een-bot-keukenmes-thuis' => [
                'focus' => 'Een aanzetstaal onderhoudt een mes, maar een echt bot mes moet je slijpen met steen of slijper.',
                'steps' => [
                    'Gebruik een wetsteen of degelijke doortrekslijper die past bij keukenmessen.',
                    'Houd bij een wetsteen een vaste hoek aan van ongeveer 15 tot 20 graden.',
                    'Slijp beide kanten even vaak en werk rustig van hiel naar punt.',
                    'Gebruik daarna een fijnere korrel om de snede gladder te maken.',
                    'Spoel en droog het mes direct na het slijpen.',
                    'Gebruik geen glazen snijplank; die maakt messen snel weer bot.',
                ],
            ],
            'hoe-krijg-ik-aangebrande-resten-uit-een-pan' => [
                'focus' => 'Aangebrande resten week je los; hard schuren beschadigt vooral de pan.',
                'steps' => [
                    'Laat de pan afkoelen en haal losse resten eruit.',
                    'Vul de bodem met warm water en een schep baking soda.',
                    'Laat dit 30 minuten weken of breng het kort aan de kook.',
                    'Schraap voorzichtig met een houten spatel.',
                    'Gebruik een zachte spons voor de laatste resten.',
                    'Vermijd staalwol op antiaanbaklagen en emaille.',
                ],
            ],
            'hoe-maak-ik-een-snijplank-weer-fris' => [
                'focus' => 'Een frisse snijplank vraagt om reinigen, ontgeuren en goed drogen.',
                'steps' => [
                    'Was de plank direct na gebruik met warm water en afwasmiddel.',
                    'Strooi zout of baking soda op de plank bij geur.',
                    'Wrijf met een halve citroen over het oppervlak.',
                    'Spoel goed na en droog rechtop aan de lucht.',
                    'Gebruik aparte planken voor rauw vlees en groente.',
                    'Olie houten planken af en toe licht in met geschikte onderhoudsolie.',
                ],
            ],
            'hoe-houd-ik-slakken-weg-uit-mijn-moestuin' => [
                'focus' => 'Slakken beperk je het best met schuilplekken weghalen, barrières en regelmatig controleren.',
                'steps' => [
                    'Geef liever ’s ochtends water, zodat de tuin ’s avonds minder vochtig is.',
                    'Haal planken, potten en nat blad weg waar slakken onder schuilen.',
                    'Controleer jonge planten ’s avonds en haal slakken handmatig weg.',
                    'Bescherm kwetsbare planten met koperrand, kraagjes of fijnmazig gaas.',
                    'Lok slakken naar een plankje en verwijder ze daar dagelijks.',
                    'Gebruik korrels alleen diervriendelijk en volgens verpakking, zeker met huisdieren in de buurt.',
                ],
            ],
            'wanneer-kan-ik-mijn-gras-het-beste-maaien' => [
                'focus' => 'Gras maai je het best als het droog is, actief groeit en niet in volle stress staat.',
                'steps' => [
                    'Maai in het groeiseizoen meestal één keer per week.',
                    'Maai niet tijdens vorst, hittegolven of wanneer het gras nat is.',
                    'Houd ongeveer 4 tot 5 centimeter hoogte aan voor een sterk gazon.',
                    'Haal nooit meer dan een derde van de lengte in één maaibeurt weg.',
                    'Maai in droge periodes wat hoger zodat de bodem minder uitdroogt.',
                    'Zorg voor scherpe messen, anders rafelt het gras en wordt het geel.',
                ],
            ],
            'hoe-krijg-ik-groene-aanslag-van-mijn-tegels' => [
                'focus' => 'Groene aanslag verwijder je met borstelen, water en eventueel een mild middel; voorkom dat resten in planten of vijver lopen.',
                'steps' => [
                    'Veeg eerst los vuil en bladeren weg.',
                    'Schrob de tegels met warm water en een harde bezem.',
                    'Gebruik eventueel groene-aanslagreiniger volgens etiket of een milde soda-oplossing.',
                    'Laat het middel inwerken, maar niet opdrogen als de verpakking dat afraadt.',
                    'Spoel gecontroleerd na en houd afvoer naar planten beperkt.',
                    'Verbeter zon en ventilatie waar mogelijk, want schaduw en vocht laten aanslag terugkomen.',
                ],
            ],
            'hoe-geef-ik-kamerplanten-water-zonder-wortelrot' => [
                'focus' => 'Wortelrot voorkom je door pas water te geven als de plant het nodig heeft en overtollig water weg te laten lopen.',
                'steps' => [
                    'Voel met je vinger 2 tot 3 centimeter diep of de aarde droog is.',
                    'Gebruik een pot met drainagegaten.',
                    'Geef water tot het onder uit de pot loopt en giet de sierpot daarna leeg.',
                    'Geef in de winter minder vaak water dan in de groeiperiode.',
                    'Laat planten niet permanent in natte aarde staan.',
                    'Check gele bladeren, slappe stengels en muffe grond als waarschuwing voor te veel water.',
                ],
            ],
            'hoe-leer-ik-mijn-kat-van-het-aanrecht-af-te-blijven' => [
                'focus' => 'Een kat leer je dit vooral door het aanrecht saai te maken en betere alternatieven te bieden.',
                'steps' => [
                    'Laat geen eten, kruimels of spannende spullen op het aanrecht liggen.',
                    'Geef je kat een hoge toegestane plek, zoals een krabpaal of plank.',
                    'Beloon gebruik van die plek met aandacht of een snack.',
                    'Til je kat rustig weg zonder boos te worden als hij toch springt.',
                    'Gebruik eventueel tijdelijk dubbelzijdige tape of een onaangename ondergrond.',
                    'Wees consequent; soms duurt het weken voordat de gewoonte verdwijnt.',
                ],
            ],
            'hoe-voorkom-ik-dat-mijn-hond-in-de-tuin-graaft' => [
                'focus' => 'Graven komt vaak door energie, verveling, warmte of jachtgedrag.',
                'steps' => [
                    'Geef dagelijks genoeg beweging en snuffeltijd buiten de tuin.',
                    'Bied kauwspeelgoed of zoekspelletjes aan voordat de hond alleen de tuin in gaat.',
                    'Maak favoriete graafplekken minder aantrekkelijk met gaas of plantenbakken.',
                    'Maak eventueel één toegestane graafhoek met zand en verstop daar speeltjes.',
                    'Laat de hond niet lang alleen in de tuin als hij dan gaat graven.',
                    'Check of hij graaft om verkoeling te zoeken en bied schaduw en water.',
                ],
            ],
            'wat-kan-ik-doen-als-mijn-hond-bang-is-voor-vuurwerk' => [
                'focus' => 'Vuurwerkangst pak je aan met veiligheid, voorspelbaarheid en eventueel training ruim voor oud en nieuw.',
                'steps' => [
                    'Laat je hond op tijd uit, voordat het vuurwerk hevig wordt.',
                    'Maak een veilige plek in huis met mand, kleed en gedempt geluid.',
                    'Sluit gordijnen en zet zachte muziek of witte ruis aan.',
                    'Blijf zelf rustig en straf angstig gedrag nooit.',
                    'Gebruik snacks of kauwmateriaal als je hond nog kan eten.',
                    'Bespreek ernstige angst ruim vooraf met de dierenarts voor training of medicatie-opties.',
                ],
            ],
            'hoe-laat-ik-mijn-kat-meer-water-drinken' => [
                'focus' => 'Katten drinken vaak meer als water fris, bewegend en weg van voer staat.',
                'steps' => [
                    'Zet meerdere waterbakjes op rustige plekken in huis.',
                    'Gebruik brede bakjes zodat snorharen de rand niet raken.',
                    'Ververs water dagelijks.',
                    'Probeer een drinkfontein als je kat stromend water interessant vindt.',
                    'Geef vaker natvoer of meng een beetje extra water door natvoer.',
                    'Bel de dierenarts als je kat plots veel meer of juist bijna niet drinkt.',
                ],
            ],
            'wat-helpt-tegen-droge-handen-in-de-winter' => [
                'focus' => 'Droge handen herstellen beter met mild wassen, vet insmeren en bescherming tegen kou en schoonmaakmiddelen.',
                'steps' => [
                    'Was met lauw water in plaats van heet water.',
                    'Gebruik een milde, parfumvrije zeep.',
                    'Smeer direct na wassen een vette handcrème of zalf.',
                    'Draag handschoenen buiten en bij schoonmaken.',
                    'Smeer ’s avonds dik in en draag eventueel katoenen handschoenen.',
                    'Ga naar de huisarts bij kloven, bloed, eczeem of pijnlijke ontsteking.',
                ],
            ],
            'hoe-krijg-ik-statisch-haar-onder-controle' => [
                'focus' => 'Statisch haar ontstaat door droogte en wrijving; vocht en glad maken helpen het meest.',
                'steps' => [
                    'Gebruik conditioner na het wassen.',
                    'Was je haar niet onnodig vaak met agressieve shampoo.',
                    'Gebruik een leave-in conditioner of klein beetje haarolie in de punten.',
                    'Kam met een houten kam of antistatische borstel.',
                    'Vermijd synthetische mutsen en sjaals waar mogelijk.',
                    'Maak je handen licht vochtig en strijk over pluizige lokken voor een snelle fix.',
                ],
            ],
            'hoe-voorkom-ik-scheerirritatie' => [
                'focus' => 'Scheerirritatie voorkom je met verzachten, scherp materiaal en niet te agressief scheren.',
                'steps' => [
                    'Scheer na een warme douche of maak de huid eerst goed nat.',
                    'Gebruik scheergel of -crème en laat die even inwerken.',
                    'Scheer met de haargroei mee als je snel irritatie krijgt.',
                    'Gebruik een schoon en scherp mesje.',
                    'Druk niet hard; laat het mes het werk doen.',
                    'Spoel koud na en gebruik een milde, parfumvrije moisturizer.',
                ],
            ],
            'wat-helpt-tegen-droge-lippen' => [
                'focus' => 'Droge lippen herstellen door beschermen, niet likken en irriterende producten vermijden.',
                'steps' => [
                    'Gebruik meerdere keren per dag een simpele lippenbalsem met vaseline of lanoline.',
                    'Lik niet aan je lippen; dat droogt ze juist verder uit.',
                    'Drink genoeg en voorkom droge lucht in huis.',
                    'Gebruik buiten een balsem met SPF bij zon of kou.',
                    'Vermijd menthol, parfum of tintelende lipproducten als je lippen kapot zijn.',
                    'Vraag advies bij hardnekkige kloven, korstjes of ontsteking in de mondhoeken.',
                ],
            ],
            'hoe-verwijder-ik-een-rodewijnvlek-uit-een-tafelkleed' => [
                'focus' => 'Bij rode wijn telt snelheid: de vlek deppen, verdunnen en pas wassen als hij grotendeels los is.',
                'steps' => [
                    'Dep direct met keukenpapier of een schone doek, zonder te wrijven.',
                    'Strooi zout of baking soda op de natte plek en laat dit 10 minuten vocht opnemen.',
                    'Spoel de achterkant van de stof met koud water.',
                    'Behandel de plek met vloeibaar wasmiddel of vlekkenmiddel voor textiel.',
                    'Was volgens het waslabel.',
                    'Stop het kleed pas in de droger als de vlek volledig weg is.',
                ],
            ],
            'hoe-krijg-ik-een-grasvlek-uit-een-spijkerbroek' => [
                'focus' => 'Grasvlekken bevatten pigment; voorbehandelen werkt beter dan meteen heet wassen.',
                'steps' => [
                    'Maak de vlek nat met koud water.',
                    'Wrijf vloeibaar wasmiddel of ossengalzeep voorzichtig in de vlek.',
                    'Laat 15 tot 30 minuten intrekken.',
                    'Borstel zacht met een oude tandenborstel als de stof stevig genoeg is.',
                    'Was de broek volgens het waslabel.',
                    'Herhaal de behandeling als er nog groen zichtbaar is voordat je droogt.',
                ],
            ],
            'hoe-haal-ik-koffievlekken-uit-een-wit-overhemd' => [
                'focus' => 'Koffie haal je het best weg door snel te spoelen en daarna gericht voor te behandelen.',
                'steps' => [
                    'Spoel de vlek vanaf de achterkant met koud water.',
                    'Dep met een beetje vloeibaar wasmiddel of afwasmiddel.',
                    'Laat 10 minuten intrekken.',
                    'Spoel opnieuw en controleer of de bruine waas lichter wordt.',
                    'Was het overhemd met witwasmiddel volgens het label.',
                    'Gebruik pas bleekmiddel als het overhemd daar volgens het label tegen kan.',
                ],
            ],
            'hoe-verwijder-ik-vetvlekken-uit-een-t-shirt' => [
                'focus' => 'Vet moet je losmaken met ontvetter voordat de wasmachine het goed kan meenemen.',
                'steps' => [
                    'Dep overtollig vet weg met keukenpapier.',
                    'Breng een druppel afwasmiddel direct op de vlek aan.',
                    'Wrijf zacht met je vingers en laat 10 tot 15 minuten intrekken.',
                    'Spoel met warm water als de stof dat aankan.',
                    'Was daarna normaal volgens het label.',
                    'Controleer voor het drogen of de vetkring weg is.',
                ],
            ],
            'hoe-was-ik-een-wollen-trui-zonder-dat-hij-krimpt' => [
                'focus' => 'Wol krimpt door warmte, wrijving en plots temperatuurverschil. Rustig wassen is dus belangrijker dan hard schoonmaken.',
                'steps' => [
                    'Check het label: handwas, wolprogramma of stomerij.',
                    'Gebruik koud tot lauw water en speciaal wolwasmiddel.',
                    'Wrijf of wring de trui niet.',
                    'Spoel op dezelfde temperatuur als waarmee je wast.',
                    'Druk water eruit in een handdoek.',
                    'Laat plat drogen in vorm, niet hangend aan een hanger.',
                ],
            ],
            'hoe-krijg-ik-mijn-witte-was-weer-echt-wit' => [
                'focus' => 'Witte was wordt grauw door vuilresten, te veel wasmiddel of gemengde kleuren.',
                'steps' => [
                    'Was wit apart van kleur en donkere was.',
                    'Doseer wasmiddel volgens de verpakking; te veel laat resten achter.',
                    'Gebruik een witwasmiddel met zuurstofbleekmiddel als de stof dat aankan.',
                    'Laat erg grauwe was vooraf weken in soda of zuurstofbleekmiddel.',
                    'Was handdoeken en beddengoed af en toe warmer als het label dat toestaat.',
                    'Maak filter en rubber van de wasmachine schoon tegen vuilresten.',
                ],
            ],
            'hoe-voorkom-ik-dat-zwarte-kleding-vaal-wordt' => [
                'focus' => 'Zwarte kleding blijft langer mooi door minder wrijving, lage temperatuur en binnenstebuiten wassen.',
                'steps' => [
                    'Was zwarte kleding binnenstebuiten.',
                    'Gebruik vloeibaar wasmiddel voor donkere was.',
                    'Was koud of op 30 graden.',
                    'Gebruik een kort of mild programma.',
                    'Laat zwarte kleding aan de lucht drogen uit direct zonlicht.',
                    'Was minder vaak als luchten voldoende is.',
                ],
            ],
            'hoe-haal-ik-zweetgeur-uit-sportkleding' => [
                'focus' => 'Sportkleding houdt geur vast doordat bacteriën en wasmiddelresten in synthetische vezels blijven zitten.',
                'steps' => [
                    'Laat sportkleding eerst drogen als je niet direct wast.',
                    'Week het 30 minuten in koud water met een scheut natuurazijn.',
                    'Was daarna met sportwasmiddel of weinig vloeibaar wasmiddel.',
                    'Gebruik geen wasverzachter; dat sluit geur juist in.',
                    'Droog aan de lucht en vermijd de droger bij elastische stoffen.',
                    'Draai af en toe een onderhoudswas voor je wasmachine.',
                ],
            ],
            'hoe-maak-ik-mijn-laptop-weer-sneller' => [
                'focus' => 'Een trage laptop wordt vaak veroorzaakt door volle opslag, te veel opstartprogramma’s of zware achtergrondprocessen.',
                'steps' => [
                    'Herstart eerst en installeer openstaande updates.',
                    'Verwijder programma’s die je niet gebruikt.',
                    'Zet onnodige opstartapps uit.',
                    'Maak opslagruimte vrij en leeg tijdelijke bestanden.',
                    'Controleer taakbeheer of activiteitenweergave op processen die veel CPU of geheugen gebruiken.',
                    'Maak een back-up voordat je reset of grote opschoningssoftware gebruikt.',
                ],
            ],
            'hoe-maak-ik-een-schermafbeelding-op-mijn-telefoon' => [
                'focus' => 'Een screenshot maken verschilt per toestel, maar meestal is het een combinatie van twee knoppen.',
                'steps' => [
                    'Op iPhone met Face ID: druk tegelijk op zijknop en volume omhoog.',
                    'Op iPhone met thuisknop: druk tegelijk op thuisknop en zijknop of bovenknop.',
                    'Op Android: druk meestal tegelijk op aan/uit en volume omlaag.',
                    'Houd de knoppen kort ingedrukt en laat los zodra het scherm flitst.',
                    'Open de melding of galerij om de screenshot te bewerken.',
                    'Gebruik de ingebouwde schermopname of scroll-screenshot als je een lange pagina wilt vastleggen.',
                ],
            ],
            'hoe-verleng-ik-de-batterijduur-van-mijn-telefoon' => [
                'focus' => 'Batterijduur verbeter je vooral met schermhelderheid, achtergrondapps en zwakke verbindingen beperken.',
                'steps' => [
                    'Zet schermhelderheid lager of gebruik automatische helderheid.',
                    'Schakel energiebesparing in als je lang met je batterij moet doen.',
                    'Beperk apps die op de achtergrond verversen.',
                    'Zet locatie uit voor apps die het niet nodig hebben.',
                    'Gebruik wifi waar mogelijk; slechte mobiele ontvangst kost veel batterij.',
                    'Vervang de batterij als de batterijconditie duidelijk slecht is.',
                ],
            ],
            'wat-kan-ik-doen-als-mijn-wifi-steeds-wegvalt' => [
                'focus' => 'Wifi die wegvalt komt vaak door afstand, storing, routerproblemen of een kanaal dat te druk is.',
                'steps' => [
                    'Herstart modem en router door ze 30 seconden van stroom te halen.',
                    'Test of het probleem op één apparaat of op alle apparaten speelt.',
                    'Zet de router vrij en hoger, niet achter metaal of in een kast.',
                    'Gebruik 5 GHz dichtbij de router en 2,4 GHz voor verder bereik.',
                    'Update routerfirmware als dat beschikbaar is.',
                    'Neem contact op met je provider als ook bekabeld internet wegvalt.',
                ],
            ],
            'hoe-verwijder-ik-ijs-van-mijn-voorruit-zonder-krassen' => [
                'focus' => 'IJs verwijder je veilig met ontdooier, ruitenkrabber en geduld; heet water kan glas laten barsten.',
                'steps' => [
                    'Start de auto en zet voorruitverwarming of blazer op de ruit.',
                    'Gebruik ruitontdooier of lauw water, nooit kokend water.',
                    'Gebruik een kunststof ruitenkrabber.',
                    'Krab in één richting en druk niet extreem hard.',
                    'Maak ook spiegels, lampen en zijruiten schoon.',
                    'Leg ’s avonds een voorruitdeken neer om ijsvorming te voorkomen.',
                ],
            ],
            'hoe-vaak-moet-ik-mijn-bandenspanning-controleren' => [
                'focus' => 'Goede bandenspanning scheelt brandstof, slijtage en remweg.',
                'steps' => [
                    'Controleer bandenspanning minimaal één keer per maand.',
                    'Check ook voor een lange rit of zware belading.',
                    'Meet bij koude banden voor de meest betrouwbare waarde.',
                    'Gebruik de spanning uit het instructieboekje of sticker in de deurstijl.',
                    'Vergeet het reservewiel niet als je dat hebt.',
                    'Laat banden controleren bij scheef afslijten of steeds drukverlies.',
                ],
            ],
            'hoe-krijg-ik-een-muffe-geur-uit-mijn-auto' => [
                'focus' => 'Muffe autogeur komt vaak uit vocht, airco, bekleding of vergeten afval.',
                'steps' => [
                    'Haal afval, matten en losse spullen uit de auto.',
                    'Stofzuig stoelen, vloer en kofferbak grondig.',
                    'Laat natte matten volledig drogen buiten de auto.',
                    'Reinig bekleding met geschikte textielreiniger.',
                    'Vervang of controleer het interieurfilter.',
                    'Laat de airco reinigen als de geur vooral uit de ventilatie komt.',
                ],
            ],
            'wat-moet-ik-checken-voor-een-lange-autorit' => [
                'focus' => 'Voor een lange rit wil je vooral banden, vloeistoffen, zicht en noodspullen controleren.',
                'steps' => [
                    'Controleer bandenspanning en profiel.',
                    'Check oliepeil, koelvloeistof en ruitensproeiervloeistof.',
                    'Test verlichting, remlichten en richtingaanwijzers.',
                    'Maak ruiten en spiegels schoon.',
                    'Neem gevarendriehoek, hesje, laadkabel en eventueel water mee.',
                    'Plan pauzes en check route, tol of milieuzones vooraf.',
                ],
            ],
            'hoe-schrijf-ik-een-goede-sollicitatiebrief' => [
                'focus' => 'Een goede sollicitatiebrief koppelt jouw ervaring direct aan wat de werkgever zoekt.',
                'steps' => [
                    'Begin met waarom juist deze functie en organisatie je aanspreken.',
                    'Noem twee of drie eisen uit de vacature en bewijs die met concrete voorbeelden.',
                    'Houd de brief kort: meestal één pagina.',
                    'Gebruik actieve taal en vermijd standaardzinnen zonder inhoud.',
                    'Sluit af met enthousiasme voor een gesprek.',
                    'Controleer naam, functietitel, spelling en bijlagen voordat je verstuurt.',
                ],
            ],
            'hoe-houd-ik-mijn-administratie-overzichtelijk' => [
                'focus' => 'Administratie wordt overzichtelijk als alles een vaste plek, naam en routine heeft.',
                'steps' => [
                    'Maak mappen voor inkomen, belasting, verzekeringen, wonen en zorg.',
                    'Geef bestanden namen met datum en onderwerp.',
                    'Plan elke week 15 minuten om post en mail te verwerken.',
                    'Bewaar belangrijke documenten ook digitaal.',
                    'Zet betaaldata en opzegtermijnen in je agenda.',
                    'Gooi of archiveer oude documenten volgens bewaartermijn.',
                ],
            ],
            'hoe-vraag-ik-netjes-om-salarisverhoging' => [
                'focus' => 'Een salarisgesprek werkt beter met bewijs, timing en een concreet voorstel.',
                'steps' => [
                    'Verzamel resultaten, extra verantwoordelijkheden en positieve feedback.',
                    'Vergelijk je salaris met marktgegevens voor vergelijkbare functies.',
                    'Plan een apart gesprek, niet tussen de bedrijven door.',
                    'Noem rustig welk bedrag of percentage je redelijk vindt.',
                    'Koppel je vraag aan waarde die je levert, niet alleen aan persoonlijke kosten.',
                    'Vraag bij een nee wat nodig is om het later wel te krijgen.',
                ],
            ],
            'hoe-maak-ik-een-simpel-maandbudget' => [
                'focus' => 'Een maandbudget werkt als je vaste lasten, variabele uitgaven en spaardoelen apart ziet.',
                'steps' => [
                    'Noteer je netto inkomen per maand.',
                    'Zet vaste lasten onder elkaar: huur, energie, verzekeringen, abonnementen.',
                    'Bepaal budgetten voor boodschappen, vervoer en vrije tijd.',
                    'Reserveer direct na inkomen een bedrag voor sparen of buffer.',
                    'Gebruik bankcategorieën of een simpele spreadsheet.',
                    'Controleer aan het einde van de maand waar je moest bijsturen.',
                ],
            ],
            'hoe-begin-ik-met-aquarelleren-als-beginner' => [
                'focus' => 'Aquarel leer je het snelst met weinig materiaal en veel kleine oefeningen.',
                'steps' => [
                    'Koop aquarelpapier, een basisset verf en twee penselen.',
                    'Oefen eerst kleurverlopen en watercontrole.',
                    'Werk licht naar donker; wit komt meestal van het papier.',
                    'Laat lagen drogen voordat je eroverheen schildert.',
                    'Begin met simpele onderwerpen zoals bladeren, lucht of fruit.',
                    'Bewaar oefenvellen zodat je ziet wat water en pigment doen.',
                ],
            ],
            'hoe-bewaar-ik-mijn-breiwerk-netjes' => [
                'focus' => 'Breiwerk blijft netjes als het schoon, droog en zonder spanning wordt bewaard.',
                'steps' => [
                    'Vouw breiwerk op in plaats van het op te hangen.',
                    'Bewaar het droog en uit direct zonlicht.',
                    'Gebruik een katoenen zak of ademende doos.',
                    'Leg mottenwering zoals cederhout bij wol.',
                    'Bewaar lopend werk met stekenstopper of kabelnaald zodat steken niet vallen.',
                    'Was of lucht kleding voordat je het lang opbergt.',
                ],
            ],
            'hoe-voorkom-ik-luchtbellen-bij-epoxy' => [
                'focus' => 'Luchtbellen in epoxy voorkom je door rustig mengen, juiste temperatuur en dunne lagen.',
                'steps' => [
                    'Meet hars en harder nauwkeurig af.',
                    'Meng langzaam zodat je weinig lucht inslaat.',
                    'Laat het mengsel kort staan zodat bellen opstijgen.',
                    'Giet in dunne lagen als het project dat toelaat.',
                    'Gebruik voorzichtig een heat gun of brander om oppervlakbellen te laten knappen.',
                    'Werk stofvrij en volg de verwerkingstijd van het product.',
                ],
            ],
            'hoe-organiseer-ik-mijn-hobbyspullen-zonder-veel-ruimte' => [
                'focus' => 'Bij weinig ruimte draait het om sorteren, verticaal opbergen en spullen zichtbaar houden.',
                'steps' => [
                    'Sorteer op hobby en gooi dubbele of uitgedroogde spullen weg.',
                    'Gebruik doorzichtige bakken met labels.',
                    'Bewaar klein materiaal in ladebakjes of etuis.',
                    'Gebruik wandplanken, deurhangers of stapelboxen.',
                    'Maak één draagbare projectbox voor waar je nu mee bezig bent.',
                    'Ruim na elk project restmateriaal direct terug op.',
                ],
            ],
            'welke-oude-truc-helpt-tegen-zilver-dat-zwart-is-geworden' => [
                'focus' => 'Zwart zilver kun je vaak opfrissen met aluminiumfolie, soda en heet water, maar wees voorzichtig met stenen of antieke afwerking.',
                'steps' => [
                    'Bekleed een schaal met aluminiumfolie.',
                    'Leg het zilver erin met contact met de folie.',
                    'Voeg heet water en een eetlepel soda toe.',
                    'Laat enkele minuten liggen en draai indien nodig om.',
                    'Spoel goed af en droog met een zachte doek.',
                    'Gebruik dit niet zomaar bij sieraden met parels, stenen of gelijmde onderdelen.',
                ],
            ],
            'hoe-laat-ik-mijn-huis-snel-fris-ruiken-zonder-parfum' => [
                'focus' => 'Fris ruiken zonder parfum begint met geurbronnen verwijderen en lucht verversen.',
                'steps' => [
                    'Zet ramen 10 minuten tegenover elkaar open.',
                    'Leeg prullenbakken en check keukenafval.',
                    'Stofzuig vloeren en textiel waar geur in blijft hangen.',
                    'Zet een bakje baking soda neer bij muffe plekken.',
                    'Kook kort water met citroenschil of kaneel als subtiele geur.',
                    'Was plaids, kussenslopen of hondenmanden als die de geur vasthouden.',
                ],
            ],
            'hoe-maak-ik-een-verstopte-afvoer-op-een-milde-manier-vrij' => [
                'focus' => 'Een milde aanpak werkt vooral bij zeep, vet en haren; chemische ontstopper is vaak niet nodig.',
                'steps' => [
                    'Haal zichtbaar haar of vuil bij het putje weg.',
                    'Giet een ketel heet maar niet kokend water door de afvoer.',
                    'Gebruik een plopper om druk op te bouwen.',
                    'Strooi baking soda in de afvoer en giet azijn erachteraan.',
                    'Laat 15 minuten bruisen en spoel na met heet water.',
                    'Bel een loodgieter bij terugkerende verstopping of water dat helemaal niet wegloopt.',
                ],
            ],
            'welke-ouderwetse-truc-helpt-tegen-fruitvliegjes' => [
                'focus' => 'Fruitvliegjes verdwijnen pas echt als je broedplekken weghaalt en volwassen vliegjes vangt.',
                'steps' => [
                    'Gooi overrijp fruit weg of bewaar fruit in de koelkast.',
                    'Maak aanrecht, prullenbak en lege flessen schoon.',
                    'Zet een kommetje appelazijn met een druppel afwasmiddel neer.',
                    'Dek eventueel af met folie met kleine gaatjes.',
                    'Spoel gootsteen en afvoer goed door.',
                    'Herhaal een paar dagen, want eitjes kunnen nog uitkomen.',
                ],
            ],
            'hoe-bespaar-ik-energie-in-huis-zonder-grote-investering' => [
                'focus' => 'Zonder grote investering bespaar je vooral met verwarming, tocht, warm water en sluipverbruik.',
                'steps' => [
                    'Zet de thermostaat één graad lager en verwarm alleen kamers die je gebruikt.',
                    'Plaats tochtstrips bij deuren en ramen.',
                    'Gebruik radiatorfolie achter radiatoren tegen buitenmuren.',
                    'Douche korter en gebruik een waterbesparende douchekop.',
                    'Zet apparaten echt uit in plaats van standby.',
                    'Was op 30 graden en droog was aan een rek wanneer dat kan.',
                ],
            ],
            'hoe-scheid-ik-mijn-afval-het-beste' => [
                'focus' => 'Goed afval scheiden begint met duidelijke bakken en weten wat lokaal wel of niet mag.',
                'steps' => [
                    'Check de afvalregels van je gemeente.',
                    'Zet aparte bakken neer voor papier, plastic/pmd, glas, gft en rest.',
                    'Maak verpakkingen leeg, maar spoel niet overdreven met warm water.',
                    'Gooi vet, batterijen, lampen en elektronica apart weg.',
                    'Twijfel je? Kies restafval om vervuiling van recycle-stromen te voorkomen.',
                    'Maak het makkelijk op de plek waar afval ontstaat, zoals keuken en badkamer.',
                ],
            ],
            'hoe-gebruik-ik-minder-water-onder-de-douche' => [
                'focus' => 'Douchewater bespaar je met tijd, doorstroming en gewoontes.',
                'steps' => [
                    'Gebruik een timer van 5 minuten.',
                    'Zet de douche uit tijdens inzepen of haren wassen.',
                    'Plaats een waterbesparende douchekop.',
                    'Repareer lekkende kranen of douchekoppen.',
                    'Douche iets minder warm zodat je minder lang blijft staan.',
                    'Meet een week je doucheduur zodat je vooruitgang ziet.',
                ],
            ],
            'hoe-begin-ik-met-spullen-repareren-in-plaats-van-weggooien' => [
                'focus' => 'Begin met simpele reparaties waar weinig risico aan zit en bouw vaardigheid op.',
                'steps' => [
                    'Kies één klein item, zoals kleding, stoelviltje, kabelhouder of losse schroef.',
                    'Zoek merk, model en probleem online op.',
                    'Leg basisgereedschap klaar: schroevendraaiers, lijm, naald, draad en tang.',
                    'Maak foto’s tijdens demonteren zodat je weet hoe het terug moet.',
                    'Koop alleen onderdelen als de reparatie goedkoper en veilig is.',
                    'Ga niet zelf aan netstroom of accu’s werken als je niet weet wat je doet.',
                ],
            ],
            'hoe-vouw-ik-een-fitted-hoeslaken-netjes-op' => [
                'focus' => 'Een hoeslaken wordt netjes als je de elastische hoeken eerst in elkaar stopt.',
                'steps' => [
                    'Houd het hoeslaken met de binnenkant naar je toe bij twee hoeken vast.',
                    'Stop één hoek over de andere.',
                    'Pak de derde en vierde hoek en stop die er ook overheen.',
                    'Leg het laken plat neer met de elastische rand naar binnen.',
                    'Vouw de zijkanten naar binnen tot je een rechthoek hebt.',
                    'Vouw daarna in helften of derden tot het in de kast past.',
                ],
            ],
            'hoe-verwijder-ik-een-vastzittende-ritssluiting' => [
                'focus' => 'Een vastzittende rits moet je smeren en voorzichtig vrijmaken, niet hard lostrekken.',
                'steps' => [
                    'Kijk of er stof tussen de tanden zit en trek die voorzichtig los.',
                    'Beweeg de rits langzaam een klein stukje terug.',
                    'Wrijf met potloodgrafiet, kaarsvet of zeep langs de tanden.',
                    'Beweeg de rits korte stukjes heen en weer.',
                    'Gebruik een pincet voor draadjes, maar trek niet aan de tanden.',
                    'Laat de rits vervangen als tanden verbogen of ontbrekend zijn.',
                ],
            ],
            'hoe-krijg-ik-plakresten-van-een-potje-af' => [
                'focus' => 'Plakresten lossen vaak op met warmte, olie of alcohol afhankelijk van de lijm.',
                'steps' => [
                    'Week het potje in warm water met afwasmiddel.',
                    'Trek het etiket eraf zodra de lijm zacht is.',
                    'Wrijf restjes in met olie en laat 10 minuten zitten.',
                    'Poets los met een doek of zachte spons.',
                    'Gebruik eventueel alcohol of stickerverwijderaar voor hardnekkige lijm.',
                    'Was het potje daarna goed af als je het voor eten gebruikt.',
                ],
            ],
            'hoe-pak-ik-een-koffer-slim-in-voor-een-weekend-weg' => [
                'focus' => 'Slim inpakken betekent combineren, beperken en zware spullen handig plaatsen.',
                'steps' => [
                    'Kies outfits die onderling combineren in plaats van losse setjes.',
                    'Neem één extra bovenstuk mee, niet meerdere noodoutfits.',
                    'Rol kleding of gebruik packing cubes.',
                    'Stop sokken en kleine spullen in schoenen.',
                    'Doe toiletspullen in een lekvrij zakje.',
                    'Leg zware spullen onderin bij de wielen en houd documenten of oplader bereikbaar.',
                ],
            ],
        ];

        return $advice[$slug] ?? null;
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
            'ai-trucjes' => [
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
