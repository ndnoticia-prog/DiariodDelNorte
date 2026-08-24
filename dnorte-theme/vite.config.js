import { resolve } from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
  // Relativa, no la raíz '/' por defecto de Vite: este tema nunca se sirve
  // desde la raíz del dominio (vive en /wp-content/themes/dnorte-theme/), así
  // que una URL absoluta tipo url(/assets/foo.woff2) generada por Vite para
  // las fuentes de app.scss apunta a la raíz del sitio en vez de a dist/assets/
  // — bug real encontrado en la verificación en el navegador de v0.1.0-alpha.17
  // (las @font-face de app.scss devolvían 404 y el navegador caía a la pila de
  // system-ui, sin que ningún error de PHP lo delatara).
  base: './',
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
