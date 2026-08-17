import { resolve } from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    manifest: false,
    rollupOptions: {
      input: {
        app: resolve(__dirname, 'assets/js/app.js'),
      },
      output: {
        entryFileNames: 'app.js',
        assetFileNames: (asset) => (asset.name?.endsWith('.css') ? 'app.css' : 'assets/[name][extname]'),
      },
    },
  },
});
