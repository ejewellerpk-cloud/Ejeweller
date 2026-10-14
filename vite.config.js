import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/storefront.js',
                'resources/js/admin.js',
            ],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        extensions: ['.js', '.vue', '.json']
    },
    optimizeDeps: {
        include: ["quill"],
        exclude: ["swiper/vue", "swiper/types"]
    },
    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost'
        },
        watch: {
            // Admin settings (EnvEditor) update .env — ignore so Vite does not restart mid-session
            ignored: ['**/.env', '**/.env.*'],
        },
    },
    build: {
        chunkSizeWarningLimit: 1200,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return;
                    }
                    // Leave heavy admin-only libs out of the shared "vendor" bucket so
                    // storefront does not preload them. Rollup will emit async chunks.
                    if (
                        id.includes('node_modules/apexcharts') ||
                        id.includes('node_modules/vue3-apexcharts') ||
                        id.includes('node_modules/quill') ||
                        id.includes('node_modules/vue3-quill')
                    ) {
                        return;
                    }
                    if (id.includes('node_modules/swiper')) {
                        return 'vendor-swiper';
                    }
                    if (id.includes('node_modules/firebase')) {
                        return 'vendor-firebase';
                    }
                    if (
                        id.includes('node_modules/vue/') ||
                        id.includes('node_modules/vue-router') ||
                        id.includes('node_modules/vuex') ||
                        id.includes('node_modules/@vue/')
                    ) {
                        return 'vendor-vue-core';
                    }
                    return 'vendor';
                }
            }
        }
    }
});