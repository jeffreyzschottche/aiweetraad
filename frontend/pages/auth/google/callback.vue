<template>
  <NuxtLayout name="auth">
    <div class="card p-8 text-center">
      <div v-if="loading" class="py-8">
        <div class="mx-auto mb-5 h-16 w-16 rounded-full border-4 border-brand-100 border-t-brand-600 animate-spin" />
        <h1 class="font-display text-3xl font-extrabold text-brand-900">Google-login afronden</h1>
        <p class="mt-2 text-sm text-ink/60">We maken je sessie klaar.</p>
      </div>

      <div v-else class="py-8">
        <div class="mx-auto mb-5 grid h-16 w-16 place-items-center rounded-3xl bg-blush-100 text-blush-500">
          <svg class="h-9 w-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        <h1 class="font-display text-3xl font-extrabold text-brand-900">Google-login mislukt</h1>
        <p class="mt-2 text-sm text-ink/60">{{ error }}</p>
        <NuxtLink to="/login" class="btn-primary mt-6 w-full">
          Terug naar inloggen
        </NuxtLink>
      </div>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
definePageMeta({
  layout: false,
  middleware: 'guest',
});

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const loading = ref(true);
const error = ref('');

usePageSeo({
  title: 'Google-login afronden',
  description: 'Rond je Google-login af.',
  path: '/auth/google/callback',
  noindex: true,
});

onMounted(async () => {
  const code = route.query.code as string | undefined;

  if (!code) {
    error.value = 'Google-login bevat geen geldige code.';
    loading.value = false;
    return;
  }

  try {
    await authStore.exchangeGoogleCode(code);
    await router.replace('/profiel');
  } catch (err: any) {
    error.value = err?.data?.message || err?.message || 'Google-login is mislukt.';
    loading.value = false;
  }
});
</script>
