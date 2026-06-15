<template>
  <div class="container-app grid gap-10 py-10 lg:grid-cols-[1fr_300px]">
    <div class="mx-auto w-full max-w-2xl">
      <div v-reveal class="text-center">
        <div class="mx-auto mb-3 grid h-16 w-16 place-items-center rounded-3xl bg-teal2-200 text-3xl shadow-card">✉️</div>
        <h1 class="font-display text-3xl font-bold text-brand-900 md:text-4xl">Contact</h1>
        <p class="mt-2 text-ink/60">
          Vraag, opmerking of interesse om te adverteren? Laat hieronder een bericht achter.
        </p>
      </div>

      <div v-if="success" class="mt-6 rounded-3xl bg-teal2-200 p-8 text-center">
        <div class="mb-2 text-4xl">🎉</div>
        <p class="font-bold text-teal2-600">{{ success }}</p>
      </div>

      <form v-else v-reveal="{ delay: 0.1 }" class="card mt-6 space-y-4 p-6" @submit.prevent="submit">
        <div v-if="error" class="rounded-2xl bg-blush-100 p-3 text-sm font-semibold text-blush-500">{{ error }}</div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-bold text-brand-800">Naam</label>
            <input v-model="form.name" required class="field" />
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-bold text-brand-800">E-mail</label>
            <input v-model="form.email" type="email" required class="field" />
          </div>
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-bold text-brand-800">Onderwerp</label>
          <input v-model="form.subject" class="field" />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-bold text-brand-800">Bericht</label>
          <textarea v-model="form.message" rows="5" required class="field" />
        </div>
        <button type="submit" class="btn-primary w-full" :disabled="loading">
          {{ loading ? 'Versturen…' : 'Verstuur bericht' }}
        </button>
      </form>
    </div>

    <aside class="space-y-6">
      <AdSlot format="sidebar" />
    </aside>
  </div>
</template>

<script setup lang="ts">
const api = useApi();

const form = reactive({ name: '', email: '', subject: '', message: '' });
const loading = ref(false);
const error = ref('');
const success = ref('');

async function submit() {
  error.value = '';
  loading.value = true;
  try {
    const res = await api.post<{ message: string }>('/contact', form);
    success.value = res.message;
  } catch (err: any) {
    error.value = err?.data?.errors
      ? Object.values(err.data.errors).flat().join(' ')
      : err?.message || 'Er ging iets mis.';
  } finally {
    loading.value = false;
  }
}

useHead({ title: 'Contact' });
</script>

<style scoped>
.input {
  @apply w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-100;
}
</style>
