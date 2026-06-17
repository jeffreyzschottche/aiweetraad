<template>
  <div class="container-app grid gap-10 py-10 lg:grid-cols-[1fr_300px]">
    <div class="mx-auto w-full max-w-2xl">
      <div v-reveal class="text-center">
        <div class="mx-auto mb-3 grid h-16 w-16 place-items-center rounded-3xl bg-blush-200 text-3xl shadow-card">💬</div>
        <h1 class="font-display text-3xl font-bold text-brand-900 md:text-4xl">Stel je vraag</h1>
        <p class="mt-2 text-ink/60">
          Typ je vraag en onze AI’s geven elk hun eigen antwoord. Daarna stem je op het beste advies.
        </p>
      </div>

      <div v-reveal="{ delay: 0.1 }" class="mt-6">
        <AskForm :initial-title="initialTitle" />
      </div>

      <ol v-stagger class="mt-8 grid gap-4 sm:grid-cols-3">
        <li v-for="(step, i) in steps" :key="i" class="card p-5 text-center">
          <span class="mx-auto grid h-10 w-10 place-items-center rounded-full bg-brand-600 text-sm font-bold text-white">
            {{ i + 1 }}
          </span>
          <p class="mt-3 text-sm font-semibold text-ink/70">{{ step }}</p>
        </li>
      </ol>
    </div>

    <aside class="space-y-6">
      <AdSlot format="sidebar" />
    </aside>
  </div>
</template>

<script setup lang="ts">
const route = useRoute();
const initialTitle = computed(() => (route.query.q as string) || '');

const steps = [
  'Typ je vraag zo duidelijk mogelijk.',
  'De AI’s genereren direct hun antwoorden.',
  'Like of dislike het advies dat het beste werkt.',
];

usePageSeo(() => ({
  title: initialTitle.value ? `Stel je vraag: ${initialTitle.value}` : 'Stel je vraag',
  description: 'Stel je vraag aan meerdere AI’s tegelijk en vergelijk direct de antwoorden.',
  path: '/vraag-stellen',
}));

const { absoluteUrl } = useSiteIdentity();

useJsonLd('ask-page', () => ({
  '@context': 'https://schema.org',
  '@type': 'WebPage',
  '@id': `${absoluteUrl('/vraag-stellen')}#webpage`,
  url: absoluteUrl('/vraag-stellen'),
  name: 'Stel je vraag',
  description: 'Stel een praktische vraag aan meerdere AI-assistenten tegelijk.',
}));
</script>
