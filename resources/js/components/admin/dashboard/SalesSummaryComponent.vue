<template>
    <LoadingComponent :props="loading" />
    <div class="col-12 xl:col-6">
        <div class="db-card">
            <div class="db-card-header">
                <h3 class="font-semibold text-lg capitalize text-heading">{{ $t('label.sales_summary') }}</h3>
                <div id="sales-range" class="cursor-pointer flex items-center gap-3">
                    <Datepicker hideInputIcon autoApply :enableTimePicker="false" utc="false"
                        :placeholder="$t('label.select_date_range')" @update:modelValue="salesSummary"
                        v-model="modelValue" :range="true">
                    </Datepicker>
                </div>
            </div>
            <div class="db-card-body">
                <ul class="flex gap-11 mb-6">
                    <li>
                        <div class="flex items-center gap-2.5">
                            <i class="lab lab-line-bar-chart lab-font-size-20 lab-font-color-2"></i>
                            <h3 class="font-bold text-xl text-secondary">{{ total_sales }}</h3>
                        </div>
                        <p class="text-xs capitalize">{{ $t("label.total_sales") }}</p>
                    </li>
                    <li>
                        <div class="flex items-center gap-2.5">
                            <i class="lab lab-line-bar-chart lab-font-size-20 lab-font-color-2"></i>
                            <h3 class="font-bold text-xl text-secondary">{{ avg_per_day }}</h3>
                        </div>
                        <p class="text-xs capitalize">{{ $t("label.avg_sales_per_day") }}</p>
                    </li>
                </ul>

                <ul v-if="dayBars.length" class="flex items-end gap-1 h-28">
                    <li
                        v-for="(bar, index) in dayBars"
                        :key="index"
                        class="flex-1 min-w-0 flex flex-col justify-end items-center gap-1"
                        :title="String(bar.value)"
                    >
                        <span
                            class="w-full max-w-[18px] rounded-t bg-primary/80"
                            :style="{ height: bar.height + '%' }"
                        ></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
import LoadingComponent from "../components/LoadingComponent";
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";

export default {
    name: "SalesSummaryComponent",
    components: { LoadingComponent, Datepicker },
    data() {
        return {
            loading: {
                isActive: false,
            },
            modelValue: null,
            first_date: null,
            last_date: null,
            total_sales: null,
            avg_per_day: null,
            dayBars: [],
        };
    },
    mounted() {
        const date = new Date();
        const startDate = new Date(date.getFullYear(), date.getMonth(), 1);
        const endDate = new Date(date.getFullYear(), date.getMonth() + 1, 0);
        this.modelValue = [startDate, endDate];
        this.salesSummary();
    },
    methods: {
        salesSummary: function (e) {
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
            this.$store.dispatch("dashboard/salesSummary", date).then((res) => {
                this.total_sales = res.data.data.total_sales;
                this.avg_per_day = res.data.data.avg_per_day;
                const series = Array.isArray(res.data.data.per_day_sales)
                    ? res.data.data.per_day_sales.map((v) => Number(v) || 0)
                    : [];
                const max = Math.max(...series, 1);
                this.dayBars = series.map((value) => ({
                    value,
                    height: Math.max(4, Math.round((value / max) * 100)),
                }));
                this.loading.isActive = false;
            }).catch(() => {
                this.loading.isActive = false;
            });
        },
    },
};
</script>
