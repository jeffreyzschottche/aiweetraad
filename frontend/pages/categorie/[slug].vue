<template>
  <div class="container-app grid gap-10 py-8 lg:grid-cols-[1fr_300px]">
    <div class="min-w-0">
      <nav class="mb-4 flex items-center gap-2 text-sm font-semibold text-ink/40">
        <NuxtLink to="/" class="hover:text-brand-600">Home</NuxtLink>
        <span>›</span>
        <NuxtLink to="/categorieen" class="hover:text-brand-600">Categorieën</NuxtLink>
      </nav>

      <div v-if="category" v-reveal class="flex items-center gap-4">
        <span
          class="grid h-16 w-16 place-items-center rounded-3xl text-3xl shadow-card"
          :style="{ backgroundColor: tint(category.color) }"
        >
          {{ category.icon }}
        </span>
        <div>
          <h1 class="font-display text-3xl font-bold text-brand-900">{{ category.name }}</h1>
          <p class="text-ink/60">{{ category.description }}</p>
        </div>
      </div>

      <div class="mt-8 grid gap-4 sm:grid-cols-2">
        <QuestionCard v-for="q in questions" :key="q.id" :question="q" />
      </div>

      <p v-if="!questions.length" class="mt-8 rounded-3xl border-2 border-dashed border-brand-100 p-10 text-center text-ink/50">
        Nog geen vragen in deze categorie.
        <NuxtLink to="/vraag-stellen" class="font-bold text-brand-600">Stel de eerste!</NuxtLink>
      </p>

      <div v-if="pagination && pagination.last_page > 1" class="mt-8 flex justify-center gap-2">
        <button
          v-for="p in pagination.last_page"
          :key="p"
          class="h-10 w-10 rounded-full text-sm font-bold transition"
          :class="p === pagination.current_page ? 'bg-brand-600 text-white shadow-soft' : 'border-2 border-brand-100 text-ink/60 hover:border-brand-300'"
          @click="loadPage(p)"
        >
          {{ p }}
        </button>
      </div>
    </div>

    <aside class="space-y-6">
      <div class="card p-6 text-center">
        <div class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-full bg-teal2-200 text-3xl">🔎</div>
        <h3 class="font-display text-lg font-bold text-brand-900">Niet gevonden wat je zocht?</h3>
        <NuxtLink to="/vraag-stellen" class="btn-primary mt-4 w-full">Stel je vraag</NuxtLink>
      </div>
      <AdSlot format="sidebar" />
    </aside>
  </div>
</template>

<script setup lang="ts">
import type { Category, Paginated, Question } from '~/types/content';

const route = useRoute();
const api = useApi();
const slug = computed(() => route.params.slug as string);

const { data } = await useAsyncData(`category-${slug.value}`, () =>
  api.get<{ data: Category; questions: Paginated<Question> }>(`/categories/${slug.value}`)
);

const category = computed(() => data.value?.data ?? null);
const questions = ref<Question[]>(data.value?.questions.data ?? []);
const pagination = ref<Paginated<Question> | null>(data.value?.questions ?? null);

watch(data, (val) => {
  questions.value = val?.questions.data ?? [];
  pagination.value = val?.questions ?? null;
});

async function loadPage(page: number) {
  const res = await api.get<Paginated<Question>>(`/questions?category=${slug.value}&page=${page}`);
  questions.value = res.data;
  pagination.value = res;
  if (typeof window !== 'undefined') window.scrollTo({ top: 0, behavior: 'smooth' });
}

function tint(hex: string): string {
  const h = (hex || '#6366f1').replace('#', '');
  return `rgba(${parseInt(h.substring(0, 2), 16)}, ${parseInt(h.substring(2, 4), 16)}, ${parseInt(h.substring(4, 6), 16)}, 0.12)`;
}

useHead(() => ({ title: category.value?.name || 'Categorie' }));
</script>
