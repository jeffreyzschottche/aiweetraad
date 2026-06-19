<template>
  <header
    ref="header"
    class="sticky top-0 z-40 border-b border-brand-100/60 bg-cream/90 backdrop-blur transition-shadow"
  >
    <div class="container-app flex h-16 items-center justify-between gap-4 md:h-[68px]">
      <NuxtLink to="/" class="flex min-w-0 items-center gap-3">
        <span class="relative shrink-0">
          <img
            src="/images/aiweetraadlogo.png"
            alt="AI Weet Raad logo"
            class="h-11 w-11 rounded-2xl object-cover shadow-soft"
          />
          <span class="absolute -bottom-1 -right-1 h-3.5 w-3.5 rounded-full border-2 border-cream bg-teal2-500" />
        </span>
        <span class="font-display text-lg font-bold tracking-tight text-brand-800">
          AI&nbsp;weet&nbsp;raad
        </span>
      </NuxtLink>

      <nav class="hidden items-center gap-1 rounded-full border border-brand-100 bg-white/80 p-1 shadow-card md:flex">
        <NuxtLink
          v-for="link in navLinks"
          :key="link.to"
          :to="link.to"
          class="rounded-full px-3.5 py-2 text-sm font-semibold text-ink/70 transition hover:bg-white hover:text-brand-700"
          active-class="bg-white text-brand-700 shadow-card"
        >
          {{ link.label }}
        </NuxtLink>
      </nav>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="grid h-10 w-10 place-items-center rounded-full text-brand-600 transition hover:bg-white"
          aria-label="Zoeken"
          @click="openSearch"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" stroke-linecap="round" />
          </svg>
        </button>

        <NuxtLink to="/vraag-stellen" class="btn-primary hidden sm:inline-flex">
          Stel je vraag
        </NuxtLink>

        <NuxtLink v-if="authStore.isLoggedIn" to="/profiel" class="btn-ghost hidden sm:inline-flex">
          {{ authStore.user?.name?.split(' ')[0] || 'Profiel' }}
        </NuxtLink>
        <NuxtLink v-else to="/login" class="btn-ghost hidden sm:inline-flex">Inloggen</NuxtLink>

        <button
          class="grid h-10 w-10 place-items-center rounded-full text-brand-700 hover:bg-white md:hidden"
          aria-label="Menu"
          @click="mobileOpen = !mobileOpen"
        >
          <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
            <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round" />
          </svg>
        </button>
      </div>
    </div>

    <Transition name="slide">
      <div v-if="mobileOpen" class="border-t border-brand-100/60 bg-cream md:hidden">
        <div class="container-app flex flex-col gap-1 py-3">
          <NuxtLink
            v-for="link in navLinks"
            :key="link.to"
            :to="link.to"
            class="rounded-2xl px-4 py-2.5 text-sm font-semibold text-ink/80 hover:bg-white"
            @click="mobileOpen = false"
          >
            {{ link.label }}
          </NuxtLink>
          <NuxtLink to="/vraag-stellen" class="btn-primary mt-2" @click="mobileOpen = false">
            Stel je vraag
          </NuxtLink>
        </div>
      </div>
    </Transition>
    <div class="h-1 bg-gradient-to-r from-brand-600 via-teal2-500 to-blush-300" />
    <SearchModal :open="searchOpen" @close="searchOpen = false" />
  </header>
</template>

<script setup lang="ts">
const authStore = useAuthStore();
const mobileOpen = ref(false);
const searchOpen = ref(false);
const header = ref<HTMLElement | null>(null);

const navLinks = [
  { to: '/', label: 'Home' },
  { to: '/categorieen', label: 'Categorieën' },
  { to: '/vraag-stellen', label: 'Vraag stellen' },
  { to: '/over-ons', label: 'Over ons' },
  { to: '/adverteren', label: 'Adverteren' },
  { to: '/contact', label: 'Contact' },
];

function onScroll() {
  if (!header.value) return;
  header.value.classList.toggle('shadow-card', window.scrollY > 8);
}

function openSearch() {
  mobileOpen.value = false;
  searchOpen.value = true;
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
});
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
  transition: all 0.2s ease;
}
.slide-enter-from,
.slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
