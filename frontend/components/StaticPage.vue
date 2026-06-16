<template>
  <div class="container-app grid gap-10 py-10 lg:grid-cols-[1fr_300px]">
    <article class="mx-auto w-full max-w-3xl">
      <h1 class="font-display text-3xl font-extrabold text-brand-900">{{ page.title }}</h1>
      <!-- Content comes from our own trusted seeder/CMS, rendered as HTML. -->
      <div class="prose-content mt-6" v-html="page.body" />

      <div v-if="props.slug === 'adverteren'" class="mt-8 grid gap-4 sm:grid-cols-3">
        <div v-for="option in adOptions" :key="option.title" class="rounded-3xl border border-brand-100 bg-white p-5 shadow-card">
          <p class="text-sm font-bold text-brand-700">{{ option.title }}</p>
          <p class="mt-1 text-sm text-ink/60">{{ option.copy }}</p>
        </div>
      </div>
    </article>
    <aside class="space-y-6">
      <AdSlot format="sidebar" />
    </aside>
  </div>
</template>

<script setup lang="ts">
import type { Page } from '~/types/content';

const props = defineProps<{ slug: string }>();
const api = useApi();

const { data } = await useAsyncData(`page-${props.slug}`, () =>
  api.get<{ data: Page }>(`/pages/${props.slug}`).catch(() => null)
);
const page = computed(() => data.value?.data ?? fallbackPages[props.slug] ?? fallbackPages['over-ons']);

const fallbackPages: Record<string, Page> = {
  'over-ons': {
    id: 0,
    slug: 'over-ons',
    title: 'Over AI Weet Raad',
    meta_description: 'Wat is AI Weet Raad en hoe werkt het?',
    body: `
      <p><strong>AI Weet Raad</strong> verzamelt praktische antwoorden van meerdere AI-assistenten op alledaagse vragen. Je ziet niet één antwoord, maar meerdere invalshoeken naast elkaar.</p>
      <h2>Zo werkt het</h2>
      <p>Je stelt een concrete vraag, kiest eventueel een categorie en krijgt daarna antwoorden van ChatGPT, Claude, Gemini, Grok en DeepSeek. Elk model heeft een eigen manier van redeneren: de één structureert sterk, de ander nuanceert beter of geeft juist een analytische tegencheck.</p>
      <p>Gebruikers kunnen stemmen op antwoorden die werken. Daardoor ontstaat per vraag én per AI een praktischer beeld dan wanneer je één losse chatbotreactie leest.</p>
      <h2>Waarvoor gebruik je AI Weet Raad?</h2>
      <p>Voor schoonmaken, vlekken, koken, huisdieren, gezondheid, tuin, kleding, elektronica en alle kleine problemen waar je snel een bruikbaar startpunt voor wilt.</p>
      <h2>Belangrijk om te weten</h2>
      <p>AI-antwoorden zijn hulpmiddelen, geen garanties. Controleer advies altijd zelf, zeker bij medische, juridische, financiële of veiligheidsgevoelige onderwerpen.</p>
    `,
  },
  adverteren: {
    id: 0,
    slug: 'adverteren',
    title: 'Adverteren op AI Weet Raad',
    meta_description: 'Bereik bezoekers die actief zoeken naar praktische oplossingen.',
    body: `
      <p>AI Weet Raad bereikt bezoekers op het moment dat ze een concreet probleem willen oplossen. Dat maakt de omgeving geschikt voor merken, webshops en diensten die praktisch advies, producten of lokale hulp aanbieden.</p>
      <h2>Beschikbare posities</h2>
      <p>We ondersteunen een leaderboard bovenaan de pagina, native blokken tussen antwoorden en een sidebarpositie voor grotere campagnes. De advertentieplekken zijn al in de layout voorbereid.</p>
      <h2>Waarom dit interessant is</h2>
      <p>Bezoekers komen binnen met duidelijke intentie: ze zoeken een oplossing voor een vlek, aankoop, klus, klacht, huisdierprobleem of dagelijkse vraag. Relevante advertenties sluiten daardoor beter aan dan brede displaycampagnes.</p>
      <h2>Voor wie is dit interessant?</h2>
      <p>Denk aan schoonmaakproducten, drogisterij, huis en tuin, huisdieren, duurzame oplossingen, keukenapparatuur, verzekeringen en services rondom wonen of gezondheid.</p>
      <h2>Formaten en samenwerking</h2>
      <p>We denken mee over vaste posities, tijdelijke campagnes, native content en categoriegerichte zichtbaarheid. Neem contact op via de contactpagina voor tarieven, beschikbaarheid en formats.</p>
    `,
  },
  'privacy-policy': {
    id: 0,
    slug: 'privacy-policy',
    title: 'Privacybeleid',
    meta_description: 'Hoe AI Weet Raad omgaat met gegevens.',
    body: `
      <p>We bewaren alleen gegevens die nodig zijn om AI Weet Raad te laten werken: accountgegevens, gestelde vragen, antwoorden en stemmen op antwoorden.</p>
      <h2>Account en activiteit</h2>
      <p>Als je ingelogd bent, koppelen we je vragen en stemmen aan je profiel. Zo kun je later terugvinden wat je hebt gevraagd en welke adviezen voor jou bruikbaar waren.</p>
      <h2>AI-verwerking</h2>
      <p>Wanneer je een vraag stelt, kan de inhoud naar de gebruikte AI-providers worden gestuurd om antwoorden te genereren. Deel daarom geen wachtwoorden, burgerservicenummers, betaalgegevens of andere gevoelige informatie in je vraag.</p>
      <h2>Cookies en technische gegevens</h2>
      <p>We gebruiken technische gegevens om de site veilig en bruikbaar te houden. Advertentiepartners kunnen eigen cookies gebruiken wanneer echte advertentietags worden toegevoegd.</p>
      <h2>Je rechten</h2>
      <p>Je kunt vragen om inzage, correctie of verwijdering van gegevens die aan jouw account gekoppeld zijn. Gebruik daarvoor de contactpagina.</p>
    `,
  },
  voorwaarden: {
    id: 0,
    slug: 'voorwaarden',
    title: 'Algemene voorwaarden',
    meta_description: 'De gebruiksvoorwaarden van AI Weet Raad.',
    body: `
      <p>AI-antwoorden zijn bedoeld als algemene informatie en praktische inspiratie. Ze vervangen geen professioneel medisch, juridisch of financieel advies.</p>
      <h2>Gebruik</h2>
      <p>Gebruik de site respectvol en plaats geen onrechtmatige, schadelijke, beledigende, spamachtige of misleidende inhoud. Vragen kunnen worden geweigerd wanneer ze niet passen bij normaal gebruik.</p>
      <h2>Eigen verantwoordelijkheid</h2>
      <p>Controleer altijd of een advies past bij jouw situatie. Bij twijfel raadpleeg je een deskundige. Het opvolgen van advies gebeurt op eigen risico.</p>
      <h2>Accounts en limieten</h2>
      <p>Voor het stellen van nieuwe AI-vragen en stemmen op antwoorden is een account nodig. We kunnen daglimieten toepassen om misbruik en onnodige API-kosten te voorkomen.</p>
      <h2>Beschikbaarheid</h2>
      <p>We proberen AI Weet Raad stabiel te houden, maar kunnen niet garanderen dat de site, AI-providers of alle antwoorden altijd beschikbaar zijn.</p>
    `,
  },
};

const adOptions = [
  { title: 'Leaderboard', copy: 'Horizontale zichtbaarheid direct onder de header.' },
  { title: 'In-content', copy: 'Tussen AI-antwoorden, midden in de aandacht.' },
  { title: 'Sidebar', copy: 'Grote positie naast categorie- en vraagpagina’s.' },
];

useHead(() => ({
  title: page.value.title,
  meta: page.value.meta_description
    ? [{ name: 'description', content: page.value.meta_description }]
    : [],
}));
</script>
