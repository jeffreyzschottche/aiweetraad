<template>
  <Teleport to="body">
    <Transition name="search-fade">
      <div
        v-if="open"
        class="fixed inset-0 z-50 bg-ink/35 px-4 py-4 backdrop-blur-sm sm:py-10"
        role="presentation"
        @click.self="close"
      >
        <div
          role="dialog"
          aria-modal="true"
          aria-labelledby="search-modal-title"
          class="mx-auto flex max-h-[calc(100vh-2rem)] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-brand-100 bg-white shadow-soft sm:max-h-[calc(100vh-5rem)]"
        >
          <form class="border-b border-brand-100/70 p-4 sm:p-5" @submit.prevent="submitSearch">
            <div class="mb-3 flex items-center justify-between gap-3">
              <h2 id="search-modal-title" class="text-lg font-bold text-brand-900">Zoeken</h2>
              <button
                type="button"
                class="grid h-9 w-9 shrink-0 place-items-center rounded-full text-ink/55 transition hover:bg-brand-50 hover:text-brand-700"
                aria-label="Sluiten"
                @click="close"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                  <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
                </svg>
              </button>
            </div>

            <label class="relative block">
              <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-brand-500">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                  <circle cx="11" cy="11" r="7" />
                  <path d="m20 20-3.5-3.5" stroke-linecap="round" />
                </svg>
              </span>
              <input
                ref="input"
                v-model="query"
                type="search"
                autocomplete="off"
                placeholder="Waar zoek je naar?"
                class="field rounded-full py-3.5 pl-12 pr-4 text-base shadow-card"
              />
            </label>
          </form>

          <div class="min-h-0 flex-1 overflow-y-auto p-3 sm:p-4">
            <div v-if="!query.trim()" class="px-3 py-8 text-center text-sm text-ink/50">
              Typ om direct suggesties te zien.
            </div>

            <div v-else-if="loading" class="px-3 py-8 text-center text-sm text-ink/50">
              Zoeken...
            </div>

            <div v-else-if="results.length" class="space-y-2">
              <NuxtLink
                v-for="question in results"
                :key="question.id"
                :to="`/vraag/${question.slug}`"
                class="group block rounded-2xl border border-brand-100/70 bg-cream/45 p-4 transition hover:border-brand-200 hover:bg-brand-50"
                @click="close"
              >
                <div class="mb-2 flex items-center gap-2">
                  <span
                    v-if="question.category"
                    class="chip"
                    :style="{ backgroundColor: tint(question.category.color), color: question.category.color }"
                  >
                    {{ question.category.icon }} {{ question.category.name }}
                  </span>
                </div>
                <p class="font-display text-base font-bold leading-snug text-brand-900 group-hover:text-brand-700">
                  {{ question.title }}
                </p>
                <p v-if="question.body" class="mt-1 line-clamp-2 text-sm leading-relaxed text-ink/60">
                  {{ question.body }}
                </p>
              </NuxtLink>
            </div>

            <div v-else class="px-3 py-8 text-center">
              <p class="text-sm text-ink/55">Geen directe resultaten.</p>
              <NuxtLink
                :to="{ path: '/vraag-stellen', query: { q: query.trim() } }"
                class="btn-primary mt-4"
                @click="close"
              >
                Stel deze vraag
              </NuxtLink>
            </div>
          </div>

          <div v-if="query.trim()" class="border-t border-brand-100/70 bg-cream/60 p-3">
            <button
              type="button"
              class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left text-sm font-bold text-brand-700 transition hover:bg-white"
              @click="submitSearch"
            >
              <span>Alle resultaten voor "{{ query.trim() }}"</span>
              <span aria-hidden="true">Enter</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import type { Paginated, Question } from '~/types/content';

const props = defineProps<{ open: boolean }>();
const emit = defineEmits<{ close: [] }>();

const api = useApi();
const router = useRouter();

const input = ref<HTMLInputElement | null>(null);
const query = ref('');
const results = ref<Question[]>([]);
const loading = ref(false);
let debounceTimer: ReturnType<typeof setTimeout> | undefined;
let requestId = 0;

function close() {
  emit('close');
}

function submitSearch() {
  const term = query.value.trim();
  if (!term) return;

  close();
  router.push({ path: '/zoeken', query: { q: term } });
}

async function fetchResults(term: string) {
  const currentRequest = ++requestId;
  loading.value = true;

  try {
    const res = await api.get<Paginated<Question>>(`/questions?q=${encodeURIComponent(term)}`);
    if (currentRequest === requestId) {
      results.value = res.data.slice(0, 6);
    }
  } finally {
    if (currentRequest === requestId) {
      loading.value = false;
    }
  }
}

watch(
  () => props.open,
  async (isOpen) => {
    document.body.classList.toggle('overflow-hidden', isOpen);

    if (isOpen) {
      await nextTick();
      input.value?.focus();
      return;
    }

    if (debounceTimer) clearTimeout(debounceTimer);
    loading.value = false;
  }
);

watch(query, (term) => {
  const trimmed = term.trim();
  if (debounceTimer) clearTimeout(debounceTimer);

  if (trimmed.length < 2) {
    requestId++;
    results.value = [];
    loading.value = false;
    return;
  }

  loading.value = true;
  debounceTimer = setTimeout(() => fetchResults(trimmed), 220);
});

function onKeydown(event: KeyboardEvent) {
  if (!props.open || event.key !== 'Escape') return;
  close();
}

function tint(hex: string): string {
  const h = (hex || '#006A6C').replace('#', '');
  return `rgba(${parseInt(h.substring(0, 2), 16)}, ${parseInt(h.substring(2, 4), 16)}, ${parseInt(h.substring(4, 6), 16)}, 0.12)`;
}

onMounted(() => window.addEventListener('keydown', onKeydown));

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown);
  document.body.classList.remove('overflow-hidden');
  if (debounceTimer) clearTimeout(debounceTimer);
});
</script>

<style scoped>
.search-fade-enter-active,
.search-fade-leave-active {
  transition: opacity 0.18s ease;
}

.search-fade-enter-from,
.search-fade-leave-to {
  opacity: 0;
}
</style>
