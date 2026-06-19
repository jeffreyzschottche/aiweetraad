<template>
  <form :class="compact ? '' : 'card overflow-hidden p-6'" @submit.prevent="submit">
    <h3 v-if="!compact" class="font-display text-lg font-bold text-brand-900">
      💌 Schrijf je in voor de nieuwsbrief
    </h3>
    <p v-if="!compact" class="mt-1 text-sm text-ink/60">
      De handigste vragen en antwoorden, elke week in je inbox.
    </p>

    <div class="mt-3 flex flex-col gap-2">
      <input
        v-model="email"
        type="email"
        required
        placeholder="jouw@email.nl"
        class="field"
      />
      <button type="submit" class="btn-primary whitespace-nowrap" :disabled="loading">
        {{ loading ? 'Aanmelden...' : 'Aanmelden' }}
      </button>
    </div>
    <p v-if="error" class="mt-2 text-xs font-bold text-blush-500">{{ error }}</p>
    <p v-if="done" class="mt-2 text-xs font-bold text-teal2-600">Bedankt voor je aanmelding! 🎉</p>
  </form>
</template>

<script setup lang="ts">
defineProps<{ compact?: boolean }>();

const api = useApi();
const email = ref('');
const done = ref(false);
const loading = ref(false);
const error = ref('');

async function submit() {
  if (!email.value) return;
  done.value = false;
  error.value = '';
  loading.value = true;

  try {
    const res = await api.post<{ message: string }>('/newsletter', { email: email.value });
    done.value = true;
    email.value = '';
  } catch (err: any) {
    error.value = err?.data?.errors
      ? Object.values(err.data.errors).flat().join(' ')
      : err?.message || 'Aanmelden lukte niet.';
  } finally {
    loading.value = false;
  }
}
</script>
