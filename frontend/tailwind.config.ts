import type { Config } from 'tailwindcss';

export default {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#fff8ed',
          100: '#fff1d8',
          200: '#f7d8a8',
          300: '#f6bd6a',
          400: '#f39a20',
          500: '#ef6b3a',
          600: '#c9521f',
          700: '#a94f16',
          800: '#5a3320',
          900: '#12110f',
        },
      },
      boxShadow: {
        elevated: '0 20px 45px rgba(61, 51, 40, 0.18)',
      },
      fontFamily: {
        sans: ['"Aptos"', '"Segoe UI"', 'system-ui', 'sans-serif'],
      },
      maxWidth: {
        '8xl': '88rem',
      },
    },
  },
  plugins: [],
} satisfies Config;
