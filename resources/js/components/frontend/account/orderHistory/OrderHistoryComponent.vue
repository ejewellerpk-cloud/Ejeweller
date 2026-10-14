<template>
    <LoadingComponent v-if="loading.isActive" :props="loading" skeleton="order-list" />

    <div class="order-history mobile:mb-20">
        <div class="mb-6">
            <h2 class="capitalize text-2xl font-bold text-heading tracking-tight">{{ $t('label.order_history') }}</h2>
            <p class="mt-1 text-sm text-text">{{ $t('message.order_status_follows') || 'Track and manage your past orders.' }}</p>
        </div>

        <!-- Empty state -->
        <div v-if="!loading.isActive && orders.length === 0"
            class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-14 text-center shadow-sm">
            <div class="mx-auto mb-4 max-w-[220px]">
                <img class="w-full h-auto opacity-80" :src="setting.not_found" alt="Not Found">
            </div>
            <h3 class="text-lg font-semibold text-heading mb-1">{{ $t('message.no_data_found') }}</h3>
            <router-link :to="{ name: 'frontend.product' }"
                class="inline-flex mt-4 items-center justify-center rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">
                {{ $t('button.continue_shopping') || 'Continue Shopping' }}
            </router-link>
        </div>

        <template v-else-if="orders.length > 0">
            <!-- Mobile cards -->
            <div class="md:hidden space-y-3">
                <article
                    v-for="order in orders"
                    :key="order.id"
                    class="rounded-2xl border border-gray-100 bg-white p-4 shadow-[0_2px_12px_rgba(15,23,42,0.04)]"
                >
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $t('label.order_id') }}</p>
                            <h3 class="text-base font-bold text-heading truncate">#{{ order.order_serial_no }}</h3>
                            <p class="mt-0.5 text-xs text-text">{{ order.order_datetime }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-base font-extrabold text-heading">{{ order.total_currency_price }}</p>
                            <p class="mt-0.5 text-xs text-text">{{ order.order_items }} {{ $t('label.product') }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold capitalize"
                            :class="orderStatusClass(order.status)">
                            {{ enums.orderStatusEnumArray[order.status] }}
                        </span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold capitalize"
                            :class="enums.paymentStatusEnum.PAID === order.payment_status ? 'text-[#2AC769] bg-[#E2FFEE]' : 'text-[#FB4E4E] bg-[#FFE8E8]'">
                            {{ enums.paymentStatusEnumArray[order.payment_status] }}
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <router-link
                            :to="{ name: 'frontend.account.orderDetails', params: { id: order.id } }"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-primary px-2 py-2.5 text-sm font-semibold text-white transition active:scale-[0.98]">
                            <i class="fa-regular fa-eye text-xs"></i>
                            {{ $t('label.view') }}
                        </router-link>
                        <router-link
                            :to="{ name: 'frontend.trackOrder', query: { order: order.order_serial_no } }"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-2 py-2.5 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 active:scale-[0.98]">
                            <i class="fa-solid fa-location-dot text-xs"></i>
                            {{ $t('label.track_order') || 'Track' }}
                        </router-link>
                        <button
                            type="button"
                            @click.prevent="handleOrderReceiptModal(order)"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-slate-50 px-2 py-2.5 text-sm font-semibold text-heading transition hover:bg-slate-100 active:scale-[0.98]">
                            <i class="lab lab-fill-printer text-sm"></i>
                            {{ $t('label.download') }}
                        </button>
                    </div>
                </article>
            </div>

            <!-- Desktop table -->
            <div class="hidden md:block rounded-2xl border border-gray-100 bg-white shadow-[0_2px_12px_rgba(15,23,42,0.04)] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50/80 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3.5 font-semibold">{{ $t('label.order_id') }}</th>
                                <th class="px-5 py-3.5 font-semibold">{{ $t('label.products') }}</th>
                                <th class="px-5 py-3.5 font-semibold">{{ $t('label.status') }}</th>
                                <th class="px-5 py-3.5 font-semibold">{{ $t('label.payment') }}</th>
                                <th class="px-5 py-3.5 font-semibold">{{ $t('label.amount') }}</th>
                                <th class="px-5 py-3.5 font-semibold text-right">{{ $t('label.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="order in orders"
                                :key="order.id"
                                class="border-t border-gray-100 transition hover:bg-slate-50/60"
                            >
                                <td class="px-5 py-4">
                                    <h5 class="font-semibold text-heading">#{{ order.order_serial_no }}</h5>
                                    <p class="text-xs text-text mt-0.5">{{ order.order_datetime }}</p>
                                </td>
                                <td class="px-5 py-4 text-heading">
                                    {{ order.order_items }} {{ $t('label.product') }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold capitalize"
                                        :class="orderStatusClass(order.status)">
                                        {{ enums.orderStatusEnumArray[order.status] }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold capitalize"
                                        :class="enums.paymentStatusEnum.PAID === order.payment_status ? 'text-[#2AC769] bg-[#E2FFEE]' : 'text-[#FB4E4E] bg-[#FFE8E8]'">
                                        {{ enums.paymentStatusEnumArray[order.payment_status] }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-bold text-heading">{{ order.total_currency_price }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        <router-link
                                            :to="{ name: 'frontend.account.orderDetails', params: { id: order.id } }"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-heading hover:border-primary hover:text-primary transition">
                                            {{ $t('label.view') }}
                                        </router-link>
                                        <router-link
                                            :to="{ name: 'frontend.trackOrder', query: { order: order.order_serial_no } }"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                                            <i class="fa-solid fa-location-dot text-[10px]"></i>
                                            {{ $t('label.track_order') || 'Track' }}
                                        </router-link>
                                        <button
                                            type="button"
                                            @click.prevent="handleOrderReceiptModal(order)"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90 transition">
                                            {{ $t('label.download') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-5 px-1" v-if="orders.length > 0">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <PaginationTextComponent :props="{ page: paginationPage }" />
                    <PaginationComponent
                        @pagination-change-page="list"
                        :data="pagination"
                        :limit="1"
                        :keep-length="false"
                    />
                </div>
            </div>
        </template>
    </div>

    <div id="orderReceiptPrint"
        class="fixed inset-0 z-50 p-3 w-screen h-dvh overflow-y-auto bg-black/50 transition-all duration-500 opacity-0 invisible">
        <div class="w-full max-w-[302px] mx-auto bg-white transition-all duration-500" id="print">
            <div class="modal-header hidden-print">
                <button type="button" @click="reset"
                    class="modal-close flex items-center justify-center gap-1.5 py-2 px-4 rounded bg-[#FB4E4E]">
                    <i class="lab lab-fill-close-circle lab-font-size-16 text-white"></i>
                    <span class="text-xs leading-5 capitalize text-white">{{ $t('button.close') }}</span>
                </button>
                <button type="button" v-print="printObj"
                    class="flex items-center justify-center gap-1.5 py-2 px-4 rounded bg-[#1AB759]">
                    <i class="lab lab-fill-printer lab-font-size-16 text-white"></i>
                    <span class="text-xs leading-5 capitalize text-white">{{ $t('button.print_invoice') }}</span>
                </button>
            </div>
            <OrderReceiptComponent v-if="orderId" :method="reset" :orderId="orderId" />
        </div>
    </div>
</template>

<script>
import LoadingComponent from "../../components/LoadingComponent";
import orderStatusEnum from "../../../../enums/modules/orderStatusEnum";
import paymentStatusEnum from "../../../../enums/modules/paymentStatusEnum";
import askEnum from "../../../../enums/modules/askEnum";
import appService from "../../../../services/appService";
import targetService from "../../../../services/targetService";
import PaginationComponent from "../../components/PaginationComponent";
import PaginationTextComponent from "../../components/PaginationTextComponent";
import OrderReceiptComponent from "../../components/OrderReceiptComponent";
import print from "vue3-print-nb";

export default {
    name: "OrderHistoryComponent",
    components: { LoadingComponent, PaginationComponent, PaginationTextComponent, OrderReceiptComponent },
    directives: {
        print
    },
    data() {
        return {
            loading: {
                isActive: false,
            },
            printObj: {
                id: "print",
                popTitle: this.$t("menu.order_receipt"),
            },
            enums: {
                paymentStatusEnum: paymentStatusEnum,
                orderStatusEnum: orderStatusEnum,
                orderStatusEnumArray: {
                    [orderStatusEnum.PENDING]: this.$t("label.pending"),
                    [orderStatusEnum.CONFIRMED]: this.$t("label.confirmed"),
                    [orderStatusEnum.ON_THE_WAY]: this.$t("label.on_the_way"),
                    [orderStatusEnum.DELIVERED]: this.$t("label.delivered"),
                    [orderStatusEnum.CANCELED]: this.$t("label.canceled"),
                    [orderStatusEnum.REJECTED]: this.$t("label.rejected"),
                },
                paymentStatusEnumArray: {
                    [paymentStatusEnum.PAID]: this.$t("label.paid"),
                    [paymentStatusEnum.UNPAID]: this.$t("label.unpaid")
                },
            },
            search: {
                paginate: 1,
                page: 1,
                per_page: 10,
                order_column: "id",
                order_by: "desc",
                active: askEnum.YES,
            },
            orderId: "",
        };
    },
    mounted() {
        this.list();
    },
    computed: {
        setting: function () {
            return this.$store.getters['frontendSetting/lists'];
        },
        orders: function () {
            return this.$store.getters["frontendOrder/lists"];
        },
        pagination: function () {
            return this.$store.getters["frontendOrder/pagination"];
        },
        paginationPage: function () {
            return this.$store.getters["frontendOrder/page"];
        },
    },
    methods: {
        orderStatusClass: function (status) {
            return appService.orderStatusClass(status);
        },
        handleOrderReceiptModal: function (order) {
            this.orderId = order.id;
            this.$nextTick(() => {
                targetService.showTarget("orderReceiptPrint", 'modal-active');
            });
        },
        reset: function () {
            targetService.hideTarget("orderReceiptPrint", 'modal-active');
            setTimeout(() => {
                this.orderId = "";
            }, 500);
        },
        list: function (page = 1) {
            this.loading.isActive = true;
            this.search.page = page;
            this.$store.dispatch("frontendOrder/lists", {
                search: this.search
            }).then(() => {
                this.loading.isActive = false;
            }).catch(() => {
                this.loading.isActive = false;
            });
        },
    }
}
</script>

<style scoped>
@media print {
    .hidden-print {
        display: none !important;
    }
}
</style>
