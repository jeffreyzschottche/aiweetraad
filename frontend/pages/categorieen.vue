<template>
  <div class="container-app py-10">
    <div v-reveal class="mb-8 text-center">
      <h1 class="font-display text-3xl font-bold text-brand-900 md:text-4xl">Alle categorieën</h1>
      <p class="mt-2 text-ink/60">Blader door alle onderwerpen en vind je antwoord.</p>
    </div>

    <div v-stagger class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <CategoryCard v-for="cat in categories" :key="cat.id" :category="cat" />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Category } from '~/types/content';

const api = useApi();
const { data } = await useAsyncData('categories', () => api.get<{ data: Category[] }>('/categories'));
const categories = computed(() => data.value?.data ?? []);

useHead({ title: 'Categorieën' });
</script>
