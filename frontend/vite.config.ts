import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react-swc';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const rootDir = dirname(fileURLToPath(import.meta.url));

export default defineConfig(() => ({
  base: '/',
  plugins: [react()],
  build: {
    chunkSizeWarningLimit: 900,
  },
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
  resolve: {
    alias: {
      '@': resolve(rootDir, 'src'),
    },
  },
}));
