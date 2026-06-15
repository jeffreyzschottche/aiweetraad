<template>
  <div class="flex min-h-screen flex-col">
    <AppHeader />
    <div class="container-app pt-4">
      <AdSlot format="leaderboard" />
    </div>
    <main class="flex-1">
      <slot />
    </main>
    <AppFooter />
  </div>
</template>
