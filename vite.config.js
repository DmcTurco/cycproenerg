import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pages/chart-demo.js',
                'resources/js/excel-uploader.js',
                'resources/js/tecnico-solicitudes.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
