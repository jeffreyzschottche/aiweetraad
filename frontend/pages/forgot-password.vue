<template>
  <NuxtLayout name="auth">
    <div class="card p-8">
      <div class="mb-6 text-center">
        <h1 class="font-display text-3xl font-extrabold text-brand-900">Wachtwoord vergeten?</h1>
        <p class="mt-2 text-sm text-ink/60">
          Vul je e-mailadres in. Als je account bestaat, sturen we een link om een nieuw wachtwoord te kiezen.
        </p>
      </div>

      <div v-if="success" class="mb-4 rounded-2xl border border-green-100 bg-green-50 p-4 text-sm font-semibold text-green-700">
        {{ success }}
      </div>

      <form v-else @submit.prevent="handleSubmit" class="space-y-4">
        <div v-if="error" class="rounded-2xl bg-blush-100 p-3 text-sm font-semibold text-blush-500">
          {{ error }}
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-bold text-brand-800">E-mailadres</label>
          <input
            v-model="email"
            type="email"
            required
            autocomplete="email"
            class="field"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="btn-primary w-full"
        >
          {{ loading ? 'Versturen...' : 'Resetlink versturen' }}
        </button>
      </form>

      <p class="mt-4 text-center text-sm text-gray-600">
        <NuxtLink to="/login" class="font-bold text-brand-600 hover:text-brand-700">
          Terug naar inloggen
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

const email = ref('');
const error = ref('');
const success = ref('');
const loading = ref(false);

async function handleSubmit() {
  error.value = '';
  success.value = '';
  loading.value = true;

  try {
    const response = await authStore.forgotPassword(email.value);
    success.value = response.message;
  } catch (err: any) {
    if (err.data?.errors) {
      error.value = Object.values(err.data.errors).flat().join(' ');
    } else {
      error.value = err.message || 'Resetlink versturen is mislukt.';
    }
  } finally {
    loading.value = false;
  }
}
</script>
