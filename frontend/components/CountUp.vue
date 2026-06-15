<template>
  <span ref="el">{{ display }}</span>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{ value: number; duration?: number }>(), {
  duration: 1.4,
});

const { $gsap } = useNuxtApp();
const el = ref<HTMLElement | null>(null);
const display = ref('0');
const obj = reactive({ n: 0 });

function run() {
  if (!$gsap) {
    display.value = String(props.value);
    return;
  }
  $gsap.to(obj, {
    n: props.value,
    duration: props.duration,
    ease: 'power2.out',
    onUpdate: () => {
      display.value = Math.round(obj.n).toLocaleString('nl-NL');
    },
  });
}

onMounted(() => {
  // Count up once the element scrolls into view.
  if (typeof IntersectionObserver === 'undefined') return run();
  const io = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting) {
        run();
        io.disconnect();
      }
    },
    { threshold: 0.4 }
  );
  if (el.value) io.observe(el.value);
});

watch(() => props.value, (v) => (display.value = String(v)));
</script>
