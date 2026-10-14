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
                    // Do NOT dump all node_modules into one shared "vendor" chunk —
                    // that forced storefront to download admin-only deps (~900KB).
                    // Do NOT force a shared swiper chunk either — it leaked into the
                    // storefront critical path via cart/product list. Let Rollup split.
                    // Do NOT split vue / vue-router / vuex / @vue into a separate chunk
                    // (TDZ: "Cannot access before initialization").
                }
            }
        }
    }
});