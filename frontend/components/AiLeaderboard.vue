<template>
  <div class="card overflow-hidden">
    <div class="bg-gradient-to-r from-brand-600 to-brand-700 px-5 py-4 text-white">
      <h3 class="font-display text-base font-bold">🏆 AI’s aan de top</h3>
      <p class="text-xs text-white/70">Likes min dislikes, over alle antwoorden.</p>
    </div>
    <ol v-if="rankedModels.length" class="space-y-2 p-3">
      <li
        v-for="(model, i) in rankedModels"
        :key="model.id"
        class="rounded-2xl px-3 py-2.5 transition hover:bg-brand-50"
      >
        <div class="flex items-center gap-3">
          <span
            class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-xs font-bold"
            :class="medal(i)"
          >
            {{ i + 1 }}
          </span>
          <span
            class="grid h-9 w-9 shrink-0 place-items-center rounded-full"
            :style="{ backgroundColor: tint(model.accent_color, 0.12) }"
          >
            <img
              v-if="aiLogoFor(model) && !failedLogos[model.id]"
              :src="aiLogoFor(model)"
              :alt="`${model.name} logo`"
              class="h-7 w-7 rounded-full object-contain"
              :class="aiLogoMetaFor(model)?.needsWhiteBg ? 'bg-white p-1 ring-1 ring-black/5' : ''"
              loading="lazy"
              @error="failedLogos[model.id] = true"
            />
            <span v-else class="text-xs font-bold" :style="{ color: model.accent_color }">
              {{ model.name.slice(0, 2) }}
            </span>
          </span>
          <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-extrabold text-brand-900">{{ model.name }}</span>
            <span class="text-[11px] font-semibold text-ink/45">
              {{ model.total_upvotes ?? 0 }} likes · {{ model.total_downvotes ?? 0 }} dislikes
            </span>
          </span>
          <span
            class="chip"
            :class="score(model) >= 0 ? 'bg-teal2-200 text-teal2-600' : 'bg-blush-200 text-blush-500'"
          >
            {{ signedScore(model) }}
          </span>
        </div>
        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-brand-100">
          <div class="h-full rounded-full bg-teal2-500" :style="{ width: approval(model) + '%' }" />
        </div>
      </li>
    </ol>
    <p v-else class="p-5 text-sm font-semibold text-ink/55">
      Nog geen AI-scores beschikbaar.
    </p>
  </div>
</template>

<script setup lang="ts">
import type { AiModel } from '~/types/content';

const props = defineProps<{ models: AiModel[] }>();
const failedLogos = reactive<Record<number, boolean>>({});
const { aiLogoFor, aiLogoMetaFor } = useAiLogo();

const rankedModels = computed(() =>
  [...props.models].sort((a, b) => score(b) - score(a))
);

function score(model: AiModel): number {
  return (model.total_upvotes ?? 0) - (model.total_downvotes ?? 0);
}

function signedScore(model: AiModel): string {
  const value = score(model);
  return `${value >= 0 ? '+' : ''}${value}`;
}

function approval(model: AiModel): number {
  const upvotes = model.total_upvotes ?? 0;
  const downvotes = model.total_downvotes ?? 0;
  const total = upvotes + downvotes;

  return total > 0 ? Math.round((upvotes / total) * 100) : 0;
}

function medal(i: number): string {
  return (
    [
      'bg-amber-100 text-amber-600',
      'bg-slate-100 text-slate-500',
      'bg-orange-100 text-orange-600',
    ][i] || 'bg-brand-50 text-brand-400'
  );
}

function tint(hex: string, alpha: number): string {
  const h = (hex || '#006A6C').replace('#', '');
  return `rgba(${parseInt(h.substring(0, 2), 16)}, ${parseInt(h.substring(2, 4), 16)}, ${parseInt(h.substring(4, 6), 16)}, ${alpha})`;
}
</script>
