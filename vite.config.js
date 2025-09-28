import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        // host: true,
        // port: 5174,
        // hmr: {
        //     host: '0.0.0.0'
        // }
        host: '0.0.0.0',
        port: 9924,
    }
});
