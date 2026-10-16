<template>
    <LoadingComponent :props="loading" />

    <div class="col-12">
        <div class="db-card overflow-hidden">
            <div class="db-card-header border-none">
                <h3 class="db-card-title">{{ $t('menu.postex_shipments') || 'PostEx Shipments' }}</h3>
                <div class="db-card-filter flex flex-wrap items-center gap-2">
                    <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5" @click.stop>
                        <button type="button"
                            class="px-3 py-1.5 text-xs font-semibold rounded-md transition"
                            :class="source === 'local' ? 'bg-white text-heading shadow-sm' : 'text-gray-500'"
                            @click.stop="switchSource('local')">
                            Booked here
                        </button>
                        <button type="button"
                            class="px-3 py-1.5 text-xs font-semibold rounded-md transition"
                            :class="source === 'live' ? 'bg-white text-heading shadow-sm' : 'text-gray-500'"
                            @click.stop="switchSource('live')">
                            PostEx live
                        </button>
                    </div>
                    <button type="button" class="db-btn py-2 text-white bg-primary" :disabled="loading.isActive"
                        @click.stop="refreshAll">
                        <i class="lab lab-line-refresh lab-font-size-16"></i>
                        <span>{{ $t('button.refresh') }}</span>
                    </button>
                </div>
            </div>

            <div class="px-4 pb-4" @click.stop>
                <div class="row g-3">
                    <div class="col-12 sm:col-6 md:col-4 xl:col-3">
                        <label class="db-field-title after:hidden">{{ $t('label.search') || 'Search' }}</label>
                        <input v-model="filters.search" type="text" class="db-field-control"
                            placeholder="Order # / tracking / phone"
                            @keyup.enter="applyFilters"
                            @click.stop />
                    </div>
                    <div class="col-12 sm:col-6 md:col-4 xl:col-3" @click.stop>
                        <label class="db-field-title after:hidden">{{ $t('label.date') }}</label>
                        <Datepicker
                            hideInputIcon
                            autoApply
                            :enableTimePicker="false"
                            utc="false"
                            :range="true"
                            v-model="modelValue"
                            @update:modelValue="handleDate"
                            @click.stop
                        />
                    </div>
                    <div class="col-12 sm:col-6 md:col-4 xl:col-3 flex items-end gap-2">
                        <button type="button" class="db-btn py-2 text-white bg-primary" @click.stop="applyFilters">
                            <i class="lab lab-line-search lab-font-size-16"></i>
                            <span>{{ $t('button.search') }}</span>
                        </button>
                        <button type="button" class="db-btn py-2 text-white bg-gray-600" @click.stop="clearFilters">
                            <i class="lab lab-line-cross lab-font-size-22"></i>
                            <span>{{ $t('button.clear') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Status filter cards -->
            <div class="px-4 pb-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6 gap-2.5">
                    <button type="button"
                        class="text-left rounded-xl border px-3 py-2.5 transition focus:outline-none"
                        :class="cardClass(null, activeStatus === null && !activeLiveStatusId)"
                        @click.stop="selectStatus(null)">
                        <span class="text-[11px] font-bold uppercase tracking-wide text-slate-500">All</span>
                        <p class="mt-1 text-xl font-extrabold text-heading tabular-nums">{{ summary.total || 0 }}</p>
                    </button>

                    <button type="button" v-for="card in statusCards" :key="card.key || card.id"
                        class="text-left rounded-xl border px-3 py-2.5 transition focus:outline-none"
                        :class="cardClass(card.tone, isCardActive(card))"
                        @click.stop="selectStatus(card)">
                        <span class="text-[11px] font-bold uppercase tracking-wide truncate block"
                            :class="toneText(card.tone)">{{ card.label }}</span>
                        <p class="mt-1 text-xl font-extrabold text-heading tabular-nums">
                            {{ source === 'local' ? card.total : '·' }}
                        </p>
                    </button>
                </div>
            </div>

            <!-- Desktop table -->
            <div class="db-table-responsive hidden md:block px-0">
                <table class="db-table stripe">
                    <thead class="db-table-head">
                        <tr class="db-table-head-tr">
                            <th class="db-table-head-th">{{ $t('label.order_id') }}</th>
                            <th class="db-table-head-th">{{ $t('label.tracking_no') || 'Tracking' }}</th>
                            <th class="db-table-head-th">{{ $t('label.customer') }}</th>
                            <th class="db-table-head-th">{{ $t('label.phone') }}</th>
                            <th class="db-table-head-th">{{ $t('label.city') || 'City' }}</th>
                            <th class="db-table-head-th">{{ $t('label.amount') }}</th>
                            <th class="db-table-head-th">{{ $t('label.status') }}</th>
                            <th class="db-table-head-th">{{ $t('label.date') }}</th>
                            <th class="db-table-head-th">{{ $t('label.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="db-table-body" v-if="orders.length > 0">
                        <tr class="db-table-body-tr" v-for="order in orders" :key="orderKey(order)">
                            <td class="db-table-body-td font-semibold">
                                {{ order.order_serial_no || order.order_ref || '—' }}
                            </td>
                            <td class="db-table-body-td break-all">
                                {{ order.postex_tracking_number || order.tracking_number || '—' }}
                            </td>
                            <td class="db-table-body-td">
                                {{ order.customer?.name || order.customer_name || '—' }}
                            </td>
                            <td class="db-table-body-td" dir="ltr">
                                {{ order.customer?.phone || order.customer_phone || '—' }}
                            </td>
                            <td class="db-table-body-td">
                                {{ order.customer?.city || order.customer_city || '—' }}
                            </td>
                            <td class="db-table-body-td">
                                {{ order.total_currency_price || formatMoney(order.cod_amount) }}
                            </td>
                            <td class="db-table-body-td">
                                <span class="db-table-badge capitalize"
                                    :class="badgeClass(order.postex_status_tone || statusTone(order.status || order.postex_status))">
                                    {{ order.postex_status || order.status || 'Unknown' }}
                                </span>
                            </td>
                            <td class="db-table-body-td">
                                {{ order.postex_booked_at || order.booking_date || order.order_datetime || '—' }}
                            </td>
                            <td class="db-table-body-td">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <router-link
                                        v-if="order.id || order.local_order_id"
                                        :to="{ name: 'admin.order.show', params: { id: order.id || order.local_order_id } }"
                                        class="db-btn py-1 px-2 text-xs bg-gray-100 text-heading"
                                        @click.stop>
                                        {{ $t('button.view') }}
                                    </router-link>
                                    <a v-if="order.postex_tracking_number || order.tracking_number"
                                        :href="'https://postex.pk/?tracking=' + encodeURIComponent(order.postex_tracking_number || order.tracking_number)"
                                        target="_blank" rel="noopener"
                                        class="db-btn py-1 px-2 text-xs bg-sky-50 text-sky-700"
                                        @click.stop>
                                        Track
                                    </a>
                                    <button v-if="source === 'local' && order.id" type="button"
                                        class="db-btn py-1 px-2 text-xs bg-indigo-50 text-indigo-700 disabled:opacity-60"
                                        :disabled="trackingId === order.id"
                                        @click.stop="syncTrack(order)">
                                        Sync
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody class="db-table-body" v-else>
                        <tr class="db-table-body-tr">
                            <td class="db-table-body-td text-center" colspan="9">
                                <span class="d-block py-6 text-base">{{ $t('message.no_data_found') }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile cards -->
            <div class="md:hidden px-4 pb-4 space-y-3">
                <div v-if="!loading.isActive && orders.length === 0"
                    class="rounded-xl border border-dashed border-gray-200 px-4 py-10 text-center text-sm text-gray-500">
                    {{ $t('message.no_data_found') }}
                </div>

                <article v-for="order in orders" :key="'m-' + orderKey(order)"
                    class="rounded-xl border border-gray-100 bg-white p-3.5 shadow-sm">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-heading truncate">
                                {{ order.order_serial_no || order.order_ref || 'Shipment' }}
                            </p>
                            <p class="text-xs text-gray-500 truncate">
                                {{ order.customer?.name || order.customer_name || '—' }}
                            </p>
                        </div>
                        <span class="shrink-0 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold capitalize"
                            :class="badgeClass(order.postex_status_tone || statusTone(order.status || order.postex_status))">
                            {{ order.postex_status || order.status || 'Unknown' }}
                        </span>
                    </div>

                    <div class="space-y-1 text-xs text-heading">
                        <p v-if="order.postex_tracking_number || order.tracking_number">
                            <span class="text-gray-400">Tracking:</span>
                            {{ order.postex_tracking_number || order.tracking_number }}
                        </p>
                        <p v-if="order.customer?.phone || order.customer_phone" dir="ltr">
                            <span class="text-gray-400">Phone:</span>
                            {{ order.customer?.phone || order.customer_phone }}
                        </p>
                        <p v-if="order.customer?.city || order.customer_city">
                            <span class="text-gray-400">City:</span>
                            {{ order.customer?.city || order.customer_city }}
                        </p>
                        <p>
                            <span class="text-gray-400">Amount:</span>
                            {{ order.total_currency_price || formatMoney(order.cod_amount) }}
                        </p>
                        <p v-if="order.postex_booked_at || order.booking_date">
                            <span class="text-gray-400">Booked:</span>
                            {{ order.postex_booked_at || order.booking_date }}
                        </p>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <router-link
                            v-if="order.id || order.local_order_id"
                            :to="{ name: 'admin.order.show', params: { id: order.id || order.local_order_id } }"
                            class="db-btn py-1.5 px-3 text-xs bg-gray-100 text-heading"
                            @click.stop>
                            {{ $t('button.view') }}
                        </router-link>
                        <a v-if="order.postex_tracking_number || order.tracking_number"
                            :href="'https://postex.pk/?tracking=' + encodeURIComponent(order.postex_tracking_number || order.tracking_number)"
                            target="_blank" rel="noopener"
                            class="db-btn py-1.5 px-3 text-xs bg-sky-50 text-sky-700"
                            @click.stop>
                            Track
                        </a>
                        <button v-if="source === 'local' && order.id" type="button"
                            class="db-btn py-1.5 px-3 text-xs bg-indigo-50 text-indigo-700 disabled:opacity-60"
                            :disabled="trackingId === order.id"
                            @click.stop="syncTrack(order)">
                            Sync
                        </button>
                    </div>
                </article>
            </div>

            <div v-if="source === 'local' && meta.last_page > 1"
                class="px-4 pb-4 flex items-center justify-between gap-3">
                <p class="text-xs text-gray-500">
                    {{ meta.current_page }} / {{ meta.last_page }} · {{ meta.total }}
                </p>
                <div class="flex gap-2">
                    <button type="button" class="db-btn py-1.5 px-3 text-sm bg-gray-100 text-heading disabled:opacity-50"
                        :disabled="meta.current_page <= 1" @click.stop="goPage(meta.current_page - 1)">
                        Prev
                    </button>
                    <button type="button" class="db-btn py-1.5 px-3 text-sm bg-gray-100 text-heading disabled:opacity-50"
                        :disabled="meta.current_page >= meta.last_page" @click.stop="goPage(meta.current_page + 1)">
                        Next
                    </button>
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
        idle: "border-sky-100 bg-sky-50/60",
        active: "border-sky-400 ring-2 ring-sky-200 bg-sky-50",
        text: "text-sky-700",
        badge: "bg-sky-100 text-sky-800",
    },
    indigo: {
        idle: "border-indigo-100 bg-indigo-50/60",
        active: "border-indigo-400 ring-2 ring-indigo-200 bg-indigo-50",
        text: "text-indigo-700",
        badge: "bg-indigo-100 text-indigo-800",
    },
    amber: {
        idle: "border-amber-100 bg-amber-50/60",
        active: "border-amber-400 ring-2 ring-amber-200 bg-amber-50",
        text: "text-amber-700",
        badge: "bg-amber-100 text-amber-800",
    },
    emerald: {
        idle: "border-emerald-100 bg-emerald-50/60",
        active: "border-emerald-400 ring-2 ring-emerald-200 bg-emerald-50",
        text: "text-emerald-700",
        badge: "bg-emerald-100 text-emerald-800",
    },
    orange: {
        idle: "border-orange-100 bg-orange-50/60",
        active: "border-orange-400 ring-2 ring-orange-200 bg-orange-50",
        text: "text-orange-700",
        badge: "bg-orange-100 text-orange-800",
    },
    rose: {
        idle: "border-rose-100 bg-rose-50/60",
        active: "border-rose-400 ring-2 ring-rose-200 bg-rose-50",
        text: "text-rose-700",
        badge: "bg-rose-100 text-rose-800",
    },
    slate: {
        idle: "border-slate-200 bg-slate-50/60",
        active: "border-slate-400 ring-2 ring-slate-200 bg-slate-50",
        text: "text-slate-700",
        badge: "bg-slate-100 text-slate-800",
    },
};

function toYmd(value) {
    if (!value) {
        return null;
    }
    const d = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(d.getTime())) {
        return null;
    }
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
}

function defaultRange() {
    const to = new Date();
    const from = new Date();
    from.setDate(to.getDate() - 30);
    return [from, to];
}

export default {
    name: "PostExShipmentListComponent",
    components: { LoadingComponent },
    data() {
        const range = defaultRange();
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
                from_date: toYmd(range[0]),
                to_date: toYmd(range[1]),
            },
            modelValue: range,
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
        handleDate(e) {
            if (Array.isArray(e) && e[0] && e[1]) {
                this.filters.from_date = toYmd(e[0]);
                this.filters.to_date = toYmd(e[1]);
            } else {
                this.filters.from_date = null;
                this.filters.to_date = null;
            }
        },
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
                alertService.info("Select a status card first.");
            }
        },
        clearFilters() {
            const range = defaultRange();
            this.modelValue = range;
            this.filters = {
                search: "",
                from_date: toYmd(range[0]),
                to_date: toYmd(range[1]),
            };
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
                    alertService.success(status ? "PostEx: " + status : (this.$t("message.success") || "Synced"));
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
