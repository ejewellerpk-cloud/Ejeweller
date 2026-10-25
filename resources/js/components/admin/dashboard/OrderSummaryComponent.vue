<template>
    <LoadingComponent :props="loading" />
    <div class="col-12 xl:col-6">
        <div class="db-card">
            <div class="db-card-header">
                <h3 class="font-semibold text-lg capitalize text-heading">{{ $t('label.orders_summary') }}</h3>
                <div id="order-range" class="cursor-pointer flex items-center gap-3">
                    <Datepicker hideInputIcon autoApply :enableTimePicker="false" utc="false"
                        :placeholder="$t('label.select_date_range')" @update:modelValue="orderSummary"
                        v-model="modelValue" :range="true">
                    </Datepicker>
                </div>
            </div>
            <div class="db-card-body">
                <ul class="flex flex-col gap-8 w-full max-w-md">
                    <li class="w-full">
                        <span class="block capitalize mb-1 text-heading">
                            {{ $t("label.delivered") }} ({{ delivered }}%)
                        </span>
                        <span class="block w-full h-2 rounded bg-slate-100 overflow-hidden">
                            <span class="block h-full rounded bg-[#FD8B0E]" :style="{ width: delivered + '%' }"></span>
                        </span>
                    </li>
                    <li class="w-full">
                        <span class="block capitalize mb-1 text-heading">
                            {{ $t("label.canceled") }} ({{ canceled }}%)
                        </span>
                        <span class="block w-full h-2 rounded bg-slate-100 overflow-hidden">
                            <span class="block h-full rounded bg-[#A953FF]" :style="{ width: canceled + '%' }"></span>
                        </span>
                    </li>
                    <li class="w-full">
                        <span class="block capitalize mb-1 text-heading">
                            {{ $t("label.rejected") }} ({{ rejected }}%)
                        </span>
                        <span class="block w-full h-2 rounded bg-slate-100 overflow-hidden">
                            <span class="block h-full rounded bg-[#FB4E4E]" :style="{ width: rejected + '%' }"></span>
                        </span>
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
    name: "OrderSummaryComponent",
    components: { LoadingComponent, Datepicker },
    data() {
        return {
            loading: {
                isActive: false,
            },
            first_date: null,
            last_date: null,
            delivered: 0,
            canceled: 0,
            rejected: 0,
            modelValue: null,
        };
    },
    mounted() {
        this.orderSummary();
    },
    methods: {
        orderSummary: function (e) {
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
            this.$store.dispatch("dashboard/orderSummary", date).then((res) => {
                this.delivered = res.data.data.delivered;
                this.canceled = res.data.data.canceled;
                this.rejected = res.data.data.rejected;
                this.loading.isActive = false;
            }).catch(() => {
                this.loading.isActive = false;
            });
        },
    },
};
</script>
