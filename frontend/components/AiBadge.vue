<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
    :style="{ backgroundColor: bg, color: model?.accent_color || '#475569' }"
  >
    <img
      v-if="logoSrc && !logoFailed"
      :src="logoSrc"
      :alt="`${model.name} logo`"
      class="h-4 w-4 rounded-full object-contain"
      :class="logoMeta?.needsWhiteBg ? 'bg-white p-0.5 ring-1 ring-black/5' : ''"
      loading="lazy"
      @error="logoFailed = true"
    />
    <span v-else class="h-2 w-2 rounded-full" :style="{ backgroundColor: model?.accent_color || '#94a3b8' }" />
    {{ model?.name || 'AI' }}
  </span>
</template>

<script setup lang="ts">
import type { AiModel } from '~/types/content';

const props = defineProps<{ model?: AiModel | null }>();
const logoFailed = ref(false);
const { aiLogoFor, aiLogoMetaFor } = useAiLogo();
const logoMeta = computed(() => aiLogoMetaFor(props.model));
const logoSrc = computed(() => aiLogoFor(props.model));

// Soft tinted background derived from the accent colour.
const bg = computed(() => {
  const hex = props.model?.accent_color || '#94a3b8';
  return hexToRgba(hex, 0.12);
});

function hexToRgba(hex: string, alpha: number): string {
  const h = hex.replace('#', '');
  const r = parseInt(h.substring(0, 2), 16);
  const g = parseInt(h.substring(2, 4), 16);
  const b = parseInt(h.substring(4, 6), 16);
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}
</script>
