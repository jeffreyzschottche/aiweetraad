<template>
  <div>
    <!-- Hero -->
    <section class="relative overflow-hidden border-b border-brand-100/60 bg-gradient-to-b from-blush-100 via-cream to-cream">
      <FloatingBlobs />
      <div ref="hero" class="container-app relative z-10 py-16 text-center md:py-24">
        <p ref="badge" class="hero-reveal mb-4 inline-flex items-center gap-2 rounded-full border border-brand-100 bg-white px-4 py-1.5 text-xs font-bold text-brand-700 shadow-card">
          <span class="flex -space-x-1">
            <span v-for="m in heroDots" :key="m" class="h-3.5 w-3.5 rounded-full ring-2 ring-white" :style="{ backgroundColor: m }" />
          </span>
          Gratis · {{ aiCount }} AI’s · <CountUp :value="home?.stats.questions ?? 0" /> vragen beantwoord
        </p>

        <h1 ref="title" class="mx-auto flex max-w-3xl flex-wrap justify-center gap-x-3 gap-y-1 text-4xl font-bold leading-[1.1] text-brand-900 md:text-6xl">
          <span class="hero-word inline-block">Stel</span>
          <span class="hero-word inline-block">je</span>
          <span class="hero-word inline-block">vraag</span>
          <span class="hero-word inline-block">aan</span>
          <span class="hero-word relative inline-block text-brand-600">
            meerdere&nbsp;AI’s
            <svg class="absolute -bottom-2 left-0 w-full" height="12" viewBox="0 0 200 12" fill="none" preserveAspectRatio="none">
              <path d="M2 8c40-6 120-6 196 0" stroke="#f9c6c5" stroke-width="5" stroke-linecap="round" />
            </svg>
          </span>
          <span class="hero-word inline-block">tegelijk</span>
        </h1>

        <p ref="subtitle" class="hero-reveal mx-auto mt-6 max-w-xl text-lg text-ink/70">
          Gratis met een account: stel je vragen, vergelijk meerdere AI-antwoorden en stem op het
          advies dat jou het beste helpt. 🤍
        </p>

        <form ref="searchForm" class="hero-reveal mx-auto mt-8 flex max-w-xl flex-col gap-2 sm:flex-row" @submit.prevent="goAsk">
          <input
            v-model="quickQuestion"
            type="text"
            placeholder="Waar kunnen de AI’s je mee helpen?"
            class="field min-w-0 rounded-full px-6 py-4 shadow-soft"
          />
          <button type="submit" class="btn-primary min-w-[160px] shrink-0 px-8 py-4">
            <span class="whitespace-nowrap">Vraag het ✨</span>
          </button>
        </form>

        <div ref="stats" class="hero-reveal mx-auto mt-10 flex max-w-md justify-center gap-8 text-center">
          <div v-for="s in statBlocks" :key="s.label">
            <p class="font-display text-2xl font-bold text-brand-700">
              <CountUp :value="s.value" />{{ s.suffix || '' }}
            </p>
            <p class="text-xs font-semibold uppercase tracking-wide text-ink/50">{{ s.label }}</p>
          </div>
        </div>
        <div ref="aiStrip" class="hero-reveal mx-auto mt-7 flex max-w-xl flex-wrap justify-center gap-5">
          <div
            v-for="ai in aiProfiles"
            :key="`hero-${ai.name}`"
            class="flex w-20 flex-col items-center gap-2"
          >
            <span
              class="grid h-14 w-14 place-items-center rounded-full border-2 border-cream shadow-card"
              :class="ai.needsWhiteBg ? 'bg-white' : 'bg-transparent'"
            >
              <img
                :src="ai.logo"
                :alt="`${ai.name} logo`"
                class="rounded-full object-contain"
                :class="aiLogoClass(ai, 'hero')"
              />
            </span>
            <span class="text-xs font-extrabold text-brand-700">{{ ai.name }}</span>
          </div>
        </div>
      </div>
    </section>

    <div class="container-app grid gap-10 py-12 lg:grid-cols-[1fr_300px]">
      <div class="space-y-14">
        <!-- Categories -->
        <section>
          <div class="mb-6 flex items-end justify-between">
            <div>
              <h2 class="font-display text-2xl font-bold text-brand-900">Categorieën</h2>
              <p class="text-sm text-ink/60">Kies een onderwerp en blader door de vragen.</p>
            </div>
            <NuxtLink to="/categorieen" class="text-sm font-bold text-brand-600 hover:text-brand-700">
              Alle →
            </NuxtLink>
          </div>
          <div v-stagger class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <CategoryCard v-for="cat in home?.categories?.slice(0, 9)" :key="cat.id" :category="cat" />
          </div>
        </section>

        <!-- AI mix -->
        <section>
          <div class="mb-6 max-w-2xl">
            <h2 class="font-display text-2xl font-bold text-brand-900">Waarom deze 5 AI’s?</h2>
            <p class="mt-1 text-sm text-ink/60">
              Elke AI redeneert net anders. Door ChatGPT, Claude, Gemini, Grok en DeepSeek naast elkaar te zetten zie je sneller welk advies praktisch, volledig en bruikbaar is.
            </p>
          </div>
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            <article
              v-for="ai in aiProfiles"
              :key="ai.name"
              class="flex min-h-[220px] min-w-0 flex-col rounded-2xl border border-brand-100 bg-white p-5 shadow-card xl:p-4 2xl:p-5"
            >
              <div class="flex min-w-0 flex-wrap items-start justify-between gap-3">
                <span
                  class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl ring-1 ring-black/5"
                  :class="ai.needsWhiteBg ? 'bg-white' : 'bg-transparent'"
                >
                  <img
                    :src="ai.logo"
                    :alt="`${ai.name} logo`"
                    class="rounded-xl object-contain"
                    :class="aiLogoClass(ai, 'card')"
                  />
                </span>
                <span class="max-w-full rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-extrabold leading-none text-brand-600">
                  {{ ai.role }}
                </span>
              </div>
              <h3 class="mt-4 font-display text-xl font-bold text-brand-900">{{ ai.name }}</h3>
              <p class="mt-2 text-sm leading-relaxed text-ink/65">{{ ai.copy }}</p>
            </article>
          </div>
        </section>

        <!-- Popular -->
        <section>
          <h2 class="mb-6 font-display text-2xl font-bold text-brand-900">🔥 Populaire vragen</h2>
          <div v-if="displayPopular.length" class="grid gap-4 sm:grid-cols-2">
            <QuestionCard v-for="q in displayPopular" :key="q.id" :question="q" />
          </div>
          <p v-else class="rounded-2xl border border-brand-100 bg-white p-5 text-sm font-semibold text-ink/55 shadow-card">
            Populaire vragen worden geladen zodra de API bereikbaar is.
          </p>
        </section>

        <!-- Latest -->
        <section>
          <h2 class="mb-6 font-display text-2xl font-bold text-brand-900">🆕 Nieuwste vragen</h2>
          <div v-if="displayLatest.length" class="grid gap-4 sm:grid-cols-2">
            <QuestionCard v-for="q in displayLatest" :key="q.id" :question="q" />
          </div>
          <p v-else class="rounded-2xl border border-brand-100 bg-white p-5 text-sm font-semibold text-ink/55 shadow-card">
            Nieuwste vragen worden geladen zodra de API bereikbaar is.
          </p>
        </section>
      </div>

      <!-- Sidebar -->
      <aside class="space-y-6">
        <div v-reveal class="card overflow-hidden p-6 text-center">
          <div class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-full bg-blush-200 text-3xl">💬</div>
          <h3 class="font-display text-lg font-bold text-brand-900">Heb jij ook een vraag?</h3>
          <p class="mt-1 text-sm text-ink/60">Krijg direct antwoord van alle AI’s.</p>
          <NuxtLink to="/vraag-stellen" class="btn-primary mt-4 w-full">Stel je vraag</NuxtLink>
        </div>
        <AiLeaderboard v-if="leaderboard.length" :models="leaderboard" />
        <NewsletterSignup />
        <AdSlot format="sidebar" />
      </aside>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { AiModel, Category, Question } from '~/types/content';

interface HomePayload {
  categories: Category[];
  latest: Question[];
  popular: Question[];
  stats: { questions: number; answers: number; categories: number };
}

interface AiProfile {
  name: string;
  role: string;
  logo: string;
  needsWhiteBg: boolean;
  copy: string;
}

const api = useApi();
const router = useRouter();
const { $gsap } = useNuxtApp();
const { absoluteUrl, siteUrl } = useSiteIdentity();
const quickQuestion = ref('');

const { data: home } = await useAsyncData('home', () => api.get<HomePayload>('/home'));
const { data: leaderboardData } = await useAsyncData('leaderboard', () =>
  api.get<{ data: AiModel[] }>('/ai-models/leaderboard')
);
const { data: popularFallbackData } = await useAsyncData('home-popular-fallback', () =>
  api.get<{ data: Question[] }>('/questions?sort=popular')
);
const { data: latestFallbackData } = await useAsyncData('home-latest-fallback', () =>
  api.get<{ data: Question[] }>('/questions?sort=recent')
);

const leaderboard = computed(() => leaderboardData.value?.data ?? []);
const aiCount = computed(() => leaderboard.value.length || 5);
const displayPopular = computed(() =>
  home.value?.popular?.length ? home.value.popular : (popularFallbackData.value?.data ?? []).slice(0, 6)
);
const displayLatest = computed(() =>
  home.value?.latest?.length ? home.value.latest : (latestFallbackData.value?.data ?? []).slice(0, 8)
);
const heroDots = computed(() =>
  (leaderboard.value.length ? leaderboard.value : []).slice(0, 5).map((m) => m.accent_color)
);

const statBlocks = computed(() => [
  { label: 'Vragen', value: home.value?.stats.questions ?? 0 },
  { label: 'Antwoorden', value: home.value?.stats.answers ?? 0 },
  { label: 'Categorieën', value: home.value?.stats.categories ?? 0 },
]);

const aiProfiles: AiProfile[] = [
  {
    name: 'ChatGPT',
    role: 'Structuur',
    logo: '/images/chatgpt.png',
    needsWhiteBg: true,
    copy: 'Maakt antwoorden overzichtelijk met heldere stappen, samenvattingen en praktische checklists.',
  },
  {
    name: 'Claude',
    role: 'Nuance',
    logo: '/images/claudelogo.png',
    needsWhiteBg: true,
    copy: 'Sterk in zorgvuldige afwegingen, nuance en rustige uitleg bij vragen waar context telt.',
  },
  {
    name: 'Gemini',
    role: 'Vergelijken',
    logo: '/images/geminilogo.png',
    needsWhiteBg: true,
    copy: 'Geeft vlot bruikbare antwoorden en legt makkelijk verbanden tussen verschillende invalshoeken.',
  },
  {
    name: 'Grok',
    role: 'Tegenstem',
    logo: '/images/groklogo.png',
    needsWhiteBg: true,
    copy: 'Bekijkt vragen direct, scherp en nuchter, met ruimte voor een eigenzinnige praktische insteek.',
  },
  {
    name: 'DeepSeek',
    role: 'Analyse',
    logo: '/images/deepseek.png',
    needsWhiteBg: true,
    copy: 'Blinkt uit in logisch redeneren, compact analyseren en opties tegen elkaar afwegen.',
  },
];

function aiLogoClass(ai: AiProfile, placement: 'hero' | 'card'): string {
  if (ai.name === 'ChatGPT') {
    return placement === 'hero' ? 'h-11 w-11 p-2' : 'h-11 w-11 p-2';
  }

  return ai.needsWhiteBg ? 'h-10 w-10 p-1' : 'h-10 w-10';
}

usePageSeo({
  title: 'Stel je vraag aan meerdere AI’s tegelijk',
  description:
    'Stel gratis je vragen en vergelijk antwoorden van ChatGPT, Claude, Gemini, Grok en DeepSeek. Stem op het advies dat jou het beste helpt.',
  path: '/',
});

useJsonLd('home-page', () => {
  return {
    '@context': 'https://schema.org',
    '@type': 'WebPage',
    '@id': `${siteUrl}/#webpage`,
    url: siteUrl,
    name: 'AI Weet Raad',
    description:
      'Stel gratis je vragen en vergelijk antwoorden van meerdere AI-assistenten.',
    primaryImageOfPage: absoluteUrl('/images/aiweetraadlogo.png'),
  };
});

const badge = ref();
const hero = ref<HTMLElement | null>(null);
const title = ref<HTMLElement | null>(null);
const subtitle = ref();
const searchForm = ref();
const stats = ref();
const aiStrip = ref();

onMounted(() => {
  const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
  if (!$gsap || reduced) {
    hero.value?.querySelectorAll('.hero-reveal').forEach((el) => ((el as HTMLElement).style.opacity = '1'));
    return;
  }

  $gsap.set(hero.value?.querySelectorAll('.hero-reveal') ?? [], { opacity: 1 });

  const tl = $gsap.timeline({ defaults: { ease: 'power3.out' } });
  tl.fromTo(badge.value, { opacity: 0, y: 16 }, { opacity: 1, y: 0, duration: 0.5, clearProps: 'transform' })
    .fromTo(
      title.value?.querySelectorAll('.hero-word') ?? [],
      { opacity: 0, y: 26, rotateX: -40 },
      { opacity: 1, y: 0, rotateX: 0, stagger: 0.07, duration: 0.6, clearProps: 'transform' },
      '-=0.2'
    )
    .fromTo(
      [subtitle.value, searchForm.value, stats.value, aiStrip.value],
      { opacity: 0, y: 18 },
      { opacity: 1, y: 0, stagger: 0.12, duration: 0.55, clearProps: 'transform' },
      '-=0.3'
    );
});

function goAsk() {
  router.push({ path: '/vraag-stellen', query: quickQuestion.value ? { q: quickQuestion.value } : {} });
}
</script>
