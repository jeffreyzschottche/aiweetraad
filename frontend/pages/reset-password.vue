<template>
  <NuxtLayout name="auth">
    <div class="card p-8">
      <div class="mb-6 text-center">
        <h1 class="font-display text-3xl font-extrabold text-brand-900">Nieuw wachtwoord</h1>
        <p class="mt-2 text-sm text-ink/60">
          Kies een sterk nieuw wachtwoord voor je AI Weet Raad-account.
        </p>
      </div>

      <div v-if="success" class="rounded-2xl border border-green-100 bg-green-50 p-4 text-sm font-semibold text-green-700">
        {{ success }}
        <NuxtLink to="/login" class="btn-primary mt-4 w-full">
          Inloggen
        </NuxtLink>
      </div>

      <form v-else @submit.prevent="handleSubmit" class="space-y-4">
        <div v-if="!form.token" class="rounded-2xl bg-blush-100 p-3 text-sm font-semibold text-blush-500">
          Deze resetlink mist een token. Vraag een nieuwe resetlink aan.
        </div>

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
          <label class="mb-1.5 block text-sm font-bold text-brand-800">Nieuw wachtwoord</label>
          <input
            v-model="form.password"
            type="password"
            required
            autocomplete="new-password"
            class="field"
          />
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-bold text-brand-800">Herhaal wachtwoord</label>
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
          :disabled="loading || !form.token"
          class="btn-primary w-full"
        >
          {{ loading ? 'Opslaan...' : 'Wachtwoord wijzigen' }}
        </button>
      </form>

      <p class="mt-4 text-center text-sm text-ink/60">
        <NuxtLink to="/forgot-password" class="font-bold text-brand-600 hover:text-brand-700">
          Nieuwe resetlink aanvragen
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
const route = useRoute();

const form = reactive({
  token: (route.query.token as string) || '',
  email: (route.query.email as string) || '',
  password: '',
  password_confirmation: '',
});

const error = ref('');
const success = ref('');
const loading = ref(false);

async function handleSubmit() {
  if (!form.token) return;

  error.value = '';
  success.value = '';
  loading.value = true;

  try {
    const response = await authStore.resetPassword(
      form.token,
      form.email,
      form.password,
      form.password_confirmation
    );
    success.value = response.message;
    form.password = '';
    form.password_confirmation = '';
  } catch (err: any) {
    if (err.data?.errors) {
      error.value = Object.values(err.data.errors).flat().join(' ');
    } else {
      error.value = err.message || 'Wachtwoord wijzigen is mislukt.';
    }
  } finally {
    loading.value = false;
  }
}
</script>
