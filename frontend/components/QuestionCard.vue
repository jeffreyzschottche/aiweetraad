<template>
  <NuxtLink
    :to="`/vraag/${question.slug}`"
    class="card group relative flex flex-col overflow-hidden p-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-soft"
  >
    <span class="absolute inset-x-0 top-0 h-1 origin-left scale-x-0 bg-brand-400 transition-transform duration-300 group-hover:scale-x-100" />
    <div class="mb-2.5 flex items-center gap-2">
      <span
        v-if="question.category"
        class="chip"
        :style="{ backgroundColor: tint(question.category.color), color: question.category.color }"
      >
        {{ question.category.icon }} {{ question.category.name }}
      </span>
    </div>
    <h3 class="font-display text-base font-bold leading-snug text-brand-900 group-hover:text-brand-700">
      {{ question.title }}
    </h3>
    <div class="mt-3 flex items-center gap-4 text-xs font-semibold text-ink/45">
      <span class="inline-flex items-center gap-1">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M8 10h8M8 14h5M21 12a8 8 0 0 1-11.3 7.3L3 21l1.7-6.7A8 8 0 1 1 21 12z" stroke-linejoin="round" />
        </svg>
        {{ question.answers_count ?? 0 }} antwoorden
      </span>
      <span class="inline-flex items-center gap-1">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" /><circle cx="12" cy="12" r="3" />
        </svg>
        {{ question.views }}
      </span>
      <span class="ml-auto text-brand-400 transition-transform duration-300 group-hover:translate-x-1">→</span>
    </div>
  </NuxtLink>
</template>

<script setup lang="ts">
import type { Question } from '~/types/content';

defineProps<{ question: Question }>();

function tint(hex: string): string {
  const h = (hex || '#006A6C').replace('#', '');
  return `rgba(${parseInt(h.substring(0, 2), 16)}, ${parseInt(h.substring(2, 4), 16)}, ${parseInt(h.substring(4, 6), 16)}, 0.12)`;
}
</script>
