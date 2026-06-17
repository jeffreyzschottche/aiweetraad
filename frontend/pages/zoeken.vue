<template>
  <div class="container-app py-10">
    <div class="text-center">
      <h1 class="font-display text-3xl font-bold text-brand-900 md:text-4xl">🔎 Zoeken</h1>
      <p class="mt-2 text-ink/60">Vind snel het antwoord dat je zoekt.</p>
    </div>

    <form class="mx-auto mt-6 flex max-w-xl flex-col gap-2 sm:flex-row" @submit.prevent="search">
      <input
        v-model="q"
        type="text"
        placeholder="Zoek een vraag…"
        class="field rounded-full px-6 py-3.5 shadow-soft"
      />
      <button type="submit" class="btn-primary px-8">Zoek</button>
    </form>

    <div v-if="loading" class="mt-10 text-center text-ink/40">Zoeken…</div>

    <div v-else-if="results.length" v-stagger class="mt-8 grid gap-4 sm:grid-cols-2">
      <QuestionCard v-for="r in results" :key="r.id" :question="r" />
    </div>

    <div v-else-if="searched" class="mx-auto mt-10 max-w-xl rounded-3xl border-2 border-dashed border-brand-100 p-10 text-center">
      <div class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-full bg-blush-200 text-3xl">🤷</div>
      <p class="text-ink/60">Geen resultaten voor “{{ lastQuery }}”.</p>
      <NuxtLink :to="{ path: '/vraag-stellen', query: { q: lastQuery } }" class="btn-primary mt-4">
        Stel deze vraag aan de AI’s
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Paginated, Question } from '~/types/content';

const route = useRoute();
const router = useRouter();
const api = useApi();

const q = ref((route.query.q as string) || '');
const results = ref<Question[]>([]);
const loading = ref(false);
const searched = ref(false);
const lastQuery = ref('');

async function search() {
  if (!q.value.trim()) return;
  loading.value = true;
  searched.value = true;
  lastQuery.value = q.value;
  router.replace({ query: { q: q.value } });
  try {
    const res = await api.get<Paginated<Question>>(`/questions?q=${encodeURIComponent(q.value)}`);
    results.value = res.data;
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  if (q.value) search();
});

usePageSeo(() => ({
  title: lastQuery.value ? `Zoeken naar ${lastQuery.value}` : 'Zoeken',
  description: lastQuery.value
    ? `Zoekresultaten voor ${lastQuery.value} op AI Weet Raad.`
    : 'Zoek in praktische vragen en AI-antwoorden op AI Weet Raad.',
  path: '/zoeken',
}));

const { absoluteUrl } = useSiteIdentity();

useJsonLd('search-page', () => ({
  '@context': 'https://schema.org',
  '@type': 'SearchResultsPage',
  '@id': `${absoluteUrl('/zoeken')}#webpage`,
  url: absoluteUrl('/zoeken'),
  name: lastQuery.value ? `Zoeken naar ${lastQuery.value}` : 'Zoeken',
  description: 'Zoek in praktische vragen en AI-antwoorden.',
  mainEntity: {
    '@type': 'ItemList',
    itemListElement: results.value.map((question, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: question.title,
      url: absoluteUrl(`/vraag/${question.slug}`),
    })),
  },
}));
</script>
