<template>
  <div class="flex min-h-screen flex-col">
    <AppHeader />
    <div v-if="adsEnabled" class="container-app pt-4">
      <AdSlot format="leaderboard" />
    </div>
    <main class="flex-1">
      <slot />
    </main>
    <AppFooter />
  </div>
</template>

<script setup lang="ts">
const config = useRuntimeConfig();
const adsEnabled = computed(() => config.public.adsEnabled === true || config.public.adsEnabled === 'true');
</script>
