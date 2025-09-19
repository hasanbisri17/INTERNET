import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js'
                // 'resources/css/logo-drag-drop.css' - Dihapus karena sudah diimpor di app.js
            ],
            refresh: true,
        }),
    ],
});
