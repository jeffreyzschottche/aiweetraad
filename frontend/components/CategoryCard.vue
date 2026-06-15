<template>
  <NuxtLink
    ref="root"
    :to="`/categorie/${category.slug}`"
    class="card group relative flex items-center gap-4 overflow-hidden p-5"
    @mouseenter="hover(true)"
    @mouseleave="hover(false)"
  >
    <span
      class="absolute -right-6 -top-6 h-20 w-20 rounded-full opacity-0 transition-opacity duration-300 group-hover:opacity-100"
      :style="{ backgroundColor: tint(category.color, 0.18) }"
    />
    <span
      ref="icon"
      class="relative grid h-14 w-14 shrink-0 place-items-center rounded-2xl text-2xl"
      :style="{ backgroundColor: tint(category.color, 0.16) }"
    >
      {{ category.icon }}
    </span>
    <div class="relative min-w-0">
      <h3 class="truncate font-display text-base font-bold text-brand-900 group-hover:text-brand-700">
        {{ category.name }}
      </h3>
      <p class="truncate text-sm text-ink/55">{{ category.questions_count ?? 0 }} vragen</p>
    </div>
  </NuxtLink>
</template>

<script setup lang="ts">
import type { Category } from '~/types/content';

defineProps<{ category: Category }>();
const { $gsap } = useNuxtApp();
const icon = ref<HTMLElement | null>(null);

function hover(on: boolean) {
  if (!$gsap || !icon.value) return;
  $gsap.to(icon.value, {
    rotate: on ? -8 : 0,
    scale: on ? 1.12 : 1,
    duration: 0.35,
    ease: on ? 'back.out(3)' : 'power2.out',
  });
}

function tint(hex: string, a: number): string {
  const h = (hex || '#006A6C').replace('#', '');
  return `rgba(${parseInt(h.substring(0, 2), 16)}, ${parseInt(h.substring(2, 4), 16)}, ${parseInt(h.substring(4, 6), 16)}, ${a})`;
}
</script>
