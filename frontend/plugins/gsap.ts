import { gsap } from 'gsap';

/**
 * GSAP setup with ScrollTrigger. Provides:
 *  - v-reveal   : fade/slide/scale a single element in on scroll
 *  - v-stagger  : reveal the direct children of a container with a stagger
 *  - $gsap, $reveal, $stagger : for ad-hoc timelines in components
 *
 * SSR-safe: ScrollTrigger is only imported/registered on the client and the
 * directive hooks (mounted) never run during SSR. Honours
 * prefers-reduced-motion by skipping animations.
 */
export default defineNuxtPlugin(async (nuxtApp) => {
  const isClient = typeof window !== 'undefined';
  const reduced =
    isClient &&
    window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

  if (isClient && !reduced) {
    const { ScrollTrigger } = await import('gsap/ScrollTrigger');
    gsap.registerPlugin(ScrollTrigger);
  }

  const reveal = (el: HTMLElement, opts: Record<string, any> = {}) => {
    if (reduced) {
      gsap.set(el, { opacity: 1, clearProps: 'transform' });
      return;
    }
    el.classList.remove('gsap-prep');
    gsap.from(el, {
      opacity: 0,
      y: opts.y ?? 28,
      x: opts.x ?? 0,
      scale: opts.scale ?? 1,
      duration: opts.duration ?? 0.7,
      delay: opts.delay ?? 0,
      ease: opts.ease ?? 'power3.out',
      scrollTrigger: { trigger: el, start: opts.start ?? 'top 88%', once: true },
    });
  };

  const stagger = (el: HTMLElement, opts: Record<string, any> = {}) => {
    const targets = Array.from(el.children) as HTMLElement[];
    if (!targets.length) return;
    if (reduced) {
      gsap.set(targets, { opacity: 1, clearProps: 'transform' });
      return;
    }
    el.classList.remove('gsap-prep');
    gsap.from(targets, {
      opacity: 0,
      y: opts.y ?? 32,
      scale: opts.scale ?? 0.97,
      duration: opts.duration ?? 0.6,
      ease: 'power3.out',
      stagger: opts.stagger ?? 0.08,
      scrollTrigger: { trigger: el, start: opts.start ?? 'top 85%', once: true },
    });
  };

  nuxtApp.vueApp.directive('reveal', {
    mounted(el: HTMLElement, binding) {
      reveal(el, binding.value || {});
    },
  });

  nuxtApp.vueApp.directive('stagger', {
    mounted(el: HTMLElement, binding) {
      stagger(el, binding.value || {});
    },
  });

  return {
    provide: { gsap, reveal, stagger },
  };
});
