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
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (!id.includes('node_modules')) return undefined;
          if (id.includes('@fullcalendar')) return 'vendor-fullcalendar';
          if (/[\\/]node_modules[\\/](react|react-dom|scheduler)[\\/]/.test(id)) return 'vendor-react';
          if (id.includes('@radix-ui') || id.includes('@headlessui')) return 'vendor-ui';
          if (id.includes('lucide-react')) return 'vendor-icons';

          return 'vendor';
        },
      },
    },
  },
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        headers: {
          Host: 'api.hociatec.fr',
        },
      },
    },
  },
  resolve: {
    alias: {
      '@': resolve(rootDir, 'src'),
    },
  },
}));
