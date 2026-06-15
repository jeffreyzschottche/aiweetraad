<template>
  <article class="card overflow-hidden">
    <header class="flex items-center justify-between gap-2 border-b border-brand-100/50 px-5 py-3.5">
      <div class="flex items-center gap-2.5">
        <span
          class="grid h-9 w-9 place-items-center rounded-full text-sm font-bold text-white"
          :style="{ backgroundColor: answer.ai_model?.accent_color || '#006A6C' }"
        >
          <img
            v-if="logoSrc && !logoFailed"
            :src="logoSrc"
            :alt="`${answer.ai_model.name} logo`"
            class="h-7 w-7 rounded-full object-contain"
            :class="logoMeta?.needsWhiteBg ? 'bg-white p-1 ring-1 ring-black/5' : ''"
            loading="lazy"
            @error="logoFailed = true"
          />
          <span v-else>{{ initials }}</span>
        </span>
        <div>
          <p class="text-sm font-bold text-brand-900">{{ answer.ai_model?.name || 'AI' }}</p>
          <p v-if="answer.ai_model?.tagline" class="text-xs text-ink/50">{{ answer.ai_model.tagline }}</p>
        </div>
      </div>
      <div class="flex shrink-0 items-center gap-2">
        <span
          v-if="status !== 'completed'"
          class="rounded-full px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wide"
          :class="status === 'failed' ? 'bg-blush-100 text-blush-500' : 'bg-amber-100 text-amber-700'"
        >
          {{ statusLabel }}
        </span>
        <span
          ref="scoreEl"
          class="chip"
          :class="score >= 0 ? 'bg-teal2-200 text-teal2-600' : 'bg-blush-200 text-blush-500'"
        >
          {{ score >= 0 ? '+' : '' }}{{ score }}
        </span>
      </div>
    </header>

    <div class="prose-content px-5 py-4 text-[15px]">
      <div
        v-if="status === 'failed'"
        class="mb-4 rounded-2xl border border-blush-200 bg-blush-100 p-4 text-sm font-semibold text-blush-500"
      >
        Deze AI is tijdelijk niet beschikbaar. Er is geen nepantwoord geplaatst.
      </div>
      <div
        v-else-if="status === 'fallback'"
        class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800"
      >
        Dit antwoord is via een fallback opgehaald.
      </div>
      <p v-for="(para, i) in paragraphs" :key="i" class="whitespace-pre-line">{{ para }}</p>
      <p v-if="providerLabel" class="mt-4 text-xs font-semibold text-ink/40">
        Provider: {{ providerLabel }}
      </p>
    </div>

    <footer v-if="status !== 'failed'" class="flex flex-wrap items-center gap-2 border-t border-brand-100/50 px-5 py-3">
      <span class="mr-1 text-xs font-semibold text-ink/45">Werkt dit?</span>
      <button
        ref="upBtn"
        class="inline-flex items-center gap-1.5 rounded-full border-2 px-3.5 py-1.5 text-sm font-bold transition"
        :class="myVote === 1
          ? 'border-teal2-400 bg-teal2-200 text-teal2-600'
          : 'border-brand-100 text-ink/60 hover:border-teal2-400 hover:text-teal2-600'"
        :disabled="loading"
        @click="vote(1)"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M7 10v11M2 11h5v10H2zM7 11l4-7a2 2 0 0 1 3 1v4h5a2 2 0 0 1 2 2.3l-1.3 6A2 2 0 0 1 16.7 21H7" stroke-linejoin="round" />
        </svg>
        Werkt · {{ upvotes }}
      </button>
      <button
        ref="downBtn"
        class="inline-flex items-center gap-1.5 rounded-full border-2 px-3.5 py-1.5 text-sm font-bold transition"
        :class="myVote === -1
          ? 'border-blush-400 bg-blush-200 text-blush-500'
          : 'border-brand-100 text-ink/60 hover:border-blush-400 hover:text-blush-500'"
        :disabled="loading"
        @click="vote(-1)"
      >
        <svg class="h-4 w-4 rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M7 10v11M2 11h5v10H2zM7 11l4-7a2 2 0 0 1 3 1v4h5a2 2 0 0 1 2 2.3l-1.3 6A2 2 0 0 1 16.7 21H7" stroke-linejoin="round" />
        </svg>
        Niet voor mij · {{ downvotes }}
      </button>
      <p v-if="authMessage" class="w-full text-xs font-semibold text-blush-500">
        {{ authMessage }}
        <NuxtLink to="/login" class="underline decoration-2 underline-offset-2">Log in</NuxtLink>
        om te stemmen.
      </p>
    </footer>
    <footer v-else class="border-t border-brand-100/50 px-5 py-3 text-xs font-semibold text-ink/45">
      Stemmen is uitgeschakeld omdat dit antwoord niet succesvol is opgehaald.
    </footer>
  </article>
</template>

<script setup lang="ts">
import type { Answer } from '~/types/content';

const props = defineProps<{ answer: Answer }>();
const emit = defineEmits<{
  voted: [{ upvotes: number; downvotes: number; my_vote: number; score?: number }];
}>();
const { $gsap } = useNuxtApp();
const api = useApi();
const authStore = useAuthStore();
const { aiLogoFor, aiLogoMetaFor } = useAiLogo();

const upvotes = ref(props.answer.upvotes);
const downvotes = ref(props.answer.downvotes);
const myVote = ref(props.answer.my_vote ?? 0);
const loading = ref(false);
const logoFailed = ref(false);
const authMessage = ref('');

const scoreEl = ref<HTMLElement | null>(null);
const upBtn = ref<HTMLElement | null>(null);
const downBtn = ref<HTMLElement | null>(null);

const score = computed(() => upvotes.value - downvotes.value);
const paragraphs = computed(() => props.answer.body.split(/\n\n+/).filter(Boolean));
const initials = computed(() => (props.answer.ai_model?.name || 'AI').slice(0, 2));
const logoMeta = computed(() => aiLogoMetaFor(props.answer.ai_model));
const logoSrc = computed(() => aiLogoFor(props.answer.ai_model));
const status = computed(() => props.answer.status ?? 'completed');
const statusLabel = computed(() => status.value === 'failed' ? 'Niet beschikbaar' : 'Fallback');
const providerLabel = computed(() => {
  if (!props.answer.actual_provider) return '';
  return props.answer.actual_model
    ? `${props.answer.actual_provider} · ${props.answer.actual_model}`
    : props.answer.actual_provider;
});

function pop(el: HTMLElement | null) {
  if (!$gsap || !el) return;
  $gsap.fromTo(el, { scale: 0.8 }, { scale: 1, duration: 0.45, ease: 'back.out(4)' });
}

async function vote(value: number) {
  if (loading.value) return;
  if (status.value === 'failed') return;
  if (!authStore.isLoggedIn) {
    authMessage.value = 'Je hebt een account nodig';
    return;
  }

  authMessage.value = '';
  loading.value = true;
  pop(value === 1 ? upBtn.value : downBtn.value);
  try {
    const res = await api.post<{ upvotes: number; downvotes: number; my_vote: number }>(
      `/answers/${props.answer.id}/vote`,
      { value }
    );
    upvotes.value = res.upvotes;
    downvotes.value = res.downvotes;
    myVote.value = res.my_vote;
    emit('voted', res);
    pop(scoreEl.value);
  } catch (e) {
    console.error('Vote failed', e);
  } finally {
    loading.value = false;
  }
}
</script>
