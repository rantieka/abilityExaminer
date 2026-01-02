import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';


export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/sass/app.scss', 'resources/sass/custom/landing.scss', 'resources/css/app.css', 'resources/js/app.js'],
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
