<template>
  <NuxtLayout name="auth">
    <div class="card p-8 text-center">
      <div v-if="loading" class="py-8">
        <div class="mx-auto mb-5 grid h-16 w-16 place-items-center rounded-3xl bg-brand-50 text-brand-600">
          <svg class="h-8 w-8 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
          </svg>
        </div>
        <h1 class="font-display text-3xl font-extrabold text-brand-900">E-mailadres bevestigen</h1>
        <p class="mt-2 text-sm text-ink/60">We controleren je verificatielink.</p>
      </div>

      <div v-else-if="success" class="py-8">
        <div class="mx-auto mb-5 grid h-16 w-16 place-items-center rounded-3xl bg-green-50 text-green-700">
          <svg class="h-9 w-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h1 class="font-display text-3xl font-extrabold text-brand-900">E-mailadres bevestigd</h1>
        <p class="mt-2 text-sm text-ink/60">Je account is bevestigd en klaar voor gebruik.</p>
        <NuxtLink to="/profiel" class="btn-primary mt-6 w-full">
          Naar mijn profiel
        </NuxtLink>
      </div>

      <div v-else class="py-8">
        <div class="mx-auto mb-5 grid h-16 w-16 place-items-center rounded-3xl bg-blush-100 text-blush-500">
          <svg class="h-9 w-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        <h1 class="font-display text-3xl font-extrabold text-brand-900">Bevestigen mislukt</h1>
        <p class="mt-2 text-sm text-ink/60">{{ error }}</p>
        <NuxtLink to="/login" class="btn-primary mt-6 w-full">
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

usePageSeo({
  title: 'E-mailadres bevestigen',
  description: 'Bevestig je AI Weet Raad-account.',
  path: '/verify-email',
  noindex: true,
});

onMounted(async () => {
  const verificationUrl = route.query.url as string;

  if (!verificationUrl) {
    error.value = 'Ongeldige verificatielink.';
    loading.value = false;
    return;
  }

  try {
    await apiFetch(normalizeVerificationEndpoint(verificationUrl));
    success.value = true;
  } catch (err: any) {
    error.value = err.message || 'E-mailadres bevestigen is mislukt.';
  } finally {
    loading.value = false;
  }
});

function normalizeVerificationEndpoint(url: string): string {
  if (!url.startsWith('http')) {
    return stripApiPrefix(url);
  }

  const parsed = new URL(url);
  return stripApiPrefix(`${parsed.pathname}${parsed.search}`);
}

function stripApiPrefix(endpoint: string): string {
  return endpoint.replace(/^\/api\/v1/, '') || endpoint;
}
</script>
