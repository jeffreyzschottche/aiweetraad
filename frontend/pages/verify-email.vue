<template>
  <NuxtLayout name="auth">
    <div class="rounded-lg bg-white p-8 text-center shadow">
      <div v-if="loading" class="py-8">
        <p class="text-gray-600">Je e-mailadres bevestigen...</p>
      </div>

      <div v-else-if="success" class="py-8">
        <div class="mb-4 text-green-600">
          <svg class="mx-auto h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h3 class="mb-2 text-xl font-semibold">E-mailadres bevestigd</h3>
        <p class="mb-4 text-gray-600">Je e-mailadres is succesvol bevestigd.</p>
        <NuxtLink to="/profiel" class="inline-block rounded bg-blue-600 px-6 py-2 text-white hover:bg-blue-700">
          Naar mijn profiel
        </NuxtLink>
      </div>

      <div v-else class="py-8">
        <div class="mb-4 text-red-600">
          <svg class="mx-auto h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        <h3 class="mb-2 text-xl font-semibold">Bevestigen mislukt</h3>
        <p class="mb-4 text-gray-600">{{ error }}</p>
        <NuxtLink to="/login" class="inline-block rounded bg-blue-600 px-6 py-2 text-white hover:bg-blue-700">
          Naar inloggen
        </NuxtLink>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import { apiFetch } from '~/services/apiFetch';

definePageMeta({
  layout: false,
});

const route = useRoute();
const loading = ref(true);
const success = ref(false);
const error = ref('');

onMounted(async () => {
  const verificationUrl = route.query.url as string;

  if (!verificationUrl) {
    error.value = 'Ongeldige verificatielink.';
    loading.value = false;
    return;
  }

  try {
    await apiFetch(verificationUrl);
    success.value = true;
  } catch (err: any) {
    error.value = err.message || 'E-mailadres bevestigen is mislukt.';
  } finally {
    loading.value = false;
  }
});
</script>
