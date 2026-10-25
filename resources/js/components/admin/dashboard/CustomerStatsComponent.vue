<template>
    <LoadingComponent :props="loading" />
    <div class="col-12 xl:col-6">
        <div class="db-card">
            <div class="db-card-header">
                <h3 class="font-semibold text-lg capitalize text-heading">{{ $t('label.customer_stats') }}</h3>
                <div id="customer-range" class="cursor-pointer flex items-center gap-3">
                    <Datepicker hideInputIcon autoApply :enableTimePicker="false" utc="false"
                        :placeholder="$t('label.select_date_range')" @update:modelValue="customerStates"
                        v-model="modelValue" :range="true">
                    </Datepicker>
                </div>
            </div>
            <div class="db-card-body">
                <ul v-if="bars.length" class="space-y-3">
                    <li v-for="(bar, index) in bars" :key="index" class="flex items-center gap-3">
                        <span class="w-16 shrink-0 text-xs text-slate-500 truncate" :title="bar.label">{{ bar.label }}</span>
                        <span class="flex-1 h-2.5 rounded bg-slate-100 overflow-hidden">
                            <span class="block h-full rounded bg-[#567DFF]" :style="{ width: bar.width + '%' }"></span>
                        </span>
                        <span class="w-10 shrink-0 text-right text-xs font-semibold text-heading">{{ bar.value }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-slate-400">{{ $t('message.no_data_found') || 'No data found' }}</p>
            </div>
        </div>
    </div>
</template>

<script>
import LoadingComponent from "../components/LoadingComponent";
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";

export default {
    name: "CustomerStatsComponent",
    components: { LoadingComponent, Datepicker },
    data() {
        return {
            loading: {
                isActive: false,
            },
            first_date: null,
            last_date: null,
            modelValue: null,
            bars: [],
        };
    },
    mounted() {
        this.customerStates();
    },
    methods: {
        customerStates: function (e) {
            let date = {
                first_date: '',
                last_date: '',
            };
            if (e) {
                this.first_date = e[0];
                this.last_date = e[1];
                date.first_date = e[0];
                date.last_date = e[1];
            }

            this.loading.isActive = true;

            this.$store.dispatch("dashboard/customerStates", date).then((res) => {
                const values = Array.isArray(res.data.data.total_customers)
                    ? res.data.data.total_customers.map((v) => Number(v) || 0)
                    : [];
                const labels = Array.isArray(res.data.data.times) ? res.data.data.times : [];
                const max = Math.max(...values, 1);
                this.bars = values.map((value, index) => ({
                    value,
                    label: labels[index] ?? String(index + 1),
                    width: Math.max(2, Math.round((value / max) * 100)),
                }));
                this.loading.isActive = false;
            }).catch(() => {
                this.loading.isActive = false;
            });
        },
    },
};
</script>
