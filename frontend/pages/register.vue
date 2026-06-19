<template>
  <NuxtLayout name="auth">
    <div class="card p-8">
      <div class="mb-6 text-center">
        <h1 class="font-display text-3xl font-extrabold text-brand-900">Account aanmaken</h1>
        <p class="mt-2 text-sm text-ink/60">
          Maak een account om vragen te stellen en te stemmen op de antwoorden die jou het beste helpen.
        </p>
      </div>

      <button
        type="button"
        class="mb-4 flex w-full items-center justify-center gap-3 rounded-full border-2 border-brand-100 bg-white px-5 py-3 text-sm font-extrabold text-brand-900 shadow-soft transition hover:border-brand-200 hover:bg-brand-50"
        @click="registerWithGoogle"
      >
        <span class="grid h-6 w-6 place-items-center rounded-full bg-white text-base shadow-sm">G</span>
        Doorgaan met Google
      </button>

      <div class="mb-4 flex items-center gap-3 text-xs font-bold uppercase tracking-[0.16em] text-ink/35">
        <span class="h-px flex-1 bg-brand-100" />
        of
        <span class="h-px flex-1 bg-brand-100" />
      </div>

      <form @submit.prevent="handleRegister" class="space-y-4">
        <div v-if="error" class="rounded-2xl bg-blush-100 p-3 text-sm font-semibold text-blush-500">
          {{ error }}
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-bold text-brand-800">Naam</label>
          <input
            v-model="form.name"
            type="text"
            required
            autocomplete="name"
            class="field"
          />
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-bold text-brand-800">E-mailadres</label>
          <input
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            class="field"
          />
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-bold text-brand-800">Wachtwoord</label>
          <input
            v-model="form.password"
            type="password"
            required
            autocomplete="new-password"
            class="field"
          />
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-bold text-brand-800">
            Wachtwoord bevestigen
          </label>
          <input
            v-model="form.password_confirmation"
            type="password"
            required
            autocomplete="new-password"
            class="field"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="btn-primary w-full"
        >
          {{ loading ? 'Account aanmaken...' : 'Account aanmaken' }}
        </button>
      </form>

      <p class="mt-4 text-center text-sm text-ink/60">
        Heb je al een account?
        <NuxtLink to="/login" class="font-bold text-brand-600 hover:text-brand-700">
          Log in
        </NuxtLink>
      </p>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'guest',
  layout: false,
});

const authStore = useAuthStore();
const router = useRouter();

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const error = ref('');
const loading = ref(false);

usePageSeo({
  title: 'Account aanmaken',
  description: 'Maak een account aan om vragen te stellen en te stemmen op AI-antwoorden.',
  path: '/register',
  noindex: true,
});

async function handleRegister() {
  error.value = '';
  loading.value = true;

  try {
    await authStore.register(
      form.name,
      form.email,
      form.password,
      form.password_confirmation
    );
    router.push('/profiel');
  } catch (err: any) {
    if (err.data?.errors) {
      error.value = Object.values(err.data.errors).flat().join(', ');
    } else {
      error.value = err.message || 'Account aanmaken is mislukt.';
    }
  } finally {
    loading.value = false;
  }
}

function registerWithGoogle() {
  window.location.href = authStore.googleRedirectUrl();
}
</script>
