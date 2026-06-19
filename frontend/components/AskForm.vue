<template>
  <form class="card p-6" @submit.prevent="submit">
    <div v-if="error" class="mb-4 rounded-2xl bg-blush-100 p-3 text-sm font-semibold text-blush-500">{{ error }}</div>
    <div v-if="!authStore.isLoggedIn" class="mb-4 rounded-2xl bg-brand-50 p-3 text-sm font-semibold text-brand-700">
      Je hebt een account nodig om een nieuwe AI-vraag te stellen.
      <NuxtLink to="/login" class="underline decoration-2 underline-offset-2">Log in</NuxtLink>
      of
      <NuxtLink to="/register" class="underline decoration-2 underline-offset-2">maak een account</NuxtLink>.
    </div>

    <label class="mb-1.5 block text-sm font-bold text-brand-800">Je vraag</label>
    <input
      v-model="form.title"
      type="text"
      required
      placeholder="Bijv. Hoe verwijder ik een koffievlek uit een wit overhemd?"
      class="field"
    />

    <label class="mb-1.5 mt-4 block text-sm font-bold text-brand-800">Categorie (optioneel)</label>
    <select v-model="form.category_id" class="field">
      <option :value="null">— Kies een categorie —</option>
      <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.icon }} {{ c.name }}</option>
    </select>

    <label class="mb-1.5 mt-4 block text-sm font-bold text-brand-800">Toelichting (optioneel)</label>
    <textarea
      v-model="form.body"
      rows="3"
      placeholder="Geef wat extra context zodat de AI’s je beter kunnen helpen."
      class="field"
    />

    <button type="submit" class="btn-primary mt-5 w-full" :disabled="loading">
      Vraag het de AI’s
    </button>
    <p class="mt-3 text-center text-xs text-ink/45">
      Je krijgt direct antwoord van {{ aiCount }} verschillende AI’s.
    </p>

    <Teleport to="body">
      <div v-if="confirmOpen" class="fixed inset-0 z-50 grid place-items-center bg-brand-900/60 px-4 backdrop-blur-sm">
        <section class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-card">
          <h2 class="font-display text-2xl font-bold text-brand-900">Vraag controleren</h2>
          <p class="mt-2 text-sm text-ink/60">Klopt je vraag zo?</p>
          <div class="mt-4 rounded-2xl border border-brand-100 bg-cream p-4">
            <p class="font-bold text-brand-900">{{ form.title }}</p>
            <p v-if="form.body" class="mt-2 text-sm text-ink/65">{{ form.body }}</p>
          </div>
          <label class="mt-4 flex items-start gap-3 text-sm font-semibold text-ink/70">
            <input v-model="accepted" type="checkbox" class="mt-1 h-4 w-4 rounded border-brand-200 text-brand-600 focus:ring-brand-300" />
            <span>Ik bevestig dat mijn vraag klopt en netjes is geformuleerd.</span>
          </label>
          <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" @click="confirmOpen = false">Aanpassen</button>
            <button type="button" class="btn-primary" :disabled="!accepted || loading" @click="confirmAndSubmit">
              Ja, stel mijn vraag
            </button>
          </div>
        </section>
      </div>

      <div v-if="processing" class="fixed inset-0 z-50 grid place-items-center bg-brand-900 px-4 text-white">
        <section class="w-full max-w-3xl overflow-hidden rounded-[2rem] border border-white/10 bg-[#062f30] shadow-2xl">
          <div class="flex items-center justify-between border-b border-white/10 bg-black/20 px-5 py-3">
            <div class="flex items-center gap-2">
              <span class="h-3 w-3 rounded-full bg-blush-300" />
              <span class="h-3 w-3 rounded-full bg-amber-300" />
              <span class="h-3 w-3 rounded-full bg-teal2-300" />
            </div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-white/45">AI Weet Raad terminal</p>
          </div>

          <div class="p-5 sm:p-7">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
              <div>
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-teal2-200">Vraag verwerken</p>
                <h2 class="mt-2 font-display text-3xl font-bold leading-tight md:text-5xl">
                  De AI’s zijn bezig
                </h2>
              </div>
              <p class="rounded-full bg-white/10 px-4 py-2 text-sm font-extrabold text-white">
                {{ progressPercent }}%
              </p>
            </div>

            <div class="mt-6 h-3 overflow-hidden rounded-full bg-white/10">
              <div class="h-full rounded-full bg-gradient-to-r from-teal2-300 via-white to-blush-300 transition-all duration-700" :style="{ width: `${progressPercent}%` }" />
            </div>

            <div class="mt-6 rounded-2xl border border-white/10 bg-black/25 p-4 font-mono text-sm">
              <div v-for="(step, index) in terminalSteps" :key="step.label" class="flex gap-3 py-1.5">
                <span class="w-5 shrink-0 text-right" :class="stepClass(index)">
                  {{ stepPrefix(index) }}
                </span>
                <span class="min-w-0 flex-1" :class="stepClass(index)">
                  {{ step.label }}
                </span>
              </div>
              <div class="mt-3 flex gap-3 border-t border-white/10 pt-3 text-white/70">
                <span class="w-5 shrink-0 text-right text-teal2-200">›</span>
                <span>{{ activeTerminalLine }}<span class="terminal-cursor">_</span></span>
              </div>
            </div>

            <p class="mt-5 text-center text-sm font-semibold text-white/55">
              Dit kan even duren. Sluit dit venster niet; je vraagpagina verschijnt zodra de antwoorden klaar zijn.
            </p>
          </div>
        </section>
      </div>

      <div v-if="successQuestion" class="fixed inset-0 z-50 grid place-items-center bg-brand-900/60 px-4 backdrop-blur-sm">
        <section class="relative w-full max-w-lg overflow-hidden rounded-3xl bg-white p-6 text-center shadow-card">
          <span
            v-for="i in 18"
            :key="i"
            class="confetti-piece"
            :style="{ left: `${(i * 37) % 100}%`, animationDelay: `${(i % 6) * 0.12}s`, backgroundColor: confettiColors[i % confettiColors.length] }"
          />
          <h2 class="font-display text-2xl font-bold text-brand-900">Je vraag staat live</h2>
          <p class="mt-2 text-sm text-ink/60">Alle beschikbare AI-antwoorden zijn opgeslagen op je vraagpagina.</p>
          <NuxtLink :to="questionPath" class="btn-primary mt-5 w-full">Bekijk je vraag</NuxtLink>
          <button type="button" class="btn-ghost mt-2 w-full" @click="copyQuestionLink">
            {{ copied ? 'Link gekopieerd' : 'Kopieer link' }}
          </button>
        </section>
      </div>
    </Teleport>
  </form>
</template>

<script setup lang="ts">
import type { Category, Question } from '~/types/content';

const props = defineProps<{ initialTitle?: string }>();

const api = useApi();
const authStore = useAuthStore();

const categories = ref<Category[]>([]);
const aiCount = ref(5);

const form = reactive<{ title: string; category_id: number | null; body: string }>({
  title: props.initialTitle ?? '',
  category_id: null,
  body: '',
});
const loading = ref(false);
const error = ref('');
const confirmOpen = ref(false);
const accepted = ref(false);
const processing = ref(false);
const elapsedSeconds = ref(0);
const successQuestion = ref<Question | null>(null);
const copied = ref(false);
let phaseTimer: ReturnType<typeof setInterval> | null = null;

const terminalSteps = [
  { label: 'Vraag ontvangen en controleren' },
  { label: 'ChatGPT benaderen' },
  { label: 'Claude benaderen' },
  { label: 'Gemini benaderen' },
  { label: 'Grok benaderen' },
  { label: 'DeepSeek benaderen' },
  { label: 'Antwoorden opslaan' },
  { label: 'Vraagpagina publiceren' },
];
const confettiColors = ['#006A6C', '#f9c6c5', '#f59e0b', '#10a37f', '#4285f4'];
const activeStepIndex = computed(() => Math.min(Math.floor(elapsedSeconds.value / 3), terminalSteps.length - 1));
const progressPercent = computed(() => {
  const base = Math.min(92, 8 + elapsedSeconds.value * 5);
  const stepBoost = activeStepIndex.value * 6;

  return Math.min(92, Math.max(base, 12 + stepBoost));
});
const activeTerminalLine = computed(() => {
  if (activeStepIndex.value >= terminalSteps.length - 1) {
    return 'Laatste checks draaien, bijna klaar';
  }

  return terminalSteps[activeStepIndex.value]?.label + '...';
});
const questionPath = computed(() => successQuestion.value ? `/vraag/${successQuestion.value.slug}` : '/');

onMounted(async () => {
  try {
    const [cats, models] = await Promise.all([
      api.get<{ data: Category[] }>('/categories'),
      api.get<{ data: unknown[] }>('/ai-models'),
    ]);
    categories.value = cats.data;
    aiCount.value = models.data.length || 5;
  } catch {
    /* non-fatal */
  }
});

async function submit() {
  error.value = '';
  if (!authStore.isLoggedIn) {
    error.value = 'Log in of maak een account om een vraag te stellen.';
    return;
  }

  accepted.value = false;
  confirmOpen.value = true;
}

async function confirmAndSubmit() {
  if (!accepted.value || loading.value) return;

  confirmOpen.value = false;
  loading.value = true;
  processing.value = true;
  elapsedSeconds.value = 0;
  phaseTimer = setInterval(() => {
    elapsedSeconds.value += 1;
  }, 1000);

  try {
    const res = await api.post<{ data: Question }>('/questions', {
      title: form.title,
      category_id: form.category_id,
      body: form.body || null,
    });
    successQuestion.value = res.data;
  } catch (err: any) {
    error.value =
      err?.data?.errors
        ? Object.values(err.data.errors).flat().join(' ')
        : err?.message || 'Er ging iets mis. Probeer het opnieuw.';
  } finally {
    if (phaseTimer) {
      clearInterval(phaseTimer);
      phaseTimer = null;
    }
    processing.value = false;
    loading.value = false;
  }
}

async function copyQuestionLink() {
  if (!successQuestion.value || typeof window === 'undefined') return;

  await navigator.clipboard?.writeText(`${window.location.origin}${questionPath.value}`);
  copied.value = true;
  setTimeout(() => {
    copied.value = false;
  }, 1800);
}

function stepPrefix(index: number): string {
  if (index < activeStepIndex.value) return '✓';
  if (index === activeStepIndex.value) return '…';
  return '·';
}

function stepClass(index: number): string {
  if (index < activeStepIndex.value) return 'text-teal2-200';
  if (index === activeStepIndex.value) return 'text-white';
  return 'text-white/35';
}
</script>

<style scoped>
.confetti-piece {
  position: absolute;
  top: -14px;
  width: 8px;
  height: 14px;
  border-radius: 2px;
  animation: confetti-fall 1.4s ease-out forwards;
}

@keyframes confetti-fall {
  0% {
    opacity: 1;
    transform: translateY(0) rotate(0deg);
  }
  100% {
    opacity: 0;
    transform: translateY(220px) rotate(260deg);
  }
}

.terminal-cursor {
  display: inline-block;
  margin-left: 2px;
  color: #f9c6c5;
  animation: cursor-blink 0.9s steps(2, start) infinite;
}

@keyframes cursor-blink {
  0%,
  45% {
    opacity: 1;
  }
  46%,
  100% {
    opacity: 0;
  }
}
</style>
