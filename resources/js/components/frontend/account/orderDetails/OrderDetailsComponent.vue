<template>
    <LoadingComponent v-if="loading.isActive" :props="loading" skeleton="order-list" :skeleton-count="1" />

    <div class="order-details mobile:mb-24">
        <div class="flex items-center gap-3 mb-5">
            <button
                type="button"
                @click.prevent="$router.back()"
                class="w-10 h-10 rounded-xl border border-gray-200 bg-white text-heading flex items-center justify-center shadow-sm hover:bg-slate-50 transition"
                :aria-label="$t('button.back') || 'Back'"
            >
                <i class="lab-line-undo text-lg"></i>
            </button>
            <div class="min-w-0">
                <h2 class="capitalize text-xl sm:text-2xl font-bold text-heading tracking-tight">{{ $t('label.order_details') }}</h2>
                <p class="text-xs sm:text-sm text-text truncate">#{{ order.order_serial_no }} · {{ order.order_datetime }}</p>
            </div>
        </div>

        <!-- Status hero -->
        <div class="rounded-2xl border border-gray-100 bg-white p-4 sm:p-6 mb-4 shadow-[0_2px_12px_rgba(15,23,42,0.04)]">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 mb-1">{{ $t('label.order_status') }}</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold capitalize" :class="orderStatusClass(order.status)">
                            {{ enums.orderStatusEnumArray[order.status] }}
                        </span>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold capitalize"
                            :class="enums.paymentStatusEnum.PAID === order.payment_status ? 'text-[#2AC769] bg-[#E2FFEE]' : 'text-[#FB4E4E] bg-[#FFE8E8]'">
                            {{ enums.paymentStatusEnumArray[order.payment_status] }}
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 mb-1">{{ $t('label.total') }}</p>
                    <p class="text-xl font-extrabold text-heading">{{ order.total_currency_price }}</p>
                </div>
            </div>

            <div v-if="order.status !== enums.orderStatusEnum.CANCELED && order.status !== enums.orderStatusEnum.REJECTED && order.order_type !== enums.orderTypeEnum.PICK_UP"
                class="pt-2 pb-8">
                <ul class="w-full flex items-center justify-center">
                    <li v-for="(track, index) in tracks" :key="'track-' + index" class="w-full flex items-center justify-center gap-1 relative">
                        <hr :class="{ '!bg-success': track.step <= order.status }"
                            class="block border-none w-full h-1 rounded-xl bg-gray-200" />
                        <i :class="{ 'lab-fill-save !bg-success text-white': track.step <= order.status }"
                            class="flex-shrink-0 w-7 h-7 !leading-7 text-center rounded-full bg-gray-200 lab-font-size-16" />
                        <hr :class="{ '!bg-success': track.step <= order.status }"
                            class="block border-none w-full h-1 rounded-xl bg-gray-200" />
                        <span class="absolute top-9 left-1/2 -translate-x-1/2 w-16 sm:w-20 text-[10px] sm:text-xs leading-[14px] text-center capitalize text-text">
                            {{ track.title }}
                        </span>
                    </li>
                </ul>
            </div>

            <div v-if="order.status !== enums.orderStatusEnum.CANCELED && order.status !== enums.orderStatusEnum.REJECTED && order.order_type === enums.orderTypeEnum.PICK_UP"
                class="pt-2 pb-8">
                <ul class="w-full flex items-center justify-center">
                    <li v-for="(track, index) in pickupTracks" :key="'pickup-' + index" class="w-full flex items-center justify-center gap-1 relative">
                        <hr :class="{ '!bg-success': track.step <= order.status }"
                            class="block border-none w-full h-1 rounded-xl bg-gray-200" />
                        <i :class="{ 'lab-fill-save !bg-success text-white': track.step <= order.status }"
                            class="flex-shrink-0 w-7 h-7 !leading-7 text-center rounded-full bg-gray-200 lab-font-size-16" />
                        <hr :class="{ '!bg-success': track.step <= order.status }"
                            class="block border-none w-full h-1 rounded-xl bg-gray-200" />
                        <span class="absolute top-9 left-1/2 -translate-x-1/2 w-16 sm:w-20 text-[10px] sm:text-xs leading-[14px] text-center capitalize text-text">
                            {{ track.title }}
                        </span>
                    </li>
                </ul>
            </div>

            <div v-if="order.status === enums.orderStatusEnum.CANCELED || order.status === enums.orderStatusEnum.REJECTED"
                class="flex items-center justify-center gap-2 py-3 px-4 rounded-xl border border-red-100 bg-red-50 text-red-600">
                <i class="lab-fill-close-circle text-lg"></i>
                <span class="text-sm font-bold capitalize">
                    {{ order.status === enums.orderStatusEnum.CANCELED ? $t('label.order_cancelled') : $t('label.order_rejected') }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
            <div class="lg:col-span-2 space-y-4">
                <div class="rounded-2xl border border-gray-100 bg-white p-4 sm:p-5 shadow-[0_2px_12px_rgba(15,23,42,0.04)]">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-400 mb-3">{{ $t('label.order_id') }}</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start justify-between gap-3">
                            <span class="text-sm text-text">{{ $t('label.order_id') }}</span>
                            <span class="text-sm font-semibold text-heading text-right">#{{ order.order_serial_no }}</span>
                        </li>
                        <li class="flex items-start justify-between gap-3">
                            <span class="text-sm text-text">{{ $t('label.order_date') }}</span>
                            <span class="text-sm font-medium text-heading text-right">{{ order.order_datetime }}</span>
                        </li>
                        <li class="flex items-start justify-between gap-3">
                            <span class="text-sm text-text">{{ $t('label.order_type') }}</span>
                            <span class="text-sm font-medium text-heading text-right capitalize">{{ enums.orderTypeEnumArray[order.order_type] }}</span>
                        </li>
                        <li class="flex items-start justify-between gap-3">
                            <span class="text-sm text-text">{{ $t('label.payment_method') }}</span>
                            <span class="text-sm font-medium text-heading text-right capitalize">{{ order.payment_method_name }}</span>
                        </li>
                        <li v-if="order.status === enums.orderStatusEnum.REJECTED" class="flex items-start justify-between gap-3">
                            <span class="text-sm text-text">{{ $t('label.reason') }}</span>
                            <span class="text-sm font-medium text-heading text-right capitalize">{{ order.reason }}</span>
                        </li>
                    </ul>
                </div>

                <div
                    v-if="order.order_type === enums.orderTypeEnum.DELIVERY && orderAddress.length > 0"
                    v-for="(address, addressIndex) in orderAddress"
                    :key="'addr-' + addressIndex"
                    class="rounded-2xl border border-gray-100 bg-white overflow-hidden shadow-[0_2px_12px_rgba(15,23,42,0.04)]"
                >
                    <h3 class="px-4 py-3 text-sm font-bold text-heading bg-slate-50 border-b border-gray-100 capitalize">
                        {{ address.address_type === enums.addressTypeEnum.SHIPPING ? $t('label.shipping_address') : $t('label.billing_address') }}
                    </h3>
                    <ul class="p-4 space-y-2.5">
                        <li class="text-sm"><span class="text-text">{{ $t('label.name') }}:</span> <span class="font-semibold capitalize text-heading">{{ address.full_name }}</span></li>
                        <li class="text-sm"><span class="text-text">{{ $t('label.phone') }}:</span> <span class="font-medium text-heading" dir="ltr">{{ address.country_code }}{{ address.phone }}</span></li>
                        <li v-if="address.email" class="text-sm"><span class="text-text">{{ $t('label.email') }}:</span> <span class="font-medium text-heading break-all">{{ address.email }}</span></li>
                        <li class="text-sm leading-relaxed">
                            <span class="text-text">{{ $t('label.address') }}:</span>
                            <span class="font-medium capitalize text-heading">
                                <span v-if="address.address">{{ address.address }}</span>
                                <span class="block" :class="address.address ? 'mt-1' : ''">
                                    <span v-if="address.city">{{ address.city }}, </span>
                                    <span v-if="address.state">{{ address.state }}, </span>
                                    <span v-if="address.country">{{ address.country }}, </span>
                                    <span v-if="address.zip_code">{{ address.zip_code }}</span>
                                </span>
                            </span>
                        </li>
                    </ul>
                </div>

                <div
                    v-if="order.order_type === enums.orderTypeEnum.PICK_UP && Object.keys(outletAddress).length > 0"
                    class="rounded-2xl border border-gray-100 bg-white overflow-hidden shadow-[0_2px_12px_rgba(15,23,42,0.04)]"
                >
                    <h3 class="px-4 py-3 text-sm font-bold text-heading bg-slate-50 border-b border-gray-100 capitalize">
                        {{ $t('label.pick_up_address') }}
                    </h3>
                    <ul class="p-4 space-y-2.5">
                        <li class="text-sm"><span class="text-text">{{ $t('label.name') }}:</span> <span class="font-semibold capitalize text-heading">{{ outletAddress.name }}</span></li>
                        <li v-if="outletAddress.phone" class="text-sm"><span class="text-text">{{ $t('label.phone') }}:</span> <span class="font-medium text-heading" dir="ltr">{{ outletAddress.country_code }}{{ outletAddress.phone }}</span></li>
                        <li v-if="outletAddress.email" class="text-sm"><span class="text-text">{{ $t('label.email') }}:</span> <span class="font-medium text-heading break-all">{{ outletAddress.email }}</span></li>
                        <li class="text-sm leading-relaxed">
                            <span class="text-text">{{ $t('label.address') }}:</span>
                            <span class="font-medium capitalize text-heading">
                                <span v-if="outletAddress.address">{{ outletAddress.address }}</span>
                                <span class="block" :class="outletAddress.address ? 'mt-1' : ''">
                                    <span v-if="outletAddress.city">{{ outletAddress.city }}, </span>
                                    <span v-if="outletAddress.state">{{ outletAddress.state }}, </span>
                                    <span v-if="outletAddress.zip_code">{{ outletAddress.zip_code }}</span>
                                </span>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="lg:col-span-3">
                <div class="rounded-2xl border border-gray-100 bg-white overflow-hidden shadow-[0_2px_12px_rgba(15,23,42,0.04)]">
                    <div class="px-4 sm:px-5 py-3.5 border-b border-gray-100 bg-slate-50 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-heading capitalize">{{ $t('label.order_summary') }}</h3>
                        <span class="text-xs font-semibold text-text">{{ orderProducts.length }} {{ $t('label.product') }}</span>
                    </div>

                    <ul class="divide-y divide-gray-100">
                        <li
                            v-for="(orderProduct, productIndex) in orderProducts"
                            :key="'product-' + productIndex"
                            class="p-4 sm:p-5 flex gap-3"
                        >
                            <img
                                :src="orderProduct.product_image"
                                alt="product"
                                class="w-16 h-16 sm:w-[72px] sm:h-[72px] object-cover rounded-xl border border-gray-100 flex-shrink-0 bg-slate-50"
                            />
                            <div class="min-w-0 flex-1">
                                <h4 class="text-sm font-semibold text-heading capitalize line-clamp-2">{{ orderProduct.product_name }}</h4>
                                <p v-if="orderProduct.variation_names" class="mt-0.5 text-xs text-text line-clamp-2">{{ orderProduct.variation_names }}</p>
                                <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-3 text-sm">
                                        <span class="font-bold text-heading">{{ orderProduct.subtotal_currency_price }}</span>
                                        <span class="text-text">× {{ orderProduct.quantity }}</span>
                                    </div>
                                    <router-link
                                        v-if="order.status === enums.orderStatusEnum.DELIVERED && !orderProduct.product_user_review"
                                        :to="{ name: 'frontend.account.productReview', params: { slug: orderProduct.product_slug } }"
                                        class="text-xs font-semibold capitalize py-1.5 px-3 rounded-full whitespace-nowrap bg-[#FFF4F1] text-primary transition hover:bg-primary hover:text-white">
                                        {{ $t('button.write_review') }}
                                    </router-link>
                                    <router-link
                                        v-if="order.status === enums.orderStatusEnum.DELIVERED && orderProduct.product_user_review"
                                        :to="{ name: 'frontend.account.productReview.edit', params: { slug: orderProduct.product_slug, id: orderProduct.product_user_review_id } }"
                                        class="text-xs font-semibold capitalize py-1.5 px-3 rounded-full whitespace-nowrap bg-[#FFF4F1] text-primary transition hover:bg-primary hover:text-white">
                                        {{ $t('button.edit_review') }}
                                    </router-link>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <div class="border-t border-dashed border-gray-200 px-4 sm:px-5 py-4 space-y-2.5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-text capitalize">{{ $t('label.subtotal') }}</span>
                            <span class="font-semibold text-heading">{{ order.subtotal_currency_price }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-text capitalize">{{ $t('label.tax_fee') }}</span>
                            <span class="font-semibold text-heading">{{ order.tax_currency_price }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-text capitalize">{{ $t('label.shipping_charge') }}</span>
                            <span class="font-semibold text-heading">{{ order.shipping_charge_currency_price }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-text capitalize">{{ $t('label.discount') }}</span>
                            <span class="font-semibold text-heading">{{ order.discount_currency_price }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between px-4 sm:px-5 py-4 bg-slate-50 border-t border-gray-100">
                        <span class="capitalize font-bold text-heading">{{ $t('label.total') }}</span>
                        <span class="capitalize font-extrabold text-lg text-heading">{{ order.total_currency_price }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="order.status !== enums.orderStatusEnum.CANCELED" class="mt-5 flex flex-wrap gap-3">
            <OrderReceiptComponent :order="order" :orderProducts="orderProducts" :orderUser="orderUser"
                :orderAddress="orderAddress[0]" />
            <button v-if="order.status === enums.orderStatusEnum.PENDING"
                @click="changeStatus(enums.orderStatusEnum.CANCELED)" type="button"
                class="px-5 py-3 capitalize rounded-full whitespace-nowrap text-center text-sm font-semibold border border-danger text-danger bg-white hover:bg-red-50 transition">
                {{ $t('button.cancel_order') }}
            </button>
            <router-link v-if="order.status === enums.orderStatusEnum.DELIVERED && order.return_and_refund"
                :to="{ name: 'frontend.account.returnOrder.request', params: { id: order.id } }"
                class="px-5 py-3 capitalize rounded-full whitespace-nowrap text-center text-sm font-semibold border border-danger text-danger bg-white hover:bg-red-50 transition">
                {{ $t('button.return_request') }}
            </router-link>
        </div>
    </div>

    <div id="payment-modal"
        :class="Object.keys(route.query).length > 0 && route.query.status === 'success' && cart.length > 0 && modal ? 'modal-active' : ''"
        class=" fixed inset-0 z-50 p-3 w-screen h-dvh overflow-y-auto bg-black/50 transition-all duration-300 opacity-0 invisible">
        <div class="w-full rounded-xl mx-auto bg-white transition-all duration-300 max-w-[360px]">
            <div class="px-4 py-5 relative">
                <button @click.prevent="reset" type="button"
                    class="lab-line-circle-cross text-lg text-[#E93C3C] absolute top-3 right-3"></button>
                <h3 class="font-medium text-center mb-5">{{ $t('message.thank_you_for_your_order') }}</h3>
                <img :src="setting.image_confirm" alt="confirm-image" class="w-[120px] mx-auto mb-5" />
                <h4 class="font-semibold text-center mb-5">{{ $t('message.your_order_is_successfully_placed') }}</h4>
                <button type="button" @click.prevent="reset" class="field-button font-semibold normal-case">{{
                    $t('button.see_your_order_details') }}
                </button>

            </div>
        </div>
    </div>
</template>

<script>
import LoadingComponent from "../../components/LoadingComponent";
import orderStatusEnum from "../../../../enums/modules/orderStatusEnum";
import paymentStatusEnum from "../../../../enums/modules/paymentStatusEnum";
import addressTypeEnum from "../../../../enums/modules/addressTypeEnum";
import appService from "../../../../services/appService";
import alertService from "../../../../services/alertService";
import targetService from "../../../../services/targetService";
import { useRoute } from 'vue-router'
import OrderReceiptComponent from "./OrderReceiptComponent";
import { trackOrderCompletedOnce } from "../../../../services/analyticsEcommerceBridge";
import { pixelService } from "../../../../services/pixelService";
import orderTypeEnum from "../../../../enums/modules/orderTypeEnum";
import activityEnum from "../../../../enums/modules/activityEnum";
import env from "../../../../config/env";

export default {
    name: "OrderDetailsComponent",
    components: { LoadingComponent, OrderReceiptComponent },
    setup() {
        const route = useRoute();
        return { route: route };
    },
    data() {
        return {
            loading: {
                isActive: false,
            },
            tracks: [
                { step: 1, title: this.$t('label.order_pending') },
                { step: 5, title: this.$t('label.order_confirmed') },
                { step: 7, title: this.$t('label.order_on_the_way') },
                { step: 10, title: this.$t('label.order_delivered') },
            ],
            pickupTracks: [
                { step: 1, title: this.$t('label.order_pending') },
                { step: 5, title: this.$t('label.order_confirmed') },
                { step: 10, title: this.$t('label.order_delivered') },
            ],
            enums: {
                orderStatusEnum: orderStatusEnum,
                paymentStatusEnum: paymentStatusEnum,
                addressTypeEnum: addressTypeEnum,
                orderTypeEnum: orderTypeEnum,
                activityEnum: activityEnum,
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
                orderTypeEnumArray: {
                    [orderTypeEnum.DELIVERY]: this.$t("label.delivery"),
                    [orderTypeEnum.PICK_UP]: this.$t("label.pick_up")
                }
            },
            modal: false

        };
    },
    computed: {
        setting: function () {
            return this.$store.getters['frontendSetting/lists'];
        },
        order: function () {
            return this.$store.getters['frontendOrder/show'];
        },
        orderProducts: function () {
            return this.$store.getters['frontendOrder/orderProducts'];
        },
        orderUser: function () {
            return this.$store.getters['frontendOrder/orderUser'];
        },
        orderAddress: function () {
            return this.$store.getters['frontendOrder/orderAddress'];
        },
        outletAddress: function () {
            return this.$store.getters['frontendOrder/outletAddress'];
        },
        cart: function () {
            return this.$store.getters['frontendCart/lists'];
        },
        paymentMethod: function () {
            return this.$store.getters['frontendCart/paymentMethod'];
        }
    },
    mounted() {
        if (this.$route.params.id) {
            this.loading.isActive = true;
            this.$store.dispatch("frontendOrder/show", this.$route.params.id).then(res => {
                // Track Purchase event on Facebook Pixel
                if (this.$route.query.status === "success") {
                    pixelService.trackPurchase(res.data.data);
                    trackOrderCompletedOnce({
                        id: res.data.data.id,
                        total: res.data.data.total,
                        currency_code: res.data.data.currency_code,
                    });
                }

                if (this.$route.query.status === "success" && this.setting.whatsapp_status === activityEnum.ENABLE && this.setting.whatsapp_checkout_status === activityEnum.ENABLE && this.setting.whatsapp_number) {
                    this.modal = false;
                    this.handleClick(res.data.data, res.data.data.outlet_address);
                } else {
                    this.modal = true;
                }
                this.loading.isActive = false;
            }).catch((error) => {
                this.loading.isActive = false;
            });
        }
    },
    methods: {
        showTarget: function (id, cClass) {
            targetService.showTarget(id, cClass);
        },
        reset: function () {
            if (this.cart.length > 0 && Object.keys(this.paymentMethod).length > 0 && this.paymentMethod.slug === 'credit') {
                this.$store.dispatch("profile").then().catch();
            }
            this.$store.dispatch("frontendCart/resetCart").then().catch();
        },
        orderStatusClass: function (status) {
            return appService.orderStatusClass(status);
        },
        changeStatus: function (status) {
            appService.cancelOrder().then((res) => {
                try {
                    this.loading.isActive = true;
                    this.$store.dispatch("frontendOrder/changeStatus", {
                        id: this.$route.params.id,
                        status: status,
                    }).then((res) => {
                        this.loading.isActive = false;
                        alertService.successFlip(
                            1,
                            this.$t("label.status")
                        );
                    }).catch((err) => {
                        this.loading.isActive = false;
                        alertService.error(err.response.data.message);
                    });
                } catch (err) {
                    this.loading.isActive = false;
                    alertService.error(err.response.data.message);
                }
            }).catch((err) => {
                this.loading.isActive = false;
            });
        },
        handleClick(order, outletAddress) {
            let text = `
            ${this.setting.company_name}
            ****************************************************
            ${this.$t('label.order_id')}           : #${order.order_serial_no}
            ${this.$t("label.order_type")}         : ${this.enums.orderTypeEnumArray[order.order_type]}
            ${this.$t('label.order_date')}         : ${order.order_date}${order.order_time}
            ${this.$t('label.order_status')}       : ${this.enums.orderStatusEnumArray[order.status]}
            ${this.$t("label.payment_status")}     : ${this.enums.paymentStatusEnumArray[order.payment_status]}
            ${this.$t("label.payment_method")}     : ${order.payment_method_name}

            `;

            text += `
            --------------------------
            ${this.$t('label.order_details')}
            --------------------------
            `;
            for (let i = 0; i < order.order_products.length; i++) {
                const item = order.order_products[i];

                text += `
            ${i + 1})  ${item.product_name}
            `;

                text += `
                --------------------------
                ${this.$t('label.variation')}     : ${item.variation_names}
                ${this.$t('label.price')}         : ${item.currency_price}
                ${this.$t('label.quantity')}      : ${item.quantity}
                ${this.$t('label.discount')}      : ${item.discount_currency_price}
                --------------------------
                ${this.$t('label.total')}         : ${item.subtotal_currency_price}
                --------------------------
                                `;

            }


            text += `
            ${this.$t('label.subtotal')}             : ${order.subtotal_currency_price}
            ${this.$t('label.tax_fee')}              : ${order.tax_currency_price}
            ${this.$t('label.shipping_charge')}      : ${order.shipping_charge_currency_price}
            ${this.$t('label.discount')}             : ${order.discount_currency_price}
            --------------------------
            ${this.$t('label.total')}                : ${order.total_currency_price}
            --------------------------
            `;
            if (order.order_type === this.enums.orderTypeEnum.DELIVERY) {

                for (let i = 0; i < order.order_address.length; i++) {
                    let address = order.order_address[i];
                    let address_type = '';
                    if (address.address_type === this.enums.addressTypeEnum.SHIPPING) {
                        address_type = this.$t('label.shipping_address')
                    }
                    else {
                        address_type = this.$t('label.billing_address')
                    }
                    text += `
            ${address_type}
            --------------------------
            ${this.$t('label.name')}       : ${address.full_name}
            ${this.$t('label.phone')}      : ${address.country_code}${address.phone}
            ${this.$t('label.email')}      : ${address.email}
            ${this.$t('label.address')}    : ${address.address}
                            ${address.city} ${address.state} ${address.country} ${address.zip_code}
            `;
                }
            }
            else {
                text += `
            ${this.$t('label.pick_up_address')}
            --------------------------
            ${this.$t('label.name')}        : ${outletAddress.name}
            ${this.$t('label.phone')}       : ${outletAddress.country_code}${outletAddress.phone}
            ${this.$t('label.email')}       : ${outletAddress.email}
            ${this.$t('label.address')}     : ${outletAddress.address}
                            ${outletAddress.city} ${outletAddress.state} ${outletAddress.country} ${outletAddress.zip_code}
            `;
            }
            text = encodeURIComponent(text);

            this.reset();

            if (env.DEMO === "true" || env.DEMO === true || env.DEMO === "1" || env.DEMO === 1) {
                window.open("https://api.whatsapp.com/send?phone=" + this.setting.whatsapp_calling_code + '' + this.setting.whatsapp_number + "&text=" + text, "_blank");
            } else {
                window.location = "https://api.whatsapp.com/send?phone=" + this.setting.whatsapp_calling_code + '' + this.setting.whatsapp_number + "&text=" + text;
            }
        }
    },
}
</script>