<template>
    <LoadingComponent :props="loading" />
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

            <!-- Premium Exit-Intent Abandoned Cart Modal -->
            <div v-if="showAbandonedModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl border border-gray-100 animate-fade-in relative animate-duration-300">
                    
                    <!-- Close button in top right -->
                    <button @click.prevent="dismissModalAndLeave" type="button" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 transition-colors w-8 h-8 rounded-full border border-gray-100 flex items-center justify-center">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                    
                    <!-- Premium Header Image / Icon -->
                    <div class="bg-gradient-to-br from-primary/10 to-orange-500/10 p-8 flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center shadow-lg mb-4 animate-bounce">
                            <i class="fa-solid fa-cart-arrow-down text-primary text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-black text-heading leading-tight tracking-tight">Wait! Don't Miss Out!</h3>
                        <p class="text-sm text-gray-500 font-medium mt-1">Complete your order now to lock in these benefits</p>
                    </div>
                    
                    <!-- Modal Content -->
                    <div class="p-6 flex flex-col gap-6">
                        
                        <!-- 1. Total Savings Badge -->
                        <div v-if="totalSavings > 0" class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 flex items-center gap-3.5 shadow-sm">
                            <div class="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center text-white shrink-0 shadow-md">
                                <i class="fa-solid fa-piggy-bank text-lg"></i>
                            </div>
                            <div class="flex flex-col text-left">
                                <span class="text-xs text-emerald-600 font-bold uppercase tracking-wider leading-none mb-1">Your Total Savings</span>
                                <span class="text-xl font-extrabold text-emerald-700 leading-none">
                                    {{
                                        currencyFormat(totalSavings, setting.site_digit_after_decimal_point,
                                            setting.site_default_currency_symbol, setting.site_currency_position)
                                    }} Saved!
                                </span>
                            </div>
                        </div>

                        <!-- 2. Free Shipping Alert -->
                        <div v-if="qualifiesForFreeShipping" class="bg-blue-50 border border-blue-100 rounded-2xl p-4 flex items-center gap-3.5 shadow-sm">
                            <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white shrink-0 shadow-md">
                                <i class="fa-solid fa-truck-fast text-lg"></i>
                            </div>
                            <div class="flex flex-col text-left">
                                <span class="text-xs text-blue-600 font-bold uppercase tracking-wider leading-none mb-1">Bonus unlocked</span>
                                <span class="text-base font-extrabold text-blue-700 leading-none">FREE SHIPPING qualifies for this order!</span>
                            </div>
                        </div>

                        <!-- 3. Preview Products In Cart -->
                        <div class="flex flex-col gap-3">
                            <h4 class="text-xs text-gray-400 font-bold uppercase tracking-widest text-left">Items in your cart</h4>
                            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none whitespace-nowrap">
                                <div v-for="(item, idx) in cartLists" :key="idx" class="w-16 h-16 rounded-xl border border-gray-100 overflow-hidden relative shrink-0 shadow-sm bg-gray-50">
                                    <img :src="item.image" alt="cart item" class="w-full h-full object-cover" />
                                    <span class="absolute bottom-0 right-0 bg-primary text-white text-[9px] font-black px-1.5 py-0.5 rounded-tl-lg leading-none">x{{ item.quantity }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Action Buttons -->
                        <div class="flex flex-col gap-2.5 mt-2">
                            <button @click.prevent="showAbandonedModal = false" type="button" 
                                class="w-full h-12 rounded-full bg-primary hover:bg-primary/95 text-white font-extrabold flex items-center justify-center gap-2 shadow-[0_4px_15px_rgba(253,139,14,0.35)] transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]">
                                <i class="fa-solid fa-circle-check text-lg"></i>
                                <span>Complete My Order</span>
                            </button>
                            <button @click.prevent="dismissModalAndLeave" type="button" 
                                class="w-full h-11 rounded-full bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-500 hover:text-red-500 font-bold text-sm transition-all duration-300 active:scale-[0.98]">
                                Lose Savings & Leave
                            </button>
                        </div>

                        <!-- 5. Dynamic WhatsApp Contact Footer -->
                        <div class="pt-4 border-t border-gray-100 text-center text-xs text-gray-400 font-medium">
                            Any Questions or issues? 
                            <a :href="'https://api.whatsapp.com/send?phone=' + setting.whatsapp_calling_code + setting.whatsapp_number + '&text=' + encodeURIComponent('Hi, I need assistance with my checkout.')" 
                               target="_blank" 
                               class="text-primary hover:underline font-bold inline-flex items-center gap-1 ml-1">
                                <i class="fa-brands fa-whatsapp text-emerald-500"></i> Contact us
                            </a>
                        </div>

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
import LoadingComponent from "../components/LoadingComponent.vue";
import activityEnum from "../../../enums/modules/activityEnum";

export default {
    name: "CheckoutComponent",
    components: { LoadingComponent, CouponComponent, CartListComponent },
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
        }
    },
    beforeRouteLeave(to, from, next) {
        if (from.path === '/checkout/checkout' && !to.path.startsWith('/checkout') && this.isList && !this.showAbandonedModal) {
            if (to.name === 'frontend.account.orderDetails') {
                next();
                return;
            }
            this.abandonedNextCallback = next;
            this.showAbandonedModal = true;
        } else {
            next();
        }
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
        goBack: function () {
            router.go(-1)
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
            if (this.abandonedNextCallback) {
                this.abandonedNextCallback();
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