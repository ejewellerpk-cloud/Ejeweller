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
                    // Leave heavy admin-only libs out of any shared bucket.
                    if (
                        id.includes('node_modules/apexcharts') ||
                        id.includes('node_modules/vue3-apexcharts') ||
                        id.includes('node_modules/quill') ||
                        id.includes('node_modules/vue3-quill') ||
                        id.includes('node_modules/lodash') ||
                        id.includes('node_modules/firebase')
                    ) {
                        return;
                    }
                    // Do NOT put vue / vue-router / @vue in a separate chunk
                    // (TDZ: "Cannot access before initialization").
                    // Split secondary libs so storefront is not one ~666KB file
                    // (large single downloads often hit ERR_CONNECTION_RESET on host).
                    if (
                        id.includes('node_modules/vue-i18n') ||
                        id.includes('node_modules/@intlify')
                    ) {
                        return 'vendor-i18n';
                    }
                    if (
                        id.includes('node_modules/vuex') ||
                        id.includes('node_modules/vuex-persistedstate')
                    ) {
                        return 'vendor-vuex';
                    }
                    if (id.includes('node_modules/axios')) {
                        return 'vendor-axios';
                    }
                    if (id.includes('node_modules/vue-toastification')) {
                        return 'vendor-toast';
                    }
                    if (
                        id.includes('node_modules/sweetalert2') ||
                        id.includes('node_modules/vue3-simple-alert')
                    ) {
                        return 'vendor-alert';
                    }
                    if (id.includes('node_modules/vue-next-select')) {
                        return 'vendor-select';
                    }
                    if (
                        id.includes('node_modules/@vueuse/head') ||
                        id.includes('node_modules/@unhead')
                    ) {
                        return 'vendor-head';
                    }
                }
            }
        }
    }
});