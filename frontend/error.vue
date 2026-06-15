<template>
  <NuxtLayout>
    <div class="container-app py-16">
      <section class="mx-auto max-w-3xl overflow-hidden rounded-3xl border border-brand-100 bg-white text-center shadow-card">
        <div class="bg-gradient-to-r from-brand-600 to-brand-700 px-6 py-10 text-white">
          <p class="text-sm font-extrabold uppercase tracking-wide text-white/70">Pagina niet gevonden</p>
          <h1 class="mt-2 font-display text-6xl font-extrabold md:text-8xl">404</h1>
          <p class="mx-auto mt-4 max-w-xl text-white/75">
            Deze pagina bestaat niet of is verplaatst. Je kunt terug naar home, zoeken of direct een nieuwe vraag stellen.
          </p>
        </div>

        <div class="grid gap-3 p-6 sm:grid-cols-3">
          <NuxtLink to="/" class="btn-primary">Home</NuxtLink>
          <NuxtLink to="/zoeken" class="btn-ghost">Zoeken</NuxtLink>
          <NuxtLink to="/vraag-stellen" class="btn-accent">Vraag stellen</NuxtLink>
        </div>
      </section>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
defineProps<{
  error: {
    statusCode?: number;
    statusMessage?: string;
    message?: string;
  };
}>();

useHead({ title: 'Pagina niet gevonden' });
</script>
