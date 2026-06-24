import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        tailwindcss(),
    ],
    publicDir: false,
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: false,
        rollupOptions: {
            input: {
                app: resolve(__dirname, 'resources/css/app.css'),
            },
            output: {
                assetFileNames: 'app.css',
                entryFileNames: 'app.js',
            },
        },
    },
});
