import type { Config } from 'tailwindcss';

export default {
  content: [
    './app/**/*.{vue,ts}',
    './components/**/*.{vue,ts}',
    './layouts/**/*.{vue,ts}',
    './pages/**/*.{vue,ts}',
    './plugins/**/*.{js,ts}',
    './composables/**/*.{js,ts}',
    './app.vue',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#edfafa',
          100: '#d2f0f1',
          200: '#a9e1e2',
          300: '#73cbcd',
          400: '#39abad',
          500: '#168f91',
          600: '#006A6C',
          700: '#07585a',
          800: '#0b4648',
          900: '#0d3a3b',
        },
        // Soft blush pink accent
        blush: {
          100: '#fdecec',
          200: '#fbd8d7',
          300: '#f9c6c5',
          400: '#f4a4a2',
          500: '#ee7b78',
        },
        // Fresh teal accent
        teal2: {
          200: '#c7eced',
          300: '#9fdadd',
          400: '#79ccd0',
          500: '#73cacd',
          600: '#4fb3b7',
        },
        cream: '#fff7ef',
        ink: '#3e363c',
      },
      fontFamily: {
        sans: ['Nunito', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        display: ['Fredoka', 'Nunito', 'ui-sans-serif', 'sans-serif'],
      },
      boxShadow: {
        card: '0 2px 4px rgba(0,106,108,0.04), 0 12px 32px -12px rgba(0,106,108,0.18)',
        soft: '0 8px 30px -10px rgba(0,106,108,0.22)',
      },
      borderRadius: {
        xl2: '1.25rem',
        '3xl': '1.75rem',
        '4xl': '2.25rem',
      },
      keyframes: {
        float: {
          '0%,100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-14px)' },
        },
      },
      animation: {
        float: 'float 6s ease-in-out infinite',
      },
    },
  },
  plugins: [],
} satisfies Config;
