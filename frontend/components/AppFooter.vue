<template>
  <footer class="relative mt-20 overflow-hidden bg-brand-700 text-white">
    <div class="absolute inset-x-0 top-0 -translate-y-1/2">
      <svg viewBox="0 0 1440 80" class="h-12 w-full" preserveAspectRatio="none">
        <path d="M0 40c240 40 480 40 720 0s480-40 720 0v80H0z" fill="#07585a" />
      </svg>
    </div>
    <div class="container-app grid gap-10 pt-16 pb-12 md:grid-cols-4">
      <div>
        <div class="flex items-center gap-2.5">
          <span class="grid h-10 w-10 place-items-center rounded-2xl bg-white text-brand-700">
            <span class="font-display text-lg font-bold">ai</span>
          </span>
          <span class="font-display text-lg font-bold">AI weet raad</span>
        </div>
        <p class="mt-3 max-w-xs text-sm text-white/70">
          Stel je dagelijkse vraag en vergelijk de antwoorden van meerdere AI’s. Stem op het
          advies dat jou het beste helpt. 🤍
        </p>
      </div>

      <div>
        <h3 class="mb-3 font-display text-sm font-bold">Ontdek</h3>
        <ul class="space-y-2 text-sm text-white/70">
          <li><NuxtLink to="/categorieen" class="hover:text-blush-300">Categorieën</NuxtLink></li>
          <li><NuxtLink to="/vraag-stellen" class="hover:text-blush-300">Stel een vraag</NuxtLink></li>
          <li><NuxtLink to="/zoeken" class="hover:text-blush-300">Zoeken</NuxtLink></li>
        </ul>
      </div>

      <div>
        <h3 class="mb-3 font-display text-sm font-bold">Informatie</h3>
        <ul class="space-y-2 text-sm text-white/70">
          <li><NuxtLink to="/over-ons" class="hover:text-blush-300">Over ons</NuxtLink></li>
          <li><NuxtLink to="/adverteren" class="hover:text-blush-300">Adverteren</NuxtLink></li>
          <li><NuxtLink to="/contact" class="hover:text-blush-300">Contact</NuxtLink></li>
          <li><NuxtLink to="/privacy" class="hover:text-blush-300">Privacybeleid</NuxtLink></li>
          <li><NuxtLink to="/voorwaarden" class="hover:text-blush-300">Voorwaarden</NuxtLink></li>
        </ul>
      </div>

      <div>
        <h3 class="mb-3 font-display text-sm font-bold">Nieuwsbrief</h3>
        <NewsletterSignup compact />
      </div>
    </div>

    <div class="border-t border-white/10 py-5">
      <div class="container-app flex flex-col items-center justify-between gap-2 text-xs text-white/50 sm:flex-row">
        <p>© {{ year }} AI weet raad. Antwoorden gegenereerd door AI — gebruik op eigen inzicht.</p>
        <p>Gemaakt met Nuxt &amp; Laravel.</p>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
const year = new Date().getFullYear();
</script>
