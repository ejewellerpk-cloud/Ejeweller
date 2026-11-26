<template>
    <LoadingComponent :props="loading" />

    <div class="col-12">
        <div class="db-card">
            <div class="db-card-header border-none flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="db-card-title">{{ $t('menu.campaigns') || 'Campaigns' }}</h3>
                    <p class="text-sm text-gray-500 mt-1">Meta / Google ads tracking, ROAS, recovery & catalog</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <input type="date" v-model="from" class="db-field-control !w-auto" @change="refreshActive" />
                    <input type="date" v-model="to" class="db-field-control !w-auto" @change="refreshActive" />
                </div>
            </div>

            <div class="px-4 pb-3">
                <div class="inline-flex flex-wrap rounded-lg border border-gray-200 bg-gray-50 p-0.5 gap-0.5">
                    <button v-for="tab in tabs" :key="tab.id" type="button"
                        class="px-3 py-1.5 text-xs font-semibold rounded-md transition"
                        :class="activeTab === tab.id ? 'bg-white text-heading shadow-sm' : 'text-gray-500'"
                        @click="switchTab(tab.id)">
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <!-- Campaigns -->
            <div v-show="activeTab === 'campaigns'" class="px-4 pb-6">
                <div class="flex justify-end mb-3">
                    <button type="button" class="db-btn py-2 text-white bg-primary" @click="openCreate">
                        <i class="lab lab-line-plus lab-font-size-16"></i>
                        <span>New campaign</span>
                    </button>
                </div>

                <div class="db-table-responsive">
                    <table class="db-table stripe">
                        <thead class="db-table-head">
                            <tr class="db-table-head-tr">
                                <th class="db-table-head-th">Name</th>
                                <th class="db-table-head-th">Channel</th>
                                <th class="db-table-head-th">Status</th>
                                <th class="db-table-head-th">UTM</th>
                                <th class="db-table-head-th">Tracking URL</th>
                                <th class="db-table-head-th">Spend</th>
                                <th class="db-table-head-th">Action</th>
                            </tr>
                        </thead>
                        <tbody class="db-table-body" v-if="campaigns.length">
                            <tr class="db-table-body-tr" v-for="c in campaigns" :key="c.id">
                                <td class="db-table-body-td font-semibold">{{ c.name }}</td>
                                <td class="db-table-body-td capitalize">{{ c.channel }}</td>
                                <td class="db-table-body-td">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold"
                                        :class="statusClass(c.status)">{{ c.status }}</span>
                                </td>
                                <td class="db-table-body-td text-xs break-all">{{ c.utm_campaign }}</td>
                                <td class="db-table-body-td">
                                    <button type="button" class="text-primary text-xs font-semibold" @click="copyUrl(c.tracking_url)">
                                        Copy link
                                    </button>
                                </td>
                                <td class="db-table-body-td">{{ formatMoney(c.total_spend) }}</td>
                                <td class="db-table-body-td">
                                    <div class="flex gap-1.5">
                                        <button type="button" class="db-btn py-1 px-2 text-xs bg-gray-100" @click="openSpend(c)">Spend</button>
                                        <button type="button" class="db-btn py-1 px-2 text-xs bg-gray-100" @click="openEdit(c)">Edit</button>
                                        <button type="button" class="db-btn py-1 px-2 text-xs bg-red-50 text-red-600" @click="destroy(c)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tbody v-else class="db-table-body">
                            <tr class="db-table-body-tr">
                                <td colspan="7" class="db-table-body-td text-center text-gray-500 py-8">
                                    No campaigns yet. Create one and paste the tracking URL into Meta Ads.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Performance -->
            <div v-show="activeTab === 'performance'" class="px-4 pb-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                    <div class="rounded-xl border p-3" v-for="kpi in performanceKpis" :key="kpi.label">
                        <div class="text-[11px] uppercase tracking-wide text-gray-500 font-bold">{{ kpi.label }}</div>
                        <div class="text-xl font-extrabold mt-1 tabular-nums">{{ kpi.value }}</div>
                    </div>
                </div>
                <div class="db-table-responsive">
                    <table class="db-table stripe">
                        <thead class="db-table-head">
                            <tr class="db-table-head-tr">
                                <th class="db-table-head-th">Campaign</th>
                                <th class="db-table-head-th">Spend</th>
                                <th class="db-table-head-th">Sessions</th>
                                <th class="db-table-head-th">Orders</th>
                                <th class="db-table-head-th">Revenue</th>
                                <th class="db-table-head-th">ROAS</th>
                                <th class="db-table-head-th">CPA</th>
                            </tr>
                        </thead>
                        <tbody class="db-table-body" v-if="performance.campaigns?.length">
                            <tr class="db-table-body-tr" v-for="row in performance.campaigns" :key="row.id">
                                <td class="db-table-body-td font-semibold">{{ row.name }}</td>
                                <td class="db-table-body-td">{{ formatMoney(row.spend) }}</td>
                                <td class="db-table-body-td">{{ row.sessions }}</td>
                                <td class="db-table-body-td">{{ row.orders }}</td>
                                <td class="db-table-body-td">{{ formatMoney(row.revenue) }}</td>
                                <td class="db-table-body-td font-semibold" :class="row.roas >= 1 ? 'text-emerald-600' : ''">
                                    {{ row.roas != null ? row.roas + 'x' : '—' }}
                                </td>
                                <td class="db-table-body-td">{{ row.cpa != null ? formatMoney(row.cpa) : '—' }}</td>
                            </tr>
                        </tbody>
                        <tbody v-else class="db-table-body">
                            <tr class="db-table-body-tr">
                                <td colspan="7" class="db-table-body-td text-center text-gray-500 py-8">
                                    Add spend and run ads with campaign UTM links to see ROAS.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h4 class="font-semibold mt-6 mb-2">Channel attribution</h4>
                <div class="db-table-responsive">
                    <table class="db-table stripe">
                        <thead class="db-table-head">
                            <tr class="db-table-head-tr">
                                <th class="db-table-head-th">Source</th>
                                <th class="db-table-head-th">Orders</th>
                                <th class="db-table-head-th">Revenue</th>
                                <th class="db-table-head-th">Spend</th>
                                <th class="db-table-head-th">ROAS</th>
                            </tr>
                        </thead>
                        <tbody class="db-table-body">
                            <tr class="db-table-body-tr" v-for="(ch, i) in attribution.channels || []" :key="i">
                                <td class="db-table-body-td capitalize">{{ ch.source }}</td>
                                <td class="db-table-body-td">{{ ch.orders }}</td>
                                <td class="db-table-body-td">{{ formatMoney(ch.revenue) }}</td>
                                <td class="db-table-body-td">{{ formatMoney(ch.spend) }}</td>
                                <td class="db-table-body-td">{{ ch.roas != null ? ch.roas + 'x' : '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recovery -->
            <div v-show="activeTab === 'recovery'" class="px-4 pb-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                    <div class="rounded-xl border p-3">
                        <div class="text-[11px] uppercase text-gray-500 font-bold">Cart abandon rate</div>
                        <div class="text-xl font-extrabold mt-1">{{ recovery.kpis?.cart_abandonment_rate ?? 0 }}%</div>
                    </div>
                    <div class="rounded-xl border p-3">
                        <div class="text-[11px] uppercase text-gray-500 font-bold">Queue pending</div>
                        <div class="text-xl font-extrabold mt-1">{{ recovery.queue?.pending ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border p-3">
                        <div class="text-[11px] uppercase text-gray-500 font-bold">Sent</div>
                        <div class="text-xl font-extrabold mt-1">{{ recovery.queue?.sent ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border p-3">
                        <div class="text-[11px] uppercase text-gray-500 font-bold">Recoverable est.</div>
                        <div class="text-xl font-extrabold mt-1">{{ formatMoney(recovery.kpis?.recoverable_value_estimate) }}</div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 mb-4">
                    <button type="button" class="db-btn py-2 text-white bg-primary" @click="enqueueRecovery">
                        Enqueue abandons
                    </button>
                    <button type="button" class="db-btn py-2 text-white bg-gray-700" @click="processRecovery">
                        Send due emails now
                    </button>
                </div>
                <div class="db-table-responsive">
                    <table class="db-table stripe">
                        <thead class="db-table-head">
                            <tr class="db-table-head-tr">
                                <th class="db-table-head-th">Event</th>
                                <th class="db-table-head-th">When</th>
                                <th class="db-table-head-th">Value</th>
                                <th class="db-table-head-th">Email</th>
                            </tr>
                        </thead>
                        <tbody class="db-table-body">
                            <tr class="db-table-body-tr" v-for="(a, i) in recovery.recent_abandons || []" :key="i">
                                <td class="db-table-body-td">{{ a.event }}</td>
                                <td class="db-table-body-td text-xs">{{ a.occurred_at }}</td>
                                <td class="db-table-body-td">{{ formatMoney(a.value) }}</td>
                                <td class="db-table-body-td">{{ a.email || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Audiences -->
            <div v-show="activeTab === 'audiences'" class="px-4 pb-6">
                <div class="flex flex-wrap items-end gap-3 mb-4">
                    <div>
                        <label class="db-field-title after:hidden">Segment</label>
                        <select v-model="audienceSegment" class="db-field-control" @change="loadAudiences">
                            <option value="all">All customers</option>
                            <option value="vip">VIP</option>
                            <option value="loyal">Loyal</option>
                            <option value="at_risk">At risk</option>
                            <option value="hibernating">Hibernating</option>
                            <option value="new">New</option>
                        </select>
                    </div>
                    <button type="button" class="db-btn py-2 text-white bg-primary" @click="exportAudience">
                        Download CSV for Meta
                    </button>
                    <button type="button" class="db-btn py-2 text-white bg-gray-700" @click="syncAudience">
                        Sync to Meta API
                    </button>
                </div>
                <p class="text-sm text-gray-500 mb-3">
                    {{ audience.count || 0 }} contacts ready.
                    {{ audience.note || 'Upload CSV in Meta → Audiences → Customer list.' }}
                </p>
            </div>

            <!-- Catalog -->
            <div v-show="activeTab === 'catalog'" class="px-4 pb-6">
                <div class="rounded-xl border p-4 mb-4">
                    <h4 class="font-semibold mb-1">Meta product catalog feed</h4>
                    <p class="text-sm text-gray-500 mb-3">
                        {{ catalog.count || 0 }} products · Paste this URL into Meta Commerce Manager → Catalog → Data Sources
                    </p>
                    <div class="flex flex-wrap gap-2 items-center">
                        <code class="text-xs bg-gray-50 border rounded px-3 py-2 break-all">{{ catalog.csv_url || feedUrl }}</code>
                        <button type="button" class="db-btn py-2 text-white bg-primary" @click="copyUrl(catalog.csv_url || feedUrl)">
                            Copy feed URL
                        </button>
                        <a :href="catalog.csv_url || feedUrl" target="_blank" class="db-btn py-2 bg-gray-100">Open CSV</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create / Edit drawer -->
    <div v-if="drawerOpen" class="fixed inset-0 z-50 bg-black/50" @click.self="closeDrawer">
        <div class="w-full max-w-xl h-screen overflow-y-auto bg-white ms-auto p-5">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">{{ form.id ? 'Edit campaign' : 'New campaign' }}</h3>
                <button type="button" class="fa-solid fa-xmark" @click="closeDrawer"></button>
            </div>
            <form @submit.prevent="saveCampaign" class="space-y-3">
                <div>
                    <label class="db-field-title required">Name</label>
                    <input v-model="form.name" class="db-field-control" required />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="db-field-title">Channel</label>
                        <select v-model="form.channel" class="db-field-control">
                            <option value="meta">Meta (Facebook/IG)</option>
                            <option value="google">Google Ads</option>
                            <option value="tiktok">TikTok</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="db-field-title">Objective</label>
                        <select v-model="form.objective" class="db-field-control">
                            <option value="conversions">Conversions</option>
                            <option value="traffic">Traffic</option>
                            <option value="awareness">Awareness</option>
                            <option value="catalog">Catalog sales</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="db-field-title">Status</label>
                        <select v-model="form.status" class="db-field-control">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="paused">Paused</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div>
                        <label class="db-field-title">Landing path</label>
                        <input v-model="form.landing_path" class="db-field-control" placeholder="/" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="db-field-title">utm_source</label>
                        <input v-model="form.utm_source" class="db-field-control" />
                    </div>
                    <div>
                        <label class="db-field-title">utm_medium</label>
                        <input v-model="form.utm_medium" class="db-field-control" />
                    </div>
                </div>
                <div>
                    <label class="db-field-title">utm_campaign (auto if empty)</label>
                    <input v-model="form.utm_campaign" class="db-field-control" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="db-field-title">utm_content</label>
                        <input v-model="form.utm_content" class="db-field-control" />
                    </div>
                    <div>
                        <label class="db-field-title">utm_term</label>
                        <input v-model="form.utm_term" class="db-field-control" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="db-field-title">Daily budget</label>
                        <input v-model="form.daily_budget" type="number" min="0" step="0.01" class="db-field-control" />
                    </div>
                    <div>
                        <label class="db-field-title">Total budget</label>
                        <input v-model="form.total_budget" type="number" min="0" step="0.01" class="db-field-control" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="db-field-title">Starts</label>
                        <input v-model="form.starts_at" type="date" class="db-field-control" />
                    </div>
                    <div>
                        <label class="db-field-title">Ends</label>
                        <input v-model="form.ends_at" type="date" class="db-field-control" />
                    </div>
                </div>
                <div>
                    <label class="db-field-title">Notes</label>
                    <textarea v-model="form.notes" class="db-field-control" rows="3"></textarea>
                </div>
                <button type="submit" class="db-btn py-2 text-white bg-primary w-full">Save campaign</button>
            </form>
        </div>
    </div>

    <!-- Spend modal -->
    <div v-if="spendOpen" class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" @click.self="spendOpen = false">
        <div class="bg-white rounded-xl w-full max-w-md p-5">
            <h3 class="font-bold mb-3">Add spend · {{ spendCampaign?.name }}</h3>
            <form @submit.prevent="saveSpend" class="space-y-3">
                <div>
                    <label class="db-field-title">Date</label>
                    <input v-model="spendForm.spend_date" type="date" class="db-field-control" required />
                </div>
                <div>
                    <label class="db-field-title">Spend amount</label>
                    <input v-model="spendForm.spend" type="number" min="0" step="0.01" class="db-field-control" required />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="db-field-title">Impressions</label>
                        <input v-model="spendForm.impressions" type="number" min="0" class="db-field-control" />
                    </div>
                    <div>
                        <label class="db-field-title">Clicks</label>
                        <input v-model="spendForm.clicks" type="number" min="0" class="db-field-control" />
                    </div>
                </div>
                <button type="submit" class="db-btn py-2 text-white bg-primary w-full">Save spend</button>
            </form>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import LoadingComponent from '../components/LoadingComponent.vue';
import alertService from '../../../services/alertService';

const emptyForm = () => ({
    id: null,
    name: '',
    channel: 'meta',
    objective: 'conversions',
    status: 'draft',
    utm_source: 'facebook',
    utm_medium: 'cpc',
    utm_campaign: '',
    utm_content: '',
    utm_term: '',
    landing_path: '/',
    daily_budget: null,
    total_budget: null,
    starts_at: '',
    ends_at: '',
    notes: '',
});

export default {
    name: 'CampaignHubComponent',
    components: { LoadingComponent },
    data() {
        const to = new Date();
        const from = new Date();
        from.setDate(from.getDate() - 29);
        return {
            loading: { isActive: false },
            activeTab: 'campaigns',
            tabs: [
                { id: 'campaigns', label: 'Campaigns' },
                { id: 'performance', label: 'Performance / ROAS' },
                { id: 'recovery', label: 'Cart recovery' },
                { id: 'audiences', label: 'Audiences' },
                { id: 'catalog', label: 'Catalog feed' },
            ],
            from: from.toISOString().slice(0, 10),
            to: to.toISOString().slice(0, 10),
            campaigns: [],
            performance: { campaigns: [], totals: {} },
            attribution: { channels: [], totals: {} },
            recovery: { kpis: {}, queue: {}, recent_abandons: [] },
            audience: {},
            audienceSegment: 'all',
            catalog: {},
            feedUrl: `${window.location.origin}/feeds/meta-catalog.csv`,
            drawerOpen: false,
            form: emptyForm(),
            spendOpen: false,
            spendCampaign: null,
            spendForm: { spend_date: to.toISOString().slice(0, 10), spend: '', impressions: '', clicks: '' },
        };
    },
    computed: {
        performanceKpis() {
            const t = this.performance.totals || {};
            return [
                { label: 'Spend', value: this.formatMoney(t.spend) },
                { label: 'Revenue', value: this.formatMoney(t.revenue) },
                { label: 'Orders', value: t.orders ?? 0 },
                { label: 'ROAS', value: t.roas != null ? `${t.roas}x` : '—' },
            ];
        },
    },
    mounted() {
        this.loadCampaigns();
    },
    methods: {
        switchTab(id) {
            this.activeTab = id;
            this.refreshActive();
        },
        refreshActive() {
            if (this.activeTab === 'campaigns') this.loadCampaigns();
            if (this.activeTab === 'performance') this.loadPerformance();
            if (this.activeTab === 'recovery') this.loadRecovery();
            if (this.activeTab === 'audiences') this.loadAudiences();
            if (this.activeTab === 'catalog') this.loadCatalog();
        },
        formatMoney(v) {
            const n = Number(v || 0);
            return n.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        },
        statusClass(status) {
            return {
                draft: 'bg-gray-100 text-gray-700',
                active: 'bg-emerald-50 text-emerald-700',
                paused: 'bg-amber-50 text-amber-700',
                completed: 'bg-slate-100 text-slate-700',
            }[status] || 'bg-gray-100';
        },
        async loadCampaigns() {
            this.loading.isActive = true;
            try {
                const res = await axios.get('admin/campaigns', { params: { paginate: 0, order_column: 'id', order_type: 'desc' } });
                this.campaigns = res.data?.data || res.data || [];
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            } finally {
                this.loading.isActive = false;
            }
        },
        async loadPerformance() {
            this.loading.isActive = true;
            try {
                const [perf, attr] = await Promise.all([
                    axios.get('admin/campaigns/performance', { params: { from: this.from, to: this.to } }),
                    axios.get('admin/campaigns/attribution', { params: { from: this.from, to: this.to } }),
                ]);
                this.performance = perf.data?.data || {};
                this.attribution = attr.data?.data || {};
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            } finally {
                this.loading.isActive = false;
            }
        },
        async loadRecovery() {
            this.loading.isActive = true;
            try {
                const res = await axios.get('admin/campaigns/recovery', { params: { from: this.from, to: this.to } });
                this.recovery = res.data?.data || {};
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            } finally {
                this.loading.isActive = false;
            }
        },
        async loadAudiences() {
            this.loading.isActive = true;
            try {
                const res = await axios.get('admin/campaigns/audiences', { params: { segment: this.audienceSegment } });
                this.audience = res.data?.data || {};
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            } finally {
                this.loading.isActive = false;
            }
        },
        async loadCatalog() {
            this.loading.isActive = true;
            try {
                const res = await axios.get('admin/campaigns/catalog');
                this.catalog = res.data?.data || {};
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            } finally {
                this.loading.isActive = false;
            }
        },
        openCreate() {
            this.form = emptyForm();
            this.drawerOpen = true;
        },
        openEdit(c) {
            this.form = {
                ...emptyForm(),
                ...c,
                starts_at: c.starts_at || '',
                ends_at: c.ends_at || '',
            };
            this.drawerOpen = true;
        },
        closeDrawer() {
            this.drawerOpen = false;
        },
        async saveCampaign() {
            this.loading.isActive = true;
            try {
                if (this.form.id) {
                    await axios.post(`admin/campaigns/${this.form.id}`, this.form);
                } else {
                    await axios.post('admin/campaigns', this.form);
                }
                alertService.successFlip(1, 'Campaign');
                this.closeDrawer();
                this.loadCampaigns();
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            } finally {
                this.loading.isActive = false;
            }
        },
        async destroy(c) {
            if (!confirm(`Delete campaign "${c.name}"?`)) return;
            try {
                await axios.delete(`admin/campaigns/${c.id}`);
                this.loadCampaigns();
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            }
        },
        openSpend(c) {
            this.spendCampaign = c;
            this.spendForm = {
                spend_date: new Date().toISOString().slice(0, 10),
                spend: '',
                impressions: '',
                clicks: '',
            };
            this.spendOpen = true;
        },
        async saveSpend() {
            try {
                await axios.post(`admin/campaigns/${this.spendCampaign.id}/spends`, this.spendForm);
                this.spendOpen = false;
                alertService.successFlip(1, 'Spend');
                this.loadCampaigns();
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            }
        },
        async enqueueRecovery() {
            try {
                const res = await axios.post('admin/campaigns/recovery/enqueue');
                alertService.success(res.data?.data?.message || 'Enqueued');
                this.loadRecovery();
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            }
        },
        async processRecovery() {
            try {
                const res = await axios.post('admin/campaigns/recovery/process');
                const d = res.data?.data || {};
                alertService.success(`Sent ${d.sent || 0}, failed ${d.failed || 0}`);
                this.loadRecovery();
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            }
        },
        async exportAudience() {
            try {
                const res = await axios.get('admin/campaigns/audiences/export', {
                    params: { segment: this.audienceSegment },
                    responseType: 'blob',
                });
                const blob = new Blob([res.data], { type: 'text/csv' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `meta-audience-${this.audienceSegment}.csv`;
                link.click();
                URL.revokeObjectURL(link.href);
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            }
        },
        async syncAudience() {
            try {
                const res = await axios.post('admin/campaigns/audiences/sync', { segment: this.audienceSegment });
                const d = res.data?.data || {};
                if (d.synced) alertService.success(d.message);
                else alertService.error(d.message || 'Sync failed');
            } catch (e) {
                alertService.error(e.response?.data?.message || e.message);
            }
        },
        async copyUrl(url) {
            try {
                await navigator.clipboard.writeText(url);
                alertService.success('Copied');
            } catch {
                prompt('Copy URL', url);
            }
        },
    },
};
</script>
