<template>
  <div ref="root" class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
    <span class="blob absolute -left-16 top-4 h-56 w-56 rounded-full bg-blush-300/50 blur-2xl" />
    <span class="blob absolute right-0 top-24 h-64 w-64 rounded-full bg-teal2-300/40 blur-2xl" />
    <span class="blob absolute left-1/3 -bottom-10 h-48 w-48 rounded-full bg-brand-200/40 blur-2xl" />
    <span class="dot absolute left-[12%] top-[30%] text-3xl">💡</span>
    <span class="dot absolute right-[14%] top-[20%] text-3xl">✨</span>
    <span class="dot absolute right-[26%] bottom-[18%] text-2xl">🤖</span>
    <span class="dot absolute left-[20%] bottom-[12%] text-2xl">💬</span>
  </div>
</template>

<script setup lang="ts">
const { $gsap } = useNuxtApp();
const root = ref<HTMLElement | null>(null);

onMounted(() => {
  if (!root.value || !$gsap) return;
  const blobs = root.value.querySelectorAll('.blob');
  const dots = root.value.querySelectorAll('.dot');

  blobs.forEach((b, i) => {
    $gsap.to(b, {
      y: i % 2 ? 26 : -26,
      x: i % 2 ? -18 : 18,
      duration: 6 + i,
      repeat: -1,
      yoyo: true,
      ease: 'sine.inOut',
    });
  });
  dots.forEach((d, i) => {
    $gsap.fromTo(
      d,
      { y: 0, rotate: -6 },
      { y: -16, rotate: 6, duration: 3 + i * 0.4, repeat: -1, yoyo: true, ease: 'sine.inOut' }
    );
  });
});
</script>
