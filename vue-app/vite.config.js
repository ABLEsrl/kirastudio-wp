import { resolve } from 'path';
import vue from '@vitejs/plugin-vue';
import vuetify from 'vite-plugin-vuetify';
import { defineConfig } from 'vite';

const prefixAllSelectors = () => ({
  postcssPlugin: 'prefix-all-selectors',
  Rule(rule) {
    // Leave @keyframes block selectors alone
    if (rule.parent?.type === 'atrule' && /keyframes/.test(rule.parent.name)) return;
    rule.selector = rule.selectors
      .map(s => (/^(html|body|\*|:root)/.test(s) ? s : `html body ${s}`))
      .join(', ');
  },
});
prefixAllSelectors.postcss = true;

export default defineConfig(({ mode }) => {
  const outDir = mode === 'drupal'
    ? '../drupal/kira_studio/assets/dist'
    : mode === 'standalone'
      ? '../standalone/dist'
      : '../wp/ablesrl-kirastudio/assets/dist';

  return {
    plugins: [
      vue(),
      vuetify({ autoImport: true }),
    ],

    css: {
      postcss: {
        plugins: [prefixAllSelectors],
      },
    },

    base: './',

    resolve: {
      alias: {
        '@': resolve(__dirname, './src'),
      },
    },

    build: {
      outDir,
      emptyOutDir: true,
      manifest: true,
      target: 'es2020',
      cssCodeSplit: true,
      reportCompressedSize: false,

      rollupOptions: {
        input: resolve(__dirname, './src/main.js'),

        output: {
          entryFileNames: 'js/[name]-[hash].js',
          chunkFileNames: 'js/[name]-[hash].js',
          assetFileNames: (info) => {
            if (info.name?.endsWith('.css')) return 'css/[name]-[hash][extname]';
            if (/\.(png|jpe?g|gif|svg|webp|ico)$/i.test(info.name ?? '')) return 'img/[name]-[hash][extname]';
            if (/\.(woff2?|ttf|eot|otf)$/i.test(info.name ?? '')) return 'fonts/[name]-[hash][extname]';
            return 'assets/[name]-[hash][extname]';
          },

          manualChunks: (id) => {
            if (id.includes('node_modules')) {
              if (id.includes('vuetify'))         return 'vendor-vuetify';
              if (id.includes('markdown-it'))     return 'vendor-markdown';
              if (id.includes('chart.js') || id.includes('chartjs')) return 'vendor-charts';
              if (id.includes('tippy') || id.includes('vue-tippy'))  return 'vendor-tippy';
              return 'vendor-vue';
            }
          },
        },
      },
    },
  };
});
