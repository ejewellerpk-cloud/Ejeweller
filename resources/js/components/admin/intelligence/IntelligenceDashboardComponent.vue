<template>
    <section class="p-4 sm:p-6 lg:p-8">
        <header class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Customer Intelligence</h1>
                <p class="text-slate-400 text-sm mt-1">Realtime ecommerce analytics · multi-site ready</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <select v-model="siteId" @change="onSiteChange"
                    class="bg-slate-800 border border-slate-700 rounded-xl px-4 py-2 text-sm">
                    <option v-for="s in sites" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <input type="date" v-model="from" @change="refresh"
                    class="bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm" />
                <input type="date" v-model="to" @change="refresh"
                    class="bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm" />
                <button type="button" @click="refresh"
                    class="px-4 py-2 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold">
                    Refresh
                </button>
            </div>
        </header>

        <div v-if="bootstrapping" class="rounded-2xl border border-slate-800 bg-slate-900/80 p-8 text-center text-slate-400">
            <i class="fa-solid fa-spinner fa-spin mr-2"></i> Setting up analytics…
        </div>

        <div v-else-if="loadError" class="rounded-2xl border border-red-900/50 bg-slate-900/80 p-8 text-center">
            <p class="text-red-300 mb-3">{{ loadError }}</p>
            <button type="button" @click="initSites"
                class="px-4 py-2 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold">
                Retry
            </button>
        </div>

        <div v-else-if="!sites.length" class="rounded-2xl border border-slate-800 bg-slate-900/80 p-8 text-center">
            <p class="text-slate-300 mb-3">No analytics site yet. Save keys in Settings or run setup below.</p>
            <div class="flex flex-wrap justify-center gap-3">
                <button type="button" @click="initSites"
                    class="px-4 py-2 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold">
                    Setup analytics now
                </button>
                <router-link :to="{ name: 'admin.settings.intelligenceAnalytics' }"
                    class="px-4 py-2 rounded-xl border border-slate-600 text-slate-200 text-sm font-semibold hover:bg-slate-800">
                    Open Intelligence Keys (Settings)
                </router-link>
            </div>
        </div>

        <template v-else>
            <!-- Realtime strip -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <KpiCard label="Live visitors" :value="realtime?.active_visitors ?? 0" accent="emerald" pulse />
                <KpiCard label="Page views today" :value="realtime?.page_views_today ?? 0" />
                <KpiCard label="Orders today" :value="realtime?.orders_today ?? 0" />
                <KpiCard label="Add to cart today" :value="realtime?.add_to_carts_today ?? 0" />
            </div>

            <!-- KPI row -->
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
                <KpiCard label="Visitors" :value="overview?.visitors ?? 0" />
                <KpiCard label="Sessions" :value="overview?.sessions ?? 0" />
                <KpiCard label="Page views" :value="overview?.page_views ?? 0" />
                <KpiCard label="Orders" :value="overview?.orders ?? 0" />
                <KpiCard label="Revenue" :value="formatMoney(overview?.revenue)" />
                <KpiCard label="Conversion" :value="(overview?.conversion_rate ?? 0) + '%'" />
            </div>

            <div class="grid lg:grid-cols-2 gap-6 mb-8">
                <!-- Funnel -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                    <h2 class="font-semibold mb-4">Conversion funnel</h2>
                    <div v-for="(step, i) in funnel" :key="i" class="mb-3">
                        <div class="flex justify-between text-sm mb-1">
                            <span>{{ step.step }}</span>
                            <span class="text-slate-400">{{ step.count }} · {{ step.conversion_pct }}%</span>
                        </div>
                        <div class="h-2 bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-orange-500 to-amber-400 transition-all duration-500"
                                :style="{ width: step.conversion_pct + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Sources -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                    <h2 class="font-semibold mb-4">Traffic sources</h2>
                    <ul class="space-y-2">
                        <li v-for="(src, i) in sources" :key="i"
                            class="flex justify-between text-sm py-2 border-b border-slate-800/80">
                            <span class="capitalize">{{ src.source || 'unknown' }}</span>
                            <span class="text-orange-400 font-medium">{{ src.sessions }}</span>
                        </li>
                        <li v-if="!sources.length" class="text-slate-500 text-sm">No data for range</li>
                    </ul>
                </div>
            </div>

            <!-- Top pages realtime + products -->
            <div class="grid lg:grid-cols-2 gap-6">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                    <h2 class="font-semibold mb-4">Top active pages (live)</h2>
                    <ul class="space-y-2 text-sm">
                        <li v-for="(p, i) in realtime?.top_pages ?? []" :key="i" class="flex justify-between">
                            <span class="truncate pr-4">{{ p.label }}</span>
                            <span>{{ p.value }}</span>
                        </li>
                    </ul>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                    <h2 class="font-semibold mb-4">Top viewed products</h2>
                    <ul class="space-y-2 text-sm">
                        <li v-for="(p, i) in products" :key="i" class="flex justify-between">
                            <span>Product #{{ p.product_id }}</span>
                            <span class="text-orange-400">{{ p.views }} views</span>
                        </li>
                        <li v-if="!products.length" class="text-slate-500">No product views in range</li>
                    </ul>
                </div>
            </div>
        </template>

        <p v-if="loading" class="fixed bottom-4 right-4 text-xs text-slate-500">Updating…</p>
    </section>
</template>

<script>
import { mapGetters } from 'vuex';
import KpiCard from './KpiCard.vue';

export default {
    name: 'IntelligenceDashboardComponent',
    components: { KpiCard },
    data() {
        const to = new Date().toISOString().slice(0, 10);
        const from = new Date(Date.now() - 6 * 86400000).toISOString().slice(0, 10);
        return { from, to, siteId: null, pollTimer: null, bootstrapping: true, loadError: null };
    },
    computed: {
        ...mapGetters({
            sites: 'intelligence/sites',
            overview: 'intelligence/overview',
            funnel: 'intelligence/funnel',
            sources: 'intelligence/sources',
            products: 'intelligence/products',
            realtime: 'intelligence/realtime',
            loading: 'intelligence/loading',
        }),
    },
    async mounted() {
        await this.initSites();
        this.pollTimer = setInterval(() => {
            if (this.siteId) this.$store.dispatch('intelligence/fetchRealtime');
        }, 5000);
    },
    beforeUnmount() {
        clearInterval(this.pollTimer);
    },
    methods: {
        async initSites() {
            this.bootstrapping = true;
            this.loadError = null;
            try {
                await this.$store.dispatch('intelligence/fetchSites');
                if (this.sites.length) {
                    this.siteId = this.sites[0].id;
                    this.$store.commit('intelligence/setActiveSiteId', this.siteId);
                    this.$store.commit('intelligence/setFilters', { from: this.from, to: this.to });
                    await this.refresh();
                }
            } catch (err) {
                this.loadError = err.response?.data?.message
                    || err.message
                    || 'Could not load analytics sites.';
            } finally {
                this.bootstrapping = false;
            }
        },
        onSiteChange() {
            this.$store.commit('intelligence/setActiveSiteId', this.siteId);
            this.refresh();
        },
        async refresh() {
            this.$store.commit('intelligence/setFilters', { from: this.from, to: this.to });
            await this.$store.dispatch('intelligence/refreshAll');
        },
        formatMoney(v) {
            const n = Number(v || 0);
            return n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        },
    },
};
</script>
