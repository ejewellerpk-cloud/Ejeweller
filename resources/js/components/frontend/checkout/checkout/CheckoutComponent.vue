<template>
    <LoadingComponent v-if="loading.isActive" :props="loading" skeleton="checkout" />
    <div class="row pb-[11rem] lg:pb-0">
        <div class="col-12 lg:col-8">
            <div class="flex items-center rounded-2xl w-fit mb-6 text-focus bg-[#EAF6FF]" v-if="outlets && outlets.length > 0">
                <div class="relative cursor-pointer">
                    <input @change="changeOrderType(orderTypeEnum.DELIVERY)" id="checkout-delivery"
                           :checked="orderType === orderTypeEnum.DELIVERY"
                           :value="orderTypeEnum.DELIVERY"
                           class="cart-switch w-full h-full absolute top-0 left-0 opacity-0 cursor-pointer"
                           type="radio">
                    <label class="py-1.5 px-3.5 rounded-2xl text-sm font-semibold capitalize transition cursor-pointer"
                           for="checkout-delivery">{{ $t('label.delivery') }}</label>
                </div>
                <div class="relative cursor-pointer">
                    <input @change="changeOrderType(orderTypeEnum.PICK_UP)" id="checkout-pickup"
                           :checked="orderType === orderTypeEnum.PICK_UP"
                           :value="orderTypeEnum.PICK_UP"
                           class="cart-switch w-full h-full absolute top-0 left-0 opacity-0 cursor-pointer"
                           type="radio">
                    <label class="py-1.5 px-3.5 rounded-2xl text-sm font-semibold capitalize transition cursor-pointer"
                           for="checkout-pickup">{{ $t('label.pick_up') }}</label>
                </div>
            </div>

            <div v-if="orderType === orderTypeEnum.PICK_UP" id="checkout-section-outlet"
                :class="validationErrors.outlet ? 'mb-6 rounded-2xl shadow-card ring-2 ring-red-500 ring-offset-2' : 'mb-6 rounded-2xl shadow-card'">
                <h4 class="font-bold capitalize p-4 border-b border-gray-100">{{ $t('label.store_location') }}</h4>

                <div v-if="outlets.length > 0" v-for="outlet in outlets" class="px-4 pt-4">
                    <div class="flex p-2 border transition-all rounded-lg" :class=" outlet.id === modelOutlet.id ? 'border-primary/50 bg-[#FFF4F1]' : 'border-[#F7F7F7] bg-[#F7F7F7]'">
                        <input type="radio" @change="outletAddress($event)" :id="outlet.name" :name="outlet.name" :value="outlet" :key="outlet" v-model="modelOutlet">
                        <label :for="outlet.name" class="px-2 text-sm capitalize cursor-pointer ">
                            <span class="font-semibold">{{ outlet.name }}</span> - {{ outlet.address }}, {{ outlet.city }}, {{ outlet.state }}, {{ outlet.zip_code }}
                        </label>
                    </div>
                </div>
            </div>

            <AddressComponent v-if="orderType === orderTypeEnum.DELIVERY" :slug="'shipping'"
                              :title="$t('label.shipping_address')" :show="true" :selectedAddress="getShippingAddress"
                              :method="shippingAddress" :highlightInvalid="validationErrors.shipping"/>

            <div v-if="orderType === orderTypeEnum.DELIVERY" class="flex items-start mb-6">
                <input checked="checked" :value="shippingAndBillingCheck" @click="checkBillingCheckBox($event)"
                       type="checkbox"
                       id="shipping-and-billing-is-same" class="cs-custom-checkbox">
                <label for="shipping-and-billing-is-same" class="font-medium pl-3 leading-none cursor-pointer">{{
                        $t('message.save_shipping_address_as_a_billing_address')
                    }}</label>
            </div>

            <AddressComponent v-if="orderType === orderTypeEnum.DELIVERY && !shippingAndBillingCheck"
                              :slug="'billing'"
                              :title="$t('label.billing_address')" :show="true"
                              :selectedAddress="getBillingAddress" :method="billingAddress"
                              :highlightInvalid="validationErrors.billing"/>

            <div class="mb-6 mt-6 rounded-2xl shadow-card">
                <h4 class="font-bold capitalize p-4 border-b border-gray-100">
                    Order Note
                </h4>
                <div class="p-4">
                    <textarea v-model="orderNote" class="w-full border rounded-lg p-3 text-sm focus:outline-none focus:border-primary" rows="3" placeholder="Write any specific instructions or notes for your order here..."></textarea>
                </div>
            </div>

            <div id="checkout-section-payment"
                :class="validationErrors.payment ? 'mb-6 mt-6 rounded-2xl shadow-card ring-2 ring-red-500 ring-offset-2' : 'mb-6 mt-6 rounded-2xl shadow-card'">
                <h4 class="font-bold capitalize p-4 border-b border-gray-100">
                    {{ $t('label.select_payment_method') }}
                </h4>

                <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 p-4">
                    <div v-if="Object.keys(cashOnDelivery).length > 0 && setting.site_cash_on_delivery === ActivityEnum.ENABLE"
                        @click.prevent="selectPaymentMethod(cashOnDelivery)"
                        :class="Object.keys(paymentMethod).length > 0 && cashOnDelivery.id === paymentMethod.id ? 'border-primary/50 bg-[#FFF4F1]' : 'border-white bg-white'"
                        class="flex flex-col items-center justify-center gap-2.5 py-4 rounded-lg shadow-xs cursor-pointer border">
                        <img class="h-6" :src="cashOnDelivery.image" alt="payment" loading="lazy" decoding="async" />
                        <span class="text-xs font-medium">{{ cashOnDelivery.name }}</span>
                    </div>

                    <div v-if="profile.balance >= total" @click.prevent="selectPaymentMethod(credit)"
                        :class="Object.keys(paymentMethod).length > 0 && credit.id === paymentMethod.id ? 'border-primary/50 bg-[#FFF4F1]' : 'border-white bg-white'"
                        class="flex flex-col items-center justify-center gap-2.5 py-4 rounded-lg shadow-xs cursor-pointer border">
                        <img class="h-6" :src="credit.image" alt="payment" loading="lazy" decoding="async" />
                        <span class="text-xs font-medium">{{ credit.name }} ({{ profile.balance }})</span>
                    </div>

                    <div v-if="setting.site_online_payment_gateway === ActivityEnum.ENABLE"
                        v-for="paymentGateway in paymentGateways" @click.prevent="selectPaymentMethod(paymentGateway)"
                        :class="Object.keys(paymentMethod).length > 0 && paymentGateway.id === paymentMethod.id ? 'border-primary/50 bg-[#FFF4F1]' : 'border-white bg-white'"
                        class="flex flex-col items-center justify-center gap-2.5 py-4 rounded-lg shadow-xs cursor-pointer border">
                        <img class="h-6" :src="paymentGateway.image" alt="payment" loading="lazy" decoding="async" />
                        <span class="text-xs font-medium">{{ paymentGateway.name }}</span>
                    </div>
                </div>
            </div>

            <div class="max-lg:hidden flex items-center justify-between gap-5 mt-10">
                <button type="button" @click.prevent="navigateBackToCart"
                             class="field-button w-fit font-semibold tracking-wide normal-case text-secondary bg-[#F7F7FC]">
                    {{ $t('button.back_to_cart') }}
                </button>

                <button type="button" v-if="setting.whatsapp_status === ActivityEnum.ENABLE && setting.whatsapp_checkout_status === ActivityEnum.ENABLE"
                    class="field-button w-fit font-semibold tracking-wide normal-case text-white bg-[#1AB759]"
                    @click.prevent="confirmOrder">
                    <i class="lab lab-whatsapp text-sm"></i>
                    {{ $t('button.proceed_to_whatsapp') }}
                </button>

                <button v-else @click.prevent="confirmOrder"
                    class="checkout-cta-glow-btn field-button w-fit font-semibold tracking-wide normal-case">
                    <span class="relative z-[1]">{{ $t('button.confirm_order') }}</span>
                </button>
            </div>
        </div>

        <div class="col-12 lg:col-4">
            <CouponComponent/>
            <SummeryComponent/>

        </div>
    </div>

    <!-- Mobile sticky confirm bar -->
    <div class="lg:hidden fixed bottom-[78px] left-0 right-0 z-20 bg-white border-t border-gray-200 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] px-4 py-3">
        <div class="flex items-center gap-3">
            <div class="min-w-0 flex-1">
                <p class="text-[10px] text-gray-400 font-bold uppercase leading-none mb-0.5">{{ $t('label.total') }}</p>
                <p class="text-lg font-extrabold text-primary leading-tight truncate">
                    {{ currencyFormat(total, setting.site_digit_after_decimal_point,
                        setting.site_default_currency_symbol, setting.site_currency_position) }}
                </p>
            </div>
            <button type="button" v-if="setting.whatsapp_status === ActivityEnum.ENABLE && setting.whatsapp_checkout_status === ActivityEnum.ENABLE"
                class="field-button shrink-0 !w-auto px-5 font-semibold tracking-wide normal-case text-white bg-[#1AB759]"
                @click.prevent="confirmOrder($event)">
                <i class="lab lab-whatsapp text-sm"></i>
                {{ $t('button.proceed_to_whatsapp') }}
            </button>
            <button v-else type="button" @click.prevent="confirmOrder($event)"
                class="checkout-cta-glow-btn field-button shrink-0 !w-auto px-5 font-semibold tracking-wide normal-case">
                <span class="relative z-[1]">{{ $t('button.confirm_order') }}</span>
            </button>
        </div>
    </div>
</template>

<script>
import orderTypeEnum from "../../../../enums/modules/orderTypeEnum";
import AddressComponent from "./AddressComponent.vue";
import SummeryComponent from "../SummeryComponent.vue";
import CouponComponent from "../CouponComponent.vue";
import router from "../../../../router";
import appService from "../../../../services/appService";
import alertService from "../../../../services/alertService";
import { pixelService } from "../../../../services/pixelService";
import {
    trackCheckoutStarted,
    trackCodSelected,
    trackOrderCompletedOnce,
    trackPaymentAttempted,
} from "../../../../services/analyticsEcommerceBridge";
import LoadingComponent from "../../components/LoadingComponent.vue";
import statusEnum from "../../../../enums/modules/statusEnum";
import sourceEnum from "../../../../enums/modules/sourceEnum";
import ENV from "../../../../config/env";
import ActivityEnum from "../../../../enums/modules/activityEnum";

export default {
    name: "CheckoutComponent",
    components: {CouponComponent, SummeryComponent, AddressComponent, LoadingComponent},
    inject: {
        promptAbandonedCheckoutLeave: {
            default: (next) => {
                if (typeof next === 'function') {
                    next();
                }
            }
        }
    },
    data() {
        return {
            loading: {
                isActive: false
            },
            enums : {
                statusEnum: statusEnum
            },
            orderTypeEnum: orderTypeEnum,
            shippingAndBillingCheck: true,
            billingStatus: false,
            modelOutlet: 0,
            paymentGateways: [],
            credit: {},
            cashOnDelivery: {},
            sourceEnum: sourceEnum,
            ActivityEnum: ActivityEnum,
            form: {},
            orderNote: "",
            validationErrors: {
                shipping: false,
                billing: false,
                payment: false,
                outlet: false,
            },
        }
    },
    computed: {
        setting: function () {
            return this.$store.getters['frontendSetting/lists'];
        },
        orderType: function () {
            return this.$store.getters['frontendCart/orderType'];
        },
        getShippingAddress: function () {
            return this.$store.getters['frontendCart/shippingAddress'];
        },
        getBillingAddress: function () {
            return this.$store.getters['frontendCart/billingAddress'];
        },
        outlets: function () {
            return this.$store.getters['frontendOutlet/lists'];
        },
        profile: function () {
            return this.$store.getters.authInfo;
        },
        paymentMethod: function () {
            return this.$store.getters['frontendCart/paymentMethod'];
        },
        subtotal: function () {
            return this.$store.getters['frontendCart/subtotal'];
        },
        discount: function () {
            return this.$store.getters['frontendCart/discount'];
        },
        total: function () {
            return this.$store.getters['frontendCart/total'];
        },
        getOutletAddress: function () {
            return this.$store.getters['frontendCart/outletAddress'];
        },
        cartCoupon: function () {
            return this.$store.getters['frontendCart/coupon'];
        },
        products: function () {
            return this.$store.getters['frontendCart/lists'];
        },
        shippingCharge: function () {
            return this.$store.getters['frontendCart/shippingCharge'];
        },
        totalTax: function () {
            return this.$store.getters['frontendCart/totalTax'];
        },
    },
    beforeRouteLeave(to, from, next) {
        if (from.path !== '/checkout/checkout') {
            next();
            return;
        }
        const goingToCart = to.name === 'frontend.checkout.cartList' || to.path === '/checkout/cart-list';
        const leavingCheckout = !to.path.startsWith('/checkout');
        if ((goingToCart || leavingCheckout) && to.name !== 'frontend.account.orderDetails') {
            this.promptAbandonedCheckoutLeave(next);
            return;
        }
        next();
    },
    mounted() {
        this.scheduleCheckoutAnalytics();
        this.bootstrapCheckoutData();
    },
    methods: {
        scheduleCheckoutAnalytics: function () {
            const run = () => {
                const cartItems = this.$store.getters['frontendCart/lists'];
                const cartTotal = this.$store.getters['frontendCart/total'];
                pixelService.trackInitiateCheckout(cartItems, cartTotal);
                trackCheckoutStarted(cartTotal, window.FACEBOOK_PIXEL_CURRENCY || 'PKR');
            };
            if (typeof requestIdleCallback === 'function') {
                requestIdleCallback(run, { timeout: 2500 });
            } else {
                setTimeout(run, 0);
            }
        },
        bootstrapCheckoutData: function () {
            this.loading.isActive = true;
            const activeStatus = this.enums.statusEnum.ACTIVE;

            Promise.allSettled([
                this.$store.dispatch('frontendOrderArea/lists'),
                this.$store.dispatch('frontendOutlet/lists', { status: activeStatus }),
                this.$store.dispatch('frontendPaymentGateway/lists', { status: activeStatus }),
            ]).then((results) => {
                if (results[1].status === 'fulfilled') {
                    this.applyOutletList(results[1].value);
                }
                if (results[2].status === 'fulfilled') {
                    this.applyPaymentGateways(results[2].value);
                }
                return this.$store.dispatch('frontendCart/recalculateTotals');
            }).finally(() => {
                this.loading.isActive = false;
            });
        },
        applyPaymentGateways: function (res) {
            const gateways = res?.data?.data || [];
            this.paymentGateways = [];
            this.credit = {};
            this.cashOnDelivery = {};

            gateways.forEach((gateway) => {
                if (gateway.slug === 'credit') {
                    this.credit = gateway;
                } else if (gateway.slug === 'cashondelivery') {
                    this.cashOnDelivery = gateway;
                    if (this.setting.site_cash_on_delivery === this.ActivityEnum.ENABLE) {
                        this.selectPaymentMethod(this.cashOnDelivery);
                    }
                } else {
                    this.paymentGateways.push(gateway);
                }
            });
        },
        applyOutletList: function (res) {
            const outlets = res?.data?.data || [];
            if (outlets.length === 0 && this.orderType === this.orderTypeEnum.PICK_UP) {
                this.$store.dispatch('frontendCart/updateOrderType', this.orderTypeEnum.DELIVERY);
            } else if (outlets.length > 0 && this.orderType === this.orderTypeEnum.PICK_UP) {
                const cachedOutlet = this.getOutletAddress;
                if (cachedOutlet && cachedOutlet.id) {
                    const outletExists = outlets.find((o) => o.id === cachedOutlet.id);
                    if (!outletExists) {
                        this.modelOutlet = outlets[0];
                        this.$store.dispatch('frontendCart/outletAddress', outlets[0]);
                    } else {
                        this.modelOutlet = cachedOutlet;
                    }
                } else {
                    this.modelOutlet = outlets[0];
                    this.$store.dispatch('frontendCart/outletAddress', outlets[0]);
                }
            }
        },
        navigateBackToCart: function () {
            this.promptAbandonedCheckoutLeave(() => {
                this.$router.push({ name: 'frontend.checkout.cartList' });
            });
        },
        changeOrderType: function (e) {
            this.$store.dispatch('frontendCart/updateOrderType', e)
        },
        shippingAddress: function (e) {
            this.validationErrors.shipping = false;
            this.$store.dispatch('frontendCart/shippingAddress', e).then().catch();
            if (this.shippingAndBillingCheck) {
                this.validationErrors.billing = false;
                this.$store.dispatch('frontendCart/billingAddress', e).then().catch();
            }
        },
        billingAddress: function (e) {
            this.validationErrors.billing = false;
            this.$store.dispatch('frontendCart/billingAddress', e).then().catch();
        },
        outletAddress: function(e) {
            this.validationErrors.outlet = false;
            setTimeout(() => {
                this.$store.dispatch('frontendCart/outletAddress', this.modelOutlet).then().catch();
            }, 100);
        },
        checkBillingCheckBox: function (e) {
            if (e.target.checked) {
                this.billingStatus           = false;
                this.shippingAndBillingCheck = true;
                this.$store.dispatch('frontendCart/billingAddress', this.getShippingAddress).then().catch();
            } else {
                this.billingStatus           = true;
                this.shippingAndBillingCheck = false;
            }
        },
        selectPaymentMethod: function (paymentMethod) {
            this.validationErrors.payment = false;
            this.$store.dispatch("frontendCart/paymentMethod", paymentMethod);
            if (paymentMethod?.slug === 'cashondelivery') {
                trackCodSelected(this.total, window.FACEBOOK_PIXEL_CURRENCY || 'PKR');
            }
        },
        currencyFormat: function (amount, decimal, currency, position) {
            return appService.currencyFormat(amount, decimal, currency, position);
        },
        scrollToCheckoutSection: function (sectionId) {
            const el = document.getElementById(sectionId);
            if (!el) {
                return;
            }
            const header = document.querySelector('header');
            const headerOffset = header
                ? Math.ceil(header.getBoundingClientRect().height) + 16
                : 96;
            const top = el.getBoundingClientRect().top + window.scrollY - headerOffset;
            window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
        },
        validateCheckout: function () {
            this.validationErrors = {
                shipping: false,
                billing: false,
                payment: false,
                outlet: false,
            };

            let firstSectionId = null;
            let errorMessage = null;

            const markInvalid = (key, sectionId, message) => {
                this.validationErrors[key] = true;
                if (!firstSectionId) {
                    firstSectionId = sectionId;
                    errorMessage = message;
                }
            };

            if (this.orderType === orderTypeEnum.DELIVERY) {
                const shipping = this.getShippingAddress;
                let billing = this.getBillingAddress;

                if (this.shippingAndBillingCheck && Object.keys(shipping).length > 0) {
                    this.$store.dispatch('frontendCart/billingAddress', shipping);
                    billing = shipping;
                }

                if (Object.keys(shipping).length === 0) {
                    markInvalid('shipping', 'checkout-section-shipping', this.$t("message.shipping_and_billing_address"));
                } else if (!this.$store.getters.authStatus) {
                    if (!shipping.full_name || !shipping.email || !shipping.phone || !shipping.address || !shipping.country || !shipping.state || !shipping.city) {
                        markInvalid('shipping', 'checkout-section-shipping', this.$t("message.fill_all_address_details"));
                    }
                } else if (!shipping.id || shipping.id === 0) {
                    markInvalid('shipping', 'checkout-section-shipping', this.$t("message.please_select_shipping_address") || "Please select a valid shipping address from your address book.");
                }

                if (!this.shippingAndBillingCheck) {
                    if (Object.keys(billing).length === 0) {
                        markInvalid('billing', 'checkout-section-billing', this.$t("message.shipping_and_billing_address"));
                    } else if (!this.$store.getters.authStatus) {
                        if (!billing.full_name || !billing.email || !billing.phone || !billing.address || !billing.country || !billing.state || !billing.city) {
                            markInvalid('billing', 'checkout-section-billing', this.$t("message.fill_all_address_details"));
                        }
                    } else if (!billing.id || billing.id === 0) {
                        markInvalid('billing', 'checkout-section-billing', this.$t("message.please_select_shipping_address") || "Please select a valid billing address from your address book.");
                    }
                }
            } else if (this.orderType === orderTypeEnum.PICK_UP) {
                const outletAddress = this.getOutletAddress;
                if (!outletAddress || Object.keys(outletAddress).length === 0) {
                    markInvalid('outlet', 'checkout-section-outlet', this.$t("message.please_select_an_outlet"));
                }
            }

            if (Object.keys(this.paymentMethod).length === 0) {
                markInvalid('payment', 'checkout-section-payment', this.$t('message.payment_method_required'));
            }

            if (firstSectionId) {
                alertService.error(errorMessage);
                this.$nextTick(() => {
                    this.scrollToCheckoutSection(firstSectionId);
                });
                return false;
            }

            return true;
        },
        confirmOrder: function (e) {
            if (!this.validateCheckout()) {
                return;
            }

            if (e && e.target) {
                e.target.disabled = true;
            }
            this.loading.isActive = true;

            trackPaymentAttempted({
                method: this.paymentMethod?.slug || this.paymentMethod?.name,
                total: this.total,
                currency: window.FACEBOOK_PIXEL_CURRENCY || 'PKR',
            });

            this.form = {
                subtotal: this.subtotal,
                discount: this.discount,
                tax: this.totalTax,
                shipping_charge: this.shippingCharge,
                total: this.total,
                order_type: this.orderType,
                shipping_id: Object.keys(this.getShippingAddress).length > 0 ? (this.getShippingAddress.id ? this.getShippingAddress.id : 0) : 0,
                billing_id: Object.keys(this.getBillingAddress).length > 0 ? (this.getBillingAddress.id ? this.getBillingAddress.id : 0) : 0,
                guest_info: !this.$store.getters.authStatus ? JSON.stringify(this.getShippingAddress) : null,
                outlet_id: Object.keys(this.getOutletAddress).length > 0 ? this.getOutletAddress.id : 0,
                coupon_id: Object.keys(this.cartCoupon).length > 0 ? this.cartCoupon.id : 0,
                source: sourceEnum.WEB,
                payment_method: Object.keys(this.paymentMethod).length > 0 ? this.paymentMethod.id : 0,
                note: this.orderNote,
                products: JSON.stringify(this.products)
            }

            this.$store.dispatch('frontendOrder/save', this.form).then(orderResponse => {
                this.loading.isActive = false;
                
                if (orderResponse.data.data.guest_token) {
                    this.$store.commit('authLogin', {
                        token: orderResponse.data.data.guest_token,
                        user: orderResponse.data.data.guest_user,
                        menu: orderResponse.data.data.guest_menu,
                        permission: orderResponse.data.data.guest_permission,
                        defaultPermission: orderResponse.data.data.guest_defaultPermission,
                        defaultMenu: orderResponse.data.data.guest_defaultMenu
                    });
                }

                let paymentSlug = Object.keys(this.paymentMethod).length > 0 ? this.paymentMethod.slug : '';
                if (orderResponse.data.data.is_cod) {
                    trackOrderCompletedOnce({
                        id: orderResponse.data.data.id,
                        total: orderResponse.data.data.total ?? this.total,
                        currency_code: window.FACEBOOK_PIXEL_CURRENCY || 'PKR',
                    });
                    this.$router.push({ name: 'frontend.account.orderDetails', params: { id: orderResponse.data.data.id }, query: { status: 'success' } });
                } else if (paymentSlug) {
                    window.location.href = ENV.API_URL + "/payment/" + paymentSlug + "/pay/" + orderResponse.data.data.id;
                } else {
                    alertService.error(this.$t('message.payment_method_required'));
                }
            }).catch((err) => {
                this.loading.isActive = false;
                if (e && e.target) {
                    e.target.disabled = false;
                }
                const fieldErrors = err.response?.data?.errors;
                if (fieldErrors && typeof fieldErrors === 'object') {
                    Object.values(fieldErrors).forEach((error) => {
                        alertService.error(Array.isArray(error) ? error[0] : error);
                    });
                } else {
                    alertService.error(err.response.data.message);
                }
            });
        }
    }
}
</script>

