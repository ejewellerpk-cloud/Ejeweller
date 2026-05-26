<template>
    <LoadingComponent v-if="loading.isActive" :props="loading" skeleton="checkout" />
    <section class="mb-28 sm:mb-20">
        <div class="container">
            <!--  Header Route Start -->
            <div class="flex items-start gap-4 mb-7">
                <button @click.prevent="goBack"
                    class="lab lab-line-undo lab-font-size-20 !text-xl !font-bold text-primary"></button>
                <router-view name="header" />
            </div>

            <!--  Header Route Close -->

            <!--  Checkbox Start -->
            <ul class="multi-step w-full max-w-sm mx-auto my-12 pt-2 pb-5 px-4 flex items-center justify-center">
                <li class="list-none w-full flex after:content-[''] after:w-full after:h-1 last:after:hidden last:w-fit"
                    :class="currentRoute === '/checkout/checkout' ? 'after:bg-success' : 'after:bg-[#EFF0F6]'">
                    <router-link :to="{ name: 'frontend.checkout.cartList' }"
                        @click="onCartStepNavigate"
                        class="flex flex-col items-center gap-4 -mt-[13px] relative">
                        <i v-if="currentRoute === '/checkout/checkout'"
                            class="lab lab-fill-save text-lg w-[30px] h-[30px] !leading-[30px] text-center rounded-full text-white bg-success"></i>
                        <span v-else class="w-[30px] h-[30px] border-[4px] rounded-full border-success bg-white"></span>
                        <small :class="currentRoute === '/checkout/cart-list' ? 'text-success' : 'text-secondary'"
                            class="text-sm font-medium capitalize absolute -bottom-8">
                            {{ $t('label.cart') }}
                        </small>
                    </router-link>
                </li>

                <li
                    class="list-none w-full flex after:content-[''] after:w-full after:h-1 last:after:hidden last:w-fit after:bg-[#EFF0F6]">
                    <router-link :to="{ name: 'frontend.checkout.checkout' }"
                        class="flex flex-col items-center gap-4 -mt-[13px] relative">
                        <span class="w-[30px] h-[30px] border-[4px] rounded-full"
                              :class="currentRoute === '/checkout/checkout' ? 'border-success bg-white' : 'border-[#D9DBE9] bg-[#D9DBE9]'"></span>
                        <small :class="currentRoute === '/checkout/checkout' ? 'text-success' : 'text-secondary'"
                            class="text-sm font-medium capitalize absolute -bottom-8">
                            {{ $t('label.checkout') }}
                        </small>
                    </router-link>
                </li>
            </ul>
            <!--  Checkbox Close -->

            <!-- Default Router -->
            <router-view />
            <!-- Default Router -->

            <!-- Exit-Intent Abandoned Cart Modal -->
            <div v-if="showAbandonedModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-3">
                <div class="bg-white rounded-2xl w-full max-w-xs sm:max-w-sm overflow-hidden shadow-2xl border border-gray-100 animate-fade-in relative animate-duration-300">
                    
                    <button @click.prevent="dismissModalAndLeave" type="button" class="absolute top-2.5 right-2.5 z-10 text-gray-400 hover:text-red-500 transition-colors w-7 h-7 rounded-full border border-gray-100 flex items-center justify-center bg-white">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                    
                    <div class="bg-gradient-to-br from-primary/10 to-orange-500/10 px-3 pt-3 pb-2.5 flex items-center gap-2.5 pr-10">
                        <div class="w-9 h-9 rounded-full bg-white flex items-center justify-center shadow-sm shrink-0">
                            <i class="fa-solid fa-cart-arrow-down text-primary text-base"></i>
                        </div>
                        <div class="text-left min-w-0">
                            <h3 class="text-base font-black text-heading leading-tight">Wait! Don't Miss Out!</h3>
                            <p class="text-[11px] text-gray-500 font-medium leading-tight">Complete your order now</p>
                        </div>
                    </div>
                    
                    <div class="px-3 py-2.5 flex flex-col gap-2">
                        
                        <div v-if="totalSavings > 0" class="bg-emerald-50 border border-emerald-100 rounded-lg px-2 py-1.5 flex items-center gap-2">
                            <i class="fa-solid fa-piggy-bank text-emerald-600 text-xs shrink-0"></i>
                            <span class="text-[11px] font-extrabold text-emerald-700 leading-tight min-w-0">
                                {{
                                    currencyFormat(totalSavings, setting.site_digit_after_decimal_point,
                                        setting.site_default_currency_symbol, setting.site_currency_position)
                                }} saved
                            </span>
                        </div>

                        <div v-if="qualifiesForFreeShipping" class="bg-blue-50 border border-blue-100 rounded-lg px-2 py-1.5 flex items-center gap-2">
                            <i class="fa-solid fa-truck-fast text-blue-600 text-xs shrink-0"></i>
                            <span class="text-[11px] font-extrabold text-blue-700 leading-tight">FREE shipping on this order</span>
                        </div>

                        <div class="rounded-lg border border-gray-100 bg-[#F7F7FC] p-2 flex items-center gap-2">
                            <div class="flex items-center gap-1 overflow-x-auto scrollbar-none min-w-0 flex-1">
                                <div v-for="(item, idx) in cartLists" :key="idx" class="w-9 h-9 rounded-md border border-gray-100 overflow-hidden relative shrink-0 bg-gray-50">
                                    <img :src="item.image" alt="cart item" class="w-full h-full object-cover" />
                                    <span class="absolute bottom-0 right-0 bg-primary text-white text-[7px] font-black px-0.5 leading-none">x{{ item.quantity }}</span>
                                </div>
                            </div>
                            <div class="shrink-0 pl-2 border-l border-gray-200 flex flex-col gap-0.5 text-right">
                                <div class="text-[9px] text-gray-400 font-bold uppercase leading-none">{{ $t('label.quantity') }}: <span class="text-heading">{{ cartTotalQuantity }}</span></div>
                                <div class="text-[9px] text-gray-400 font-bold uppercase leading-none">{{ $t('label.total') }}: <span class="text-primary text-xs font-extrabold">{{
                                    currencyFormat(cartTotal, setting.site_digit_after_decimal_point,
                                        setting.site_default_currency_symbol, setting.site_currency_position)
                                }}</span></div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <button @click.prevent="showAbandonedModal = false; abandonedNextCallback = null" type="button" 
                                class="checkout-cta-glow-btn w-full h-9 rounded-full bg-primary hover:bg-primary/95 text-white text-xs font-bold flex items-center justify-center gap-1 transition-transform duration-300 active:scale-[0.98]">
                                <i class="fa-solid fa-circle-check relative z-[1] text-sm"></i>
                                <span class="relative z-[1]">Complete My Order</span>
                            </button>
                            <button @click.prevent="dismissModalAndLeave" type="button" 
                                class="w-full h-8 rounded-full bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-500 hover:text-red-500 font-semibold text-[11px] transition-all duration-300 active:scale-[0.98]">
                                Lose Savings & Leave
                            </button>
                        </div>

                        <p class="text-center text-[10px] text-gray-400 leading-tight">
                            Questions?
                            <a :href="'https://api.whatsapp.com/send?phone=' + setting.whatsapp_calling_code + setting.whatsapp_number + '&text=' + encodeURIComponent('Hi, I need assistance with my checkout.')" 
                               target="_blank" 
                               class="text-primary hover:underline font-bold inline-flex items-center gap-0.5">
                                <i class="fa-brands fa-whatsapp text-emerald-500"></i> WhatsApp
                            </a>
                        </p>

                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import CartListComponent from "./cartList/CartListComponent.vue";
import router from "../../../router";
import appService from "../../../services/appService";
import CouponComponent from "./CouponComponent.vue";
import { trackCheckoutAbandoned } from "../../../services/analyticsEcommerceBridge";
import LoadingComponent from "../components/LoadingComponent.vue";
import activityEnum from "../../../enums/modules/activityEnum";

export default {
    name: "CheckoutComponent",
    components: { LoadingComponent, CouponComponent, CartListComponent },
    provide() {
        return {
            promptAbandonedCheckoutLeave: (next) => this.promptAbandonedCheckoutLeave(next),
        };
    },
    data() {
        return {
            loading: {
                isActive: false,
            },
            currentRoute: null,
            enums: {
                activityEnum: activityEnum
            },
            showAbandonedModal: false,
            abandonedNextCallback: null
        }
    },
    computed: {
        isList: function () {
            return this.$store.getters['frontendCart/isList'];
        },
        setting: function () {
            return this.$store.getters['frontendSetting/lists'];
        },
        logged: function () {
            return this.$store.getters.authStatus;
        },
        cartLists: function () {
            return this.$store.getters['frontendCart/lists'] || [];
        },
        couponDiscount: function () {
            return this.$store.getters['frontendCart/discount'] || 0;
        },
        totalSavings: function () {
            return (this.$store.getters['frontendCart/totalSavings'] || 0) + this.couponDiscount;
        },
        shippingCharge: function () {
            return this.$store.getters['frontendCart/shippingCharge'] || 0;
        },
        qualifiesForFreeShipping: function () {
            return this.shippingCharge === 0 && this.$store.getters['frontendCart/subtotal'] > 0;
        },
        cartTotal: function () {
            return this.$store.getters['frontendCart/total'] || 0;
        },
        cartTotalQuantity: function () {
            return this.cartLists.reduce((sum, item) => sum + (parseInt(item.quantity, 10) || 0), 0);
        }
    },
    beforeRouteLeave(to, from, next) {
        const leavingCheckoutStep = from.path === '/checkout/checkout';
        const goingToCartList = to.path === '/checkout/cart-list';
        const leavingCheckoutFlow = !to.path.startsWith('/checkout');

        if (leavingCheckoutStep && this.isList && !this.showAbandonedModal && (goingToCartList || leavingCheckoutFlow)) {
            if (to.name === 'frontend.account.orderDetails') {
                next();
                return;
            }
            this.abandonedNextCallback = next;
            this.showAbandonedModal = true;
            return;
        }
        next();
    },
    mounted() {
        this.currentRoute = this.$route.path;
        this.checkGuestAccess();
        this.$store.dispatch('frontendCart/listChecker').then(res => {
            if (!res.status) {
                this.$router.push({ name: 'frontend.home' });
            }
        }).catch((err) => {
            if (!err.status) {
                this.$router.push({ name: 'frontend.home' });
            }
        })
    },
    methods: {
        onCartStepNavigate: function (event) {
            if (this.currentRoute === '/checkout/checkout' && this.isList) {
                event.preventDefault();
                this.promptAbandonedCheckoutLeave(() => {
                    this.$router.push({ name: 'frontend.checkout.cartList' });
                });
            }
        },
        promptAbandonedCheckoutLeave: function (next) {
            if (!this.isList) {
                if (typeof next === 'function') {
                    next();
                }
                return;
            }
            if (this.showAbandonedModal) {
                return;
            }
            if (typeof next === 'function') {
                this.abandonedNextCallback = next;
            }
            this.showAbandonedModal = true;
        },
        goBack: function () {
            if (this.currentRoute === '/checkout/checkout' && this.isList) {
                this.promptAbandonedCheckoutLeave(() => {
                    router.go(-1);
                });
                return;
            }
            router.go(-1);
        },
        checkGuestAccess: function () {
            if (this.setting.site_guest_checkout == this.enums.activityEnum.DISABLE && !this.logged && this.$route.path !== '/checkout/cart-list') {
                this.$router.push({ name: 'auth.login' });
            }
        },
        currencyFormat(amount, decimal, currency, position) {
            return appService.currencyFormat(amount, decimal, currency, position);
        },
        dismissModalAndLeave: function () {
            this.showAbandonedModal = false;
            trackCheckoutAbandoned(
                this.$store.getters['frontendCart/total'],
                window.FACEBOOK_PIXEL_CURRENCY || 'PKR'
            );
            if (this.abandonedNextCallback) {
                const next = this.abandonedNextCallback;
                this.abandonedNextCallback = null;
                next();
            }
        }
    },
    watch: {
        $route(to, from) {
            this.currentRoute = to.path;
            this.checkGuestAccess();
        },
        isList: {
            deep: true,
            handler(isListObject) {
                if (!isListObject) {
                    this.$router.push({ name: 'frontend.home' });
                }
            }
        },
        setting: {
            deep: true,
            handler() {
                this.checkGuestAccess();
            }
        }
    }
}
</script>