<template>
    <LoadingComponent :props="loading" />

    <div class="col-12">
        <div class="db-card overflow-hidden">
            <div class="db-card-header border-none !items-start sm:!items-center gap-3">
                <div class="min-w-0">
                    <h3 class="db-card-title">{{ $t('menu.postex_shipments') || 'PostEx Shipments' }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Order-wise bookings on PostEx — tap a status card to filter.
                    </p>
                </div>
                <div class="db-card-filter !ml-auto flex flex-wrap gap-2">
                    <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5">
                        <button type="button"
                            class="px-3 py-1.5 text-xs font-semibold rounded-md transition"
                            :class="source === 'local' ? 'bg-white text-heading shadow-sm' : 'text-gray-500'"
                            @click="switchSource('local')">
                            Booked here
                        </button>
                        <button type="button"
                            class="px-3 py-1.5 text-xs font-semibold rounded-md transition"
                            :class="source === 'live' ? 'bg-white text-heading shadow-sm' : 'text-gray-500'"
                            @click="switchSource('live')">
                            PostEx live
                        </button>
                    </div>
                    <button type="button" class="db-btn py-2 text-white bg-primary" :disabled="loading.isActive" @click="refreshAll">
                        <i class="lab lab-line-refresh lab-font-size-16"></i>
                        <span>{{ $t('button.refresh') || 'Refresh' }}</span>
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="px-4 pb-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="db-field-title after:hidden">{{ $t('label.search') || 'Search' }}</label>
                        <input v-model="filters.search" type="text" class="db-field-control"
                            placeholder="Order #, tracking, name, phone, city"
                            @keyup.enter="applyFilters" />
                    </div>
                    <div>
                        <label class="db-field-title after:hidden">From</label>
                        <input v-model="filters.from_date" type="date" class="db-field-control" />
                    </div>
                    <div>
                        <label class="db-field-title after:hidden">To</label>
                        <input v-model="filters.to_date" type="date" class="db-field-control" />
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="button" class="db-btn py-2 text-white bg-primary flex-1" @click="applyFilters">
                            <i class="lab lab-line-search lab-font-size-16"></i>
                            <span>{{ $t('button.search') }}</span>
                        </button>
                        <button type="button" class="db-btn py-2 text-white bg-gray-600" @click="clearFilters">
                            <i class="lab lab-line-cross lab-font-size-22"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Status cards -->
            <div class="px-4 pb-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6 gap-3">
                    <button type="button"
                        class="text-left rounded-2xl border p-3.5 transition shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary/30"
                        :class="cardClass(null, activeStatus === null)"
                        @click="selectStatus(null)">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-[11px] font-bold uppercase tracking-wide text-slate-500">All</span>
                            <i class="fa-solid fa-layer-group text-slate-400"></i>
                        </div>
                        <p class="mt-2 text-2xl font-extrabold text-heading tabular-nums">{{ summary.total || 0 }}</p>
                        <p class="mt-0.5 text-[11px] text-gray-500">Booked shipments</p>
                    </button>

                    <button type="button" v-for="card in statusCards" :key="card.key || card.id"
                        class="text-left rounded-2xl border p-3.5 transition shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary/30"
                        :class="cardClass(card.tone, isCardActive(card))"
                        @click="selectStatus(card)">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-[11px] font-bold uppercase tracking-wide truncate"
                                :class="toneText(card.tone)">{{ card.label }}</span>
                            <i class="fa-solid" :class="toneIcon(card.tone)"></i>
                        </div>
                        <p class="mt-2 text-2xl font-extrabold text-heading tabular-nums">
                            {{ source === 'local' ? card.total : '—' }}
                        </p>
                        <p class="mt-0.5 text-[11px] text-gray-500">
                            {{ source === 'live' ? 'Tap to load from PostEx' : 'Orders' }}
                        </p>
                    </button>
                </div>
            </div>

            <!-- Orders -->
            <div class="px-4 pb-5">
                <div v-if="!loading.isActive && orders.length === 0"
                    class="rounded-2xl border border-dashed border-gray-200 bg-gray-50/80 px-6 py-14 text-center">
                    <i class="fa-solid fa-truck-fast text-3xl text-gray-300 mb-3"></i>
                    <p class="text-sm font-semibold text-heading">No PostEx shipments found</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ source === 'live'
                            ? 'Pick a PostEx status card and date range, then search.'
                            : 'Book orders from Online Orders to see them here.' }}
                    </p>
                </div>

                <div v-else class="grid grid-cols-1 xl:grid-cols-2 gap-3">
                    <article v-for="order in orders" :key="orderKey(order)"
                        class="rounded-2xl border border-gray-100 bg-white p-4 shadow-[0_1px_8px_rgba(15,23,42,0.04)] hover:border-primary/25 transition">
                        <div class="flex flex-wrap items-start justify-between gap-2 mb-3">
                            <div class="min-w-0">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                    {{ order.order_serial_no || order.order_ref || 'Shipment' }}
                                </p>
                                <h4 class="text-base font-bold text-heading truncate">
                                    {{ order.customer?.name || order.customer_name || '—' }}
                                </h4>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold capitalize"
                                :class="badgeClass(order.postex_status_tone || statusTone(order.status || order.postex_status))">
                                {{ order.postex_status || order.status || 'Unknown' }}
                            </span>
                        </div>

                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            <div v-if="order.postex_tracking_number || order.tracking_number">
                                <dt class="text-[10px] uppercase font-bold text-slate-400">Tracking</dt>
                                <dd class="font-semibold text-heading break-all">
                                    {{ order.postex_tracking_number || order.tracking_number }}
                                </dd>
                            </div>
                            <div v-if="order.total_currency_price || order.cod_amount != null">
                                <dt class="text-[10px] uppercase font-bold text-slate-400">Amount</dt>
                                <dd class="font-semibold text-heading">
                                    {{ order.total_currency_price || formatMoney(order.cod_amount) }}
                                </dd>
                            </div>
                            <div v-if="order.customer?.phone || order.customer_phone">
                                <dt class="text-[10px] uppercase font-bold text-slate-400">Phone</dt>
                                <dd class="font-medium text-heading">{{ order.customer?.phone || order.customer_phone }}</dd>
                            </div>
                            <div v-if="order.customer?.city || order.customer_city">
                                <dt class="text-[10px] uppercase font-bold text-slate-400">City</dt>
                                <dd class="font-medium text-heading">{{ order.customer?.city || order.customer_city }}</dd>
                            </div>
                            <div class="sm:col-span-2" v-if="order.customer?.address || order.customer_address">
                                <dt class="text-[10px] uppercase font-bold text-slate-400">Address</dt>
                                <dd class="font-medium text-heading leading-snug">
                                    {{ order.customer?.address || order.customer_address }}
                                </dd>
                            </div>
                            <div v-if="order.postex_booked_at || order.booking_date">
                                <dt class="text-[10px] uppercase font-bold text-slate-400">Booked</dt>
                                <dd class="font-medium text-heading">{{ order.postex_booked_at || order.booking_date }}</dd>
                            </div>
                            <div v-if="order.order_datetime">
                                <dt class="text-[10px] uppercase font-bold text-slate-400">Order date</dt>
                                <dd class="font-medium text-heading">{{ order.order_datetime }}</dd>
                            </div>
                            <div v-if="order.payment_method_name">
                                <dt class="text-[10px] uppercase font-bold text-slate-400">Payment</dt>
                                <dd class="font-medium text-heading">{{ order.payment_method_name }}</dd>
                            </div>
                            <div v-if="order.status_name">
                                <dt class="text-[10px] uppercase font-bold text-slate-400">Shop status</dt>
                                <dd class="font-medium text-heading">{{ order.status_name }}</dd>
                            </div>
                            <div v-if="order.items_count != null">
                                <dt class="text-[10px] uppercase font-bold text-slate-400">Items</dt>
                                <dd class="font-medium text-heading">{{ order.items_count }}</dd>
                            </div>
                            <div class="sm:col-span-2" v-if="order.note">
                                <dt class="text-[10px] uppercase font-bold text-slate-400">Note</dt>
                                <dd class="font-medium text-heading leading-snug">{{ order.note }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <router-link
                                v-if="order.id || order.local_order_id"
                                :to="{ name: 'admin.order.show', params: { id: order.id || order.local_order_id } }"
                                class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-heading hover:border-primary hover:text-primary transition">
                                <i class="fa-solid fa-eye"></i>
                                View order
                            </router-link>
                            <a v-if="order.postex_tracking_number || order.tracking_number"
                                :href="'https://postex.pk/?tracking=' + encodeURIComponent(order.postex_tracking_number || order.tracking_number)"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-1.5 rounded-full border border-sky-100 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-100 transition">
                                <i class="fa-solid fa-location-dot"></i>
                                Track
                            </a>
                            <button v-if="source === 'local' && order.id"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition disabled:opacity-60"
                                :disabled="trackingId === order.id"
                                @click="syncTrack(order)">
                                <i class="fa-solid" :class="trackingId === order.id ? 'fa-spinner fa-spin' : 'fa-rotate'"></i>
                                Sync status
                            </button>
                        </div>
                    </article>
                </div>

                <div v-if="source === 'local' && meta.last_page > 1" class="mt-5 flex items-center justify-between gap-3">
                    <p class="text-xs text-gray-500">
                        Page {{ meta.current_page }} of {{ meta.last_page }} · {{ meta.total }} total
                    </p>
                    <div class="flex gap-2">
                        <button type="button" class="db-btn py-1.5 px-3 text-sm bg-gray-100 text-heading disabled:opacity-50"
                            :disabled="meta.current_page <= 1" @click="goPage(meta.current_page - 1)">
                            Prev
                        </button>
                        <button type="button" class="db-btn py-1.5 px-3 text-sm bg-gray-100 text-heading disabled:opacity-50"
                            :disabled="meta.current_page >= meta.last_page" @click="goPage(meta.current_page + 1)">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import alertService from "../../../services/alertService";
import LoadingComponent from "../components/LoadingComponent.vue";

const TONE_STYLES = {
    sky: {
        idle: "border-sky-100 bg-gradient-to-br from-sky-50 to-white",
        active: "border-sky-400 ring-2 ring-sky-200 bg-sky-50",
        text: "text-sky-700",
        badge: "bg-sky-100 text-sky-800",
        icon: "fa-bookmark text-sky-500",
    },
    indigo: {
        idle: "border-indigo-100 bg-gradient-to-br from-indigo-50 to-white",
        active: "border-indigo-400 ring-2 ring-indigo-200 bg-indigo-50",
        text: "text-indigo-700",
        badge: "bg-indigo-100 text-indigo-800",
        icon: "fa-warehouse text-indigo-500",
    },
    amber: {
        idle: "border-amber-100 bg-gradient-to-br from-amber-50 to-white",
        active: "border-amber-400 ring-2 ring-amber-200 bg-amber-50",
        text: "text-amber-700",
        badge: "bg-amber-100 text-amber-800",
        icon: "fa-truck text-amber-500",
    },
    emerald: {
        idle: "border-emerald-100 bg-gradient-to-br from-emerald-50 to-white",
        active: "border-emerald-400 ring-2 ring-emerald-200 bg-emerald-50",
        text: "text-emerald-700",
        badge: "bg-emerald-100 text-emerald-800",
        icon: "fa-circle-check text-emerald-500",
    },
    orange: {
        idle: "border-orange-100 bg-gradient-to-br from-orange-50 to-white",
        active: "border-orange-400 ring-2 ring-orange-200 bg-orange-50",
        text: "text-orange-700",
        badge: "bg-orange-100 text-orange-800",
        icon: "fa-rotate-left text-orange-500",
    },
    rose: {
        idle: "border-rose-100 bg-gradient-to-br from-rose-50 to-white",
        active: "border-rose-400 ring-2 ring-rose-200 bg-rose-50",
        text: "text-rose-700",
        badge: "bg-rose-100 text-rose-800",
        icon: "fa-ban text-rose-500",
    },
    slate: {
        idle: "border-slate-200 bg-gradient-to-br from-slate-50 to-white",
        active: "border-slate-400 ring-2 ring-slate-200 bg-slate-50",
        text: "text-slate-700",
        badge: "bg-slate-100 text-slate-800",
        icon: "fa-box text-slate-400",
    },
};

function defaultDateRange() {
    const to = new Date();
    const from = new Date();
    from.setDate(to.getDate() - 30);
    const fmt = (d) => d.toISOString().slice(0, 10);
    return { from_date: fmt(from), to_date: fmt(to) };
}

export default {
    name: "PostExShipmentListComponent",
    components: { LoadingComponent },
    data() {
        const range = defaultDateRange();
        return {
            loading: { isActive: false },
            source: "local",
            summary: { total: 0, statuses: [] },
            liveStatuses: [],
            activeStatus: null,
            activeLiveStatusId: null,
            orders: [],
            meta: { current_page: 1, last_page: 1, per_page: 20, total: 0 },
            filters: {
                search: "",
                from_date: range.from_date,
                to_date: range.to_date,
            },
            trackingId: null,
        };
    },
    computed: {
        statusCards() {
            if (this.source === "live") {
                return this.liveStatuses;
            }
            return this.summary.statuses || [];
        },
    },
    mounted() {
        this.refreshAll();
    },
    methods: {
        switchSource(source) {
            if (this.source === source) {
                return;
            }
            this.source = source;
            this.activeStatus = null;
            this.activeLiveStatusId = null;
            this.orders = [];
            this.refreshAll();
        },
        refreshAll() {
            if (this.source === "local") {
                this.loadSummary().then(() => this.loadLocalOrders());
            } else {
                this.loadLiveStatuses().then(() => {
                    if (this.activeLiveStatusId) {
                        this.loadLiveOrders();
                    }
                });
            }
        },
        applyFilters() {
            if (this.source === "local") {
                this.meta.current_page = 1;
                this.loadSummary().then(() => this.loadLocalOrders());
            } else if (this.activeLiveStatusId) {
                this.loadLiveOrders();
            } else {
                alertService.info("Select a PostEx status card first.");
            }
        },
        clearFilters() {
            const range = defaultDateRange();
            this.filters = { search: "", from_date: range.from_date, to_date: range.to_date };
            this.activeStatus = null;
            this.activeLiveStatusId = null;
            this.refreshAll();
        },
        selectStatus(card) {
            if (!card) {
                this.activeStatus = null;
                this.activeLiveStatusId = null;
                if (this.source === "local") {
                    this.loadLocalOrders();
                } else {
                    this.orders = [];
                    this.summary = { ...this.summary, total: this.orders.length };
                }
                return;
            }

            if (this.source === "live") {
                this.activeLiveStatusId = card.id;
                this.activeStatus = card.label;
                this.loadLiveOrders();
                return;
            }

            this.activeStatus = card.key;
            this.loadLocalOrders();
        },
        isCardActive(card) {
            if (this.source === "live") {
                return this.activeLiveStatusId === card.id;
            }
            return this.activeStatus === card.key;
        },
        loadSummary() {
            this.loading.isActive = true;
            return axios
                .get("admin/postex-shipments/summary", {
                    params: {
                        from_date: this.filters.from_date || undefined,
                        to_date: this.filters.to_date || undefined,
                    },
                })
                .then((res) => {
                    this.summary = res.data?.data || { total: 0, statuses: [] };
                })
                .catch((err) => {
                    alertService.error(err.response?.data?.message || "Failed to load summary");
                })
                .finally(() => {
                    this.loading.isActive = false;
                });
        },
        loadLocalOrders(page = 1) {
            this.loading.isActive = true;
            return axios
                .get("admin/postex-shipments", {
                    params: {
                        page,
                        per_page: this.meta.per_page,
                        search: this.filters.search || undefined,
                        from_date: this.filters.from_date || undefined,
                        to_date: this.filters.to_date || undefined,
                        postex_status: this.activeStatus || undefined,
                    },
                })
                .then((res) => {
                    this.orders = res.data?.data || [];
                    this.meta = res.data?.meta || this.meta;
                })
                .catch((err) => {
                    alertService.error(err.response?.data?.message || "Failed to load shipments");
                })
                .finally(() => {
                    this.loading.isActive = false;
                });
        },
        loadLiveStatuses() {
            this.loading.isActive = true;
            return axios
                .get("admin/postex-shipments/live-statuses")
                .then((res) => {
                    this.liveStatuses = res.data?.data || [];
                    this.summary = { total: this.liveStatuses.length, statuses: [] };
                })
                .catch((err) => {
                    alertService.error(err.response?.data?.message || "Failed to load PostEx statuses");
                    this.liveStatuses = [];
                })
                .finally(() => {
                    this.loading.isActive = false;
                });
        },
        loadLiveOrders() {
            if (!this.activeLiveStatusId) {
                return Promise.resolve();
            }
            this.loading.isActive = true;
            return axios
                .get("admin/postex-shipments/live", {
                    params: {
                        orderStatusID: this.activeLiveStatusId,
                        fromDate: this.filters.from_date,
                        toDate: this.filters.to_date,
                    },
                })
                .then((res) => {
                    let rows = res.data?.data?.orders || [];
                    const q = (this.filters.search || "").trim().toLowerCase();
                    if (q) {
                        rows = rows.filter((row) => {
                            const hay = [
                                row.tracking_number,
                                row.order_ref,
                                row.order_serial_no,
                                row.customer_name,
                                row.customer_phone,
                                row.customer_city,
                                row.status,
                            ]
                                .join(" ")
                                .toLowerCase();
                            return hay.includes(q);
                        });
                    }
                    this.orders = rows;
                    this.summary = { ...this.summary, total: rows.length };
                })
                .catch((err) => {
                    alertService.error(err.response?.data?.message || "Failed to load PostEx orders");
                    this.orders = [];
                })
                .finally(() => {
                    this.loading.isActive = false;
                });
        },
        goPage(page) {
            this.loadLocalOrders(page);
        },
        syncTrack(order) {
            this.trackingId = order.id;
            axios
                .get(`admin/online-order/${order.id}/postex/track`)
                .then((res) => {
                    const data = res.data?.data || {};
                    const status =
                        data.transactionStatus ||
                        data.orderStatus ||
                        data.status ||
                        data.Status ||
                        null;
                    if (status) {
                        alertService.success("PostEx: " + status);
                    } else {
                        alertService.success(this.$t("message.success") || "Synced");
                    }
                    this.loadSummary().then(() => this.loadLocalOrders(this.meta.current_page));
                })
                .catch((err) => {
                    alertService.error(err.response?.data?.message || "Track failed");
                })
                .finally(() => {
                    this.trackingId = null;
                });
        },
        orderKey(order) {
            return order.id || order.tracking_number || order.postex_tracking_number || Math.random();
        },
        formatMoney(value) {
            if (value == null || value === "") {
                return "—";
            }
            return value;
        },
        tone(name) {
            return TONE_STYLES[name] || TONE_STYLES.slate;
        },
        cardClass(toneName, active) {
            const t = this.tone(toneName || "slate");
            return active ? t.active : t.idle;
        },
        toneText(toneName) {
            return this.tone(toneName).text;
        },
        toneIcon(toneName) {
            return this.tone(toneName).icon;
        },
        badgeClass(toneName) {
            return this.tone(toneName).badge;
        },
        statusTone(status) {
            const key = String(status || "").toLowerCase();
            if (key.includes("deliver") && !key.includes("out for")) return "emerald";
            if (key.includes("return")) return "orange";
            if (key.includes("cancel") || key.includes("expir") || key.includes("unassign")) return "rose";
            if (key.includes("out for delivery") || key.includes("attempt")) return "amber";
            if (key.includes("warehouse") || key.includes("picked") || key.includes("en-route") || key.includes("en route")) return "indigo";
            if (key.includes("booked") && !key.includes("unbooked")) return "sky";
            return "slate";
        },
    },
};
</script>
