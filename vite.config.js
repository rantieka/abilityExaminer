import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';


export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/sass/app.scss',
        'resources/sass/custom/landing.scss',
        'resources/sass/custom/filament.scss',
        'resources/sass/test-layout.scss',
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/js/test-background.js',
        'resources/js/test-timer.js'
      ],
      refresh: true,
    }),
  ],
  css: {
    preprocessorOptions: {
      scss: {
        quietDeps: true,
        silenceDeprecations: ['legacy-js-api', 'import', 'if-function', 'color-functions'],
      },
    },
  },
  server: {
    watch: {
      ignored: ['**/storage/framework/views/**'],
    },
  },
});
