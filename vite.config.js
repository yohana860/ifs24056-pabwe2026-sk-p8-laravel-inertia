
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.jsx',
            ],
            refresh: true,
        }),

        react(),

        tailwindcss(),
    ],

    build: {
        // Menghasilkan source map untuk JavaScript produksi.
        sourcemap: true,

        // Batas peringatan ukuran chunk.
        chunkSizeWarningLimit: 1000,

        // Minifikasi JavaScript dan CSS.
        minify: 'esbuild',
        cssMinify: true,
    },

    server: {
        watch: {
            ignored: [
                '**/storage/framework/views/**',
            ],
        },
    },
});
