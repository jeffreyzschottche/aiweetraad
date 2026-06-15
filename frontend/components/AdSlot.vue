<template>
  <aside
    :class="wrapperClass"
    role="complementary"
    aria-label="Advertentie"
    data-ad-slot
  >
    <!--
      Advertentie-placeholder. Vervang de binnenkant door je echte ad-tag
      (bijv. Google AdSense / Ad Manager). De `format` prop bepaalt de maat:
      - leaderboard : 728x90 / responsive horizontaal (onder de header)
      - in-content  : native blok tussen de antwoorden
      - sidebar     : 300x600 sticky in de zijbalk
    -->
    <span class="text-[10px] font-bold uppercase tracking-widest text-brand-300">
      Advertentie
    </span>
    <span class="mt-1 text-xs text-brand-200">{{ label }}</span>
  </aside>
</template>

<script setup lang="ts">
const props = withDefaults(
  defineProps<{ format?: 'leaderboard' | 'in-content' | 'sidebar' }>(),
  { format: 'leaderboard' }
);

const wrapperClass = computed(() => {
  const base =
    'flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-brand-100 bg-white/50 text-center';
  return {
    leaderboard: `${base} h-[90px] w-full`,
    'in-content': `${base} h-28 w-full`,
    sidebar: `${base} sticky top-20 h-[600px] w-full`,
  }[props.format];
});

const label = computed(
  () =>
    ({
      leaderboard: '728 × 90',
      'in-content': 'Native / matched content',
      sidebar: '300 × 600',
    })[props.format]
);
</script>
