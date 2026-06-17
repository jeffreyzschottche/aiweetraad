<template>
  <div class="container-app py-10">
    <div class="grid gap-8 lg:grid-cols-[320px_1fr]">
      <aside v-if="authStore.user" class="space-y-5">
        <div class="card space-y-5 p-6">
          <div class="flex items-center gap-4">
            <span class="grid h-14 w-14 place-items-center rounded-full bg-brand-100 text-xl font-bold text-brand-700">
              {{ initials }}
            </span>
            <div class="min-w-0">
              <p class="truncate text-lg font-bold text-brand-900">{{ authStore.user.name }}</p>
              <p class="truncate text-sm text-ink/50">{{ authStore.user.email }}</p>
            </div>
          </div>

          <div
            v-if="!authStore.user.email_verified_at"
            class="rounded-xl2 border-l-4 border-amber-400 bg-amber-50 p-4"
          >
            <div class="space-y-3">
              <div>
                <p class="font-semibold text-amber-800">Bevestig je e-mailadres</p>
                <p class="text-sm text-amber-700">Check je inbox voor de verificatielink.</p>
              </div>
              <button class="btn-primary w-full" :disabled="resending" @click="resendVerification">
                {{ resending ? 'Versturen…' : 'Opnieuw versturen' }}
              </button>
            </div>
            <p v-if="resendMessage" class="mt-2 text-sm text-green-700">{{ resendMessage }}</p>
          </div>
          <div v-else class="flex items-center gap-2 text-sm font-medium text-green-600">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            E-mailadres bevestigd
          </div>

          <div class="grid grid-cols-2 gap-3 border-t border-brand-100 pt-5">
            <div class="rounded-2xl bg-brand-50 p-4 text-center">
              <p class="font-display text-2xl font-bold text-brand-700">{{ activity?.stats.questions ?? 0 }}</p>
              <p class="text-xs font-semibold text-ink/50">Vragen</p>
            </div>
            <div class="rounded-2xl bg-teal2-200 p-4 text-center">
              <p class="font-display text-2xl font-bold text-teal2-600">{{ activity?.stats.worked ?? 0 }}</p>
              <p class="text-xs font-semibold text-ink/50">Werkt</p>
            </div>
          </div>

          <div class="flex gap-3">
            <NuxtLink to="/vraag-stellen" class="btn-primary flex-1">Stel vraag</NuxtLink>
            <button class="btn-ghost" @click="handleLogout">Uitloggen</button>
          </div>
        </div>

        <AdSlot format="in-content" />
      </aside>

      <main>
        <div class="mb-6">
          <h1 class="font-display text-3xl font-extrabold text-brand-900">Mijn profiel</h1>
          <p class="mt-1 text-ink/60">Je gestelde vragen en antwoorden waarvan je hebt gezegd dat ze werken.</p>
        </div>

        <div v-if="pending" class="rounded-3xl border-2 border-dashed border-brand-100 p-10 text-center text-ink/50">
          Profielactiviteit laden…
        </div>

        <div v-else class="space-y-8">
          <section>
            <div class="mb-4 flex items-center justify-between gap-4">
              <h2 class="font-display text-xl font-bold text-brand-900">Jouw vragen</h2>
              <NuxtLink to="/vraag-stellen" class="text-sm font-bold text-brand-600 hover:text-brand-700">Nieuwe vraag</NuxtLink>
            </div>

            <div v-if="activity?.questions.length" class="grid gap-4 md:grid-cols-2">
              <QuestionCard v-for="question in activity.questions" :key="question.id" :question="question" />
            </div>
            <div v-else class="rounded-3xl border-2 border-dashed border-brand-100 p-8 text-center">
              <p class="text-ink/60">Je hebt nog geen vragen gesteld.</p>
              <NuxtLink to="/vraag-stellen" class="btn-primary mt-4">Stel je eerste vraag</NuxtLink>
            </div>
          </section>

          <section>
            <h2 class="mb-4 font-display text-xl font-bold text-brand-900">Antwoorden die werkten</h2>
            <div v-if="activity?.worked.length" class="space-y-4">
              <article v-for="vote in activity.worked" :key="vote.id" class="card p-5">
                <div class="flex flex-wrap items-center gap-2">
                  <NuxtLink
                    :to="`/vraag/${vote.answer.question.slug}`"
                    class="font-display text-lg font-bold leading-tight text-brand-900 hover:text-brand-700"
                  >
                    {{ vote.answer.question.title }}
                  </NuxtLink>
                  <AiBadge :model="vote.answer.ai_model" />
                </div>
                <p class="mt-3 line-clamp-3 text-sm leading-6 text-ink/70">{{ vote.answer.body }}</p>
                <NuxtLink :to="`/vraag/${vote.answer.question.slug}`" class="mt-3 inline-flex text-sm font-bold text-brand-600 hover:text-brand-700">
                  Bekijk antwoord →
                </NuxtLink>
              </article>
            </div>
            <div v-else class="rounded-3xl border-2 border-dashed border-brand-100 p-8 text-center text-ink/60">
              Nog geen antwoorden gemarkeerd als werkend. Klik bij een antwoord op “Werkt” om het hier terug te vinden.
            </div>
          </section>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Answer, Question } from '~/types/content';

definePageMeta({ middleware: 'auth' });

const authStore = useAuthStore();
const router = useRouter();
const api = useApi();
const resending = ref(false);
const resendMessage = ref('');

interface ProfileVote {
  id: number;
  value: number;
  answer: Answer & { question: Question };
}

interface ProfileActivity {
  questions: Question[];
  worked: ProfileVote[];
  stats: {
    questions: number;
    worked: number;
  };
}

const { data: activityResponse, pending } = await useAsyncData('profile-activity', () =>
  api.get<{ data: ProfileActivity }>('/profile/activity')
);
const activity = computed(() => activityResponse.value?.data ?? null);

const initials = computed(() =>
  (authStore.user?.name || '?')
    .split(' ')
    .map((w) => w[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
);

async function resendVerification() {
  resending.value = true;
  resendMessage.value = '';
  try {
    const response = await authStore.resendVerification();
    resendMessage.value = response.message;
  } catch (error: any) {
    resendMessage.value = error.message || 'Versturen mislukt.';
  } finally {
    resending.value = false;
  }
}

async function handleLogout() {
  try {
    await api.post('/logout');
  } catch {
    /* ignore */
  } finally {
    authStore.logout();
    router.push('/');
  }
}

usePageSeo({
  title: 'Mijn profiel',
  description: 'Bekijk je eigen vragen en stemmen op AI Weet Raad.',
  path: '/profiel',
  noindex: true,
});
</script>
