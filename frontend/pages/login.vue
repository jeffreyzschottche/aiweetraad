<template>
  <NuxtLayout name="auth">
    <div class="card p-8">
      <div class="mb-6 text-center">
        <h1 class="font-display text-3xl font-extrabold text-brand-900">Inloggen</h1>
        <p class="mt-2 text-sm text-ink/60">
          Log in om vragen te stellen, antwoorden te beoordelen en je profiel te beheren.
        </p>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div v-if="error" class="rounded-2xl bg-blush-100 p-3 text-sm font-semibold text-blush-500">
          {{ error }}
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
            autocomplete="current-password"
            class="field"
          />
        </div>

        <div class="text-right">
          <NuxtLink to="/forgot-password" class="text-sm font-bold text-brand-600 hover:text-brand-700">
            Wachtwoord vergeten?
          </NuxtLink>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="btn-primary w-full"
        >
          {{ loading ? 'Inloggen...' : 'Inloggen' }}
        </button>
      </form>

      <p class="mt-4 text-center text-sm text-ink/60">
        Nog geen account?
        <NuxtLink to="/register" class="font-bold text-brand-600 hover:text-brand-700">
          Maak een account
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
const route = useRoute();

const form = reactive({
  email: '',
  password: '',
});

const error = ref('');
const loading = ref(false);

usePageSeo({
  title: 'Inloggen',
  description: 'Log in op AI Weet Raad om vragen te stellen en te stemmen op antwoorden.',
  path: '/login',
  noindex: true,
});

async function handleLogin() {
  error.value = '';
  loading.value = true;

  try {
    await authStore.login(form.email, form.password);
    const redirect = (route.query.redirect as string) || '/profiel';
    router.push(redirect);
  } catch (err: any) {
    if (err.data?.errors) {
      error.value = Object.values(err.data.errors).flat().join(', ');
    } else {
      error.value = err.message || 'Inloggen is mislukt.';
    }
  } finally {
    loading.value = false;
  }
}
</script>
