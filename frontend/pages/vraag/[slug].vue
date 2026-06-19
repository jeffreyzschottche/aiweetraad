<template>
  <div class="container-app grid gap-10 py-8 lg:grid-cols-[1fr_300px]">
    <div v-if="question" class="min-w-0">
      <!-- Breadcrumb -->
      <nav class="mb-4 flex items-center gap-2 text-sm font-semibold text-ink/40">
        <NuxtLink to="/" class="hover:text-brand-600">Home</NuxtLink>
        <span>›</span>
        <NuxtLink
          v-if="question.category"
          :to="`/categorie/${question.category.slug}`"
          class="hover:text-brand-600"
        >
          {{ question.category.name }}
        </NuxtLink>
      </nav>

      <div class="relative overflow-hidden rounded-3xl border border-brand-100 bg-white p-6 shadow-card">
        <span class="absolute -right-8 -top-8 h-24 w-24 rounded-full bg-blush-200/60" />
        <span
          v-if="question.category"
          class="chip relative bg-brand-50 text-brand-600"
        >
          {{ question.category.icon }} {{ question.category.name }}
        </span>
        <h1 class="relative mt-3 font-display text-2xl font-bold leading-tight text-brand-900 md:text-3xl">
          {{ question.title }}
        </h1>
        <p v-if="question.body" class="relative mt-3 text-ink/70">{{ question.body }}</p>

        <div class="relative mt-5 grid gap-3 sm:grid-cols-3">
          <div class="rounded-2xl bg-brand-50 px-4 py-3">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-500">Antwoorden</p>
            <p class="font-display text-2xl font-bold text-brand-800">{{ answers.length }}</p>
          </div>
          <div class="rounded-2xl bg-teal2-200 px-4 py-3">
            <p class="text-xs font-bold uppercase tracking-wide text-teal2-600">Beste score</p>
            <p class="font-display text-2xl font-bold text-teal2-600">
              {{ topAnswer ? signedScore(topAnswer) : '0' }}
            </p>
          </div>
          <div class="rounded-2xl bg-blush-100 px-4 py-3">
            <p class="text-xs font-bold uppercase tracking-wide text-ink/50">Bekeken</p>
            <p class="font-display text-2xl font-bold text-brand-800">{{ question.views }}</p>
          </div>
        </div>
      </div>

      <section v-if="answers.length" class="mt-8">
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 class="font-display text-xl font-bold text-brand-900">Vergelijk de AI-antwoorden</h2>
            <p class="text-sm text-ink/60">Kies een AI-tab en lees één antwoord tegelijk.</p>
          </div>
          <NuxtLink to="/vraag-stellen" class="text-sm font-bold text-brand-600 hover:text-brand-700">
            Stel ook een vraag
          </NuxtLink>
        </div>

        <div
          class="grid gap-3 rounded-3xl border border-brand-100 bg-white p-3 shadow-card sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
          role="tablist"
          aria-label="AI-antwoorden"
        >
          <button
            v-for="answer in answers"
            :key="answer.id"
            type="button"
            role="tab"
            :aria-selected="activeAnswerId === answer.id"
            class="group grid min-h-[126px] min-w-0 grid-rows-[auto_1fr] gap-3 overflow-hidden rounded-2xl border-2 p-3 text-left transition"
            :class="activeAnswerId === answer.id
              ? 'border-brand-500 bg-brand-50 shadow-card'
              : 'border-transparent hover:border-brand-100 hover:bg-brand-50/60'"
            @click="activeAnswerId = answer.id"
          >
            <span class="flex min-w-0 items-center gap-2.5">
              <span
                class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-sm font-bold text-white"
                :style="logoBubbleStyle(answer)"
              >
                <img
                  v-if="aiLogo(answer) && !failedLogos[answer.id]"
                  :src="aiLogo(answer)"
                  :alt="`${answer.ai_model.name} logo`"
                  class="h-8 w-8 rounded-full object-contain"
                  :class="logoImageClass(answer)"
                  loading="lazy"
                  @error="failedLogos[answer.id] = true"
                />
                <span v-else>{{ initials(answer) }}</span>
              </span>
              <span class="block min-w-0 text-xs font-extrabold leading-tight text-brand-900 sm:text-sm xl:text-[13px]">
                {{ answer.ai_model?.name || 'AI' }}
              </span>
            </span>
            <span class="flex min-w-0 flex-col justify-end">
              <span class="mt-1 flex flex-wrap items-center gap-1.5">
                <span
                  class="rounded-full px-2 py-0.5 text-[11px] font-bold"
                  :class="answer.status === 'failed'
                    ? 'bg-blush-100 text-blush-500'
                    : score(answer) >= 0 ? 'bg-teal2-200 text-teal2-600' : 'bg-blush-200 text-blush-500'"
                >
                  {{ answer.status === 'failed' ? 'offline' : signedScore(answer) }}
                </span>
                <span class="whitespace-nowrap text-[11px] font-semibold text-ink/45">
                  {{ answer.upvotes }} werkt
                </span>
              </span>
              <span class="mt-2 block h-1.5 overflow-hidden rounded-full bg-brand-100">
                <span
                  class="block h-full rounded-full bg-teal2-500 transition-all"
                  :style="{ width: approval(answer) + '%' }"
                />
              </span>
            </span>
          </button>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-[1fr_220px]">
          <AnswerCard
            v-if="selectedAnswer"
            :key="selectedAnswer.id"
            v-reveal="{ y: 20 }"
            :answer="selectedAnswer"
            @voted="updateAnswerVotes(selectedAnswer.id, $event)"
          />

          <div class="space-y-3">
            <div
              v-for="answer in answers"
              :key="`compare-${answer.id}`"
              class="rounded-2xl border border-brand-100 bg-white p-4"
            >
              <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                  <img
                    v-if="aiLogo(answer) && !failedLogos[`compare-${answer.id}`]"
                    :src="aiLogo(answer)"
                    :alt="`${answer.ai_model?.name || 'AI'} logo`"
                    class="h-7 w-7 rounded-full object-contain"
                    :class="logoImageClass(answer)"
                    loading="lazy"
                    @error="failedLogos[`compare-${answer.id}`] = true"
                  />
                  <p class="truncate text-sm font-bold text-brand-900">{{ answer.ai_model?.name || 'AI' }}</p>
                </div>
                <span
                  class="text-xs font-bold"
                  :class="answer.status === 'failed' ? 'text-blush-500' : 'text-brand-600'"
                >
                  {{ answer.status === 'failed' ? 'offline' : `${approval(answer)}%` }}
                </span>
              </div>
              <div class="mt-2 h-2 overflow-hidden rounded-full bg-brand-100">
                <div class="h-full rounded-full bg-teal2-500" :style="{ width: approval(answer) + '%' }" />
              </div>
              <p class="mt-1 text-xs font-semibold text-ink/45">werkt-score</p>
            </div>
          </div>
        </div>

        <AdSlot class="mt-6" format="in-content" />
      </section>

      <div v-else class="mt-8 rounded-3xl border-2 border-dashed border-brand-100 p-10 text-center text-ink/50">
        Voor deze vraag zijn nog geen AI-antwoorden beschikbaar.
      </div>

      <div v-reveal class="mt-8 flex flex-col items-center gap-4 rounded-3xl bg-gradient-to-r from-brand-600 to-brand-700 p-7 text-center text-white sm:flex-row sm:justify-between sm:text-left">
        <div>
          <h3 class="font-display text-lg font-bold">Een andere vraag?</h3>
          <p class="text-sm text-white/75">Laat de AI’s ook jouw vraag beantwoorden.</p>
        </div>
        <NuxtLink to="/vraag-stellen" class="btn-accent shrink-0">Stel je vraag</NuxtLink>
      </div>
    </div>

    <div v-else class="py-20 text-center text-ink/40">Vraag niet gevonden.</div>

    <!-- Sidebar -->
    <aside class="space-y-6">
      <div v-if="related.length" class="card p-5">
        <h3 class="mb-3 font-display text-base font-bold text-brand-900">Gerelateerde vragen</h3>
        <ul class="space-y-1">
          <li v-for="r in related" :key="r.id">
            <NuxtLink :to="`/vraag/${r.slug}`" class="block rounded-2xl px-3 py-2 text-sm font-medium text-ink/70 transition hover:bg-brand-50 hover:text-brand-700">
              {{ r.title }}
            </NuxtLink>
          </li>
        </ul>
      </div>
      <AdSlot format="sidebar" />
    </aside>
  </div>
</template>

<script setup lang="ts">
import type { Answer, Question } from '~/types/content';

const route = useRoute();
const api = useApi();
const { aiLogoFor, aiLogoMetaFor } = useAiLogo();
const { absoluteUrl, siteUrl } = useSiteIdentity();
const slug = computed(() => route.params.slug as string);

const { data } = await useAsyncData(`question-${slug.value}`, () =>
  api.get<{ data: Question; related: { id: number; title: string; slug: string }[] }>(
    `/questions/${slug.value}`
  )
);

const question = computed(() => data.value?.data ?? null);
const related = computed(() => data.value?.related ?? []);
const answers = computed(() => question.value?.answers ?? []);
const activeAnswerId = ref<number | null>(null);
const failedLogos = reactive<Record<string, boolean>>({});
const selectedAnswer = computed(() =>
  answers.value.find((answer) => answer.id === activeAnswerId.value) ?? answers.value[0] ?? null
);
const topAnswer = computed(() =>
  answers.value.length
    ? [...answers.value].sort((a, b) => score(b) - score(a))[0]
    : null
);
const completedAnswers = computed(() =>
  answers.value.filter((answer) => (answer.status ?? 'completed') !== 'failed' && answer.body)
);

watch(
  answers,
  (items) => {
    if (!items.length) {
      activeAnswerId.value = null;
      return;
    }

    if (!items.some((answer) => answer.id === activeAnswerId.value)) {
      activeAnswerId.value = items[0].id;
    }
  },
  { immediate: true }
);

function score(answer: Answer): number {
  return answer.upvotes - answer.downvotes;
}

function signedScore(answer: Answer): string {
  const value = score(answer);
  return `${value >= 0 ? '+' : ''}${value}`;
}

function approval(answer: Answer): number {
  const total = answer.upvotes + answer.downvotes;
  return total > 0 ? Math.round((answer.upvotes / total) * 100) : 0;
}

function initials(answer: Answer): string {
  return (answer.ai_model?.name || 'AI').slice(0, 2);
}

function aiLogo(answer: Answer): string | null {
  return aiLogoFor(answer.ai_model);
}

function aiLogoMeta(answer: Answer) {
  return aiLogoMetaFor(answer.ai_model);
}

function isDeepSeek(answer: Answer): boolean {
  const slug = answer.ai_model?.slug?.toLowerCase() || '';
  const name = answer.ai_model?.name?.toLowerCase() || '';

  return slug.includes('deepseek') || name.includes('deepseek');
}

function logoBubbleStyle(answer: Answer): Record<string, string> {
  if (isDeepSeek(answer)) {
    return {
      backgroundColor: '#e8e5ff',
      color: '#5364f5',
    };
  }

  return {
    backgroundColor: answer.ai_model?.accent_color || '#006A6C',
  };
}

function logoImageClass(answer: Answer): string {
  if (isDeepSeek(answer)) {
    return 'p-1';
  }

  return aiLogoMeta(answer)?.needsWhiteBg ? 'bg-white p-1 ring-1 ring-black/5' : '';
}

function updateAnswerVotes(answerId: number, payload: { upvotes: number; downvotes: number; my_vote: number }) {
  const answer = answers.value.find((item) => item.id === answerId);
  if (!answer) return;
  answer.upvotes = payload.upvotes;
  answer.downvotes = payload.downvotes;
  answer.my_vote = payload.my_vote;
  answer.score = payload.upvotes - payload.downvotes;
}

const pageDescription = computed(() =>
  textExcerpt(question.value?.body || topAnswer.value?.body || question.value?.title)
);

usePageSeo(() => ({
  title: question.value?.title || 'Vraag',
  description: pageDescription.value,
  path: `/vraag/${slug.value}`,
  type: 'article',
}));

useJsonLd('question-page', () => {
  if (!question.value) return null;

  const url = absoluteUrl(`/vraag/${question.value.slug}`);
  const breadcrumbItems = [
    {
      '@type': 'ListItem',
      position: 1,
      name: 'Home',
      item: siteUrl,
    },
  ];

  if (question.value.category) {
    breadcrumbItems.push({
      '@type': 'ListItem',
      position: 2,
      name: question.value.category.name,
      item: absoluteUrl(`/categorie/${question.value.category.slug}`),
    });
  }

  breadcrumbItems.push({
    '@type': 'ListItem',
    position: breadcrumbItems.length + 1,
    name: question.value.title,
    item: url,
  });

  const breadcrumb = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: breadcrumbItems,
  };

  if (!completedAnswers.value.length) {
    return [
      breadcrumb,
      {
        '@context': 'https://schema.org',
        '@type': 'WebPage',
        '@id': `${url}#webpage`,
        url,
        name: question.value.title,
        description: pageDescription.value,
      },
    ];
  }

  const sortedAnswers = [...completedAnswers.value].sort((a, b) => score(b) - score(a));
  const [accepted, ...suggested] = sortedAnswers;
  const answerJson = (answer: Answer) => ({
    '@type': 'Answer',
    text: textExcerpt(answer.body, 5000),
    upvoteCount: Math.max(answer.upvotes, 0),
    datePublished: question.value?.created_at,
    author: {
      '@type': 'Organization',
      name: answer.ai_model?.name || 'AI-assistent',
    },
  });

  return [
    breadcrumb,
    {
      '@context': 'https://schema.org',
      '@type': 'QAPage',
      '@id': `${url}#qapage`,
      mainEntity: {
        '@type': 'Question',
        name: question.value.title,
        text: question.value.body || question.value.title,
        answerCount: completedAnswers.value.length,
        upvoteCount: completedAnswers.value.reduce((total, answer) => total + Math.max(answer.upvotes, 0), 0),
        datePublished: question.value.created_at,
        author: {
          '@type': 'Organization',
          name: 'AI Weet Raad community',
        },
        acceptedAnswer: answerJson(accepted),
        suggestedAnswer: suggested.map(answerJson),
      },
    },
  ];
});
</script>
