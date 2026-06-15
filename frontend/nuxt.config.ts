import autoprefixer from 'autoprefixer';
import tailwindcss from 'tailwindcss';

export default defineNuxtConfig({
  srcDir: '.',
  compatibilityDate: '2024-01-09',

  modules: ['@pinia/nuxt'],

  css: ['~/assets/css/tailwind.css'],

  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.NUXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/api/v1',
      siteName: 'AI Weet Raad',
    },
  },

  app: {
    head: {
      htmlAttrs: { lang: 'nl' },
      titleTemplate: (title?: string) =>
        title ? `${title} — AI Weet Raad` : 'AI Weet Raad — Stel je vraag aan meerdere AI’s',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        {
          name: 'description',
          content:
            'Stel je dagelijkse vraag en krijg meteen antwoord van meerdere AI’s. Vergelijk de adviezen en stem op het beste antwoord.',
        },
      ],
      link: [
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Nunito:wght@400;500;600;700;800&display=swap',
        },
        { rel: 'icon', type: 'image/png', href: '/images/aiweetraadlogo.png' },
        { rel: 'apple-touch-icon', href: '/images/aiweetraadlogo.png' },
      ],
    },
  },

  devtools: { enabled: process.env.NODE_ENV !== 'production' },

  vite: {
    css: {
      postcss: {
        plugins: [tailwindcss(), autoprefixer()],
      },
    },
    server: {
      hmr: {
        port: 24679,
      },
    },
    build: {
      cssMinify: false,
    },
  },
});
