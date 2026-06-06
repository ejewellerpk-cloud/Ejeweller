<template>
    <div v-show="theme === 'frontend'">
        <main class="">
            <FrontendNavbarComponent />
            <FrontendCartComponent />
            <router-view v-slot="{ Component, route }">
                <keep-alive include="HomeComponent">
                    <component :is="Component" :key="route.meta.keepAlive ? route.name : route.fullPath" />
                </keep-alive>
            </router-view>
            <FrontendMobileSideBarComponent />
            <FrontendMobileNavBarComponent />
            <FrontendMobileCategoryComponent />
            <FrontendMobileAccountComponent />
            <FrontendCookiesComponent />
            <FrontendFooterComponent v-if="!$route.meta.hideFooter" />
        </main>
    </div>

    <div v-if="theme === 'backend'">
        <main class="db-main" v-if="logged">
            <BackendNavbarComponent />
            <BackendMenuComponent />
            <router-view></router-view>
            <BackendAiSidebarComponent />
        </main>
        <div v-if="!logged">
            <router-view></router-view>
        </div>
    </div>

    <!-- Abandoned Cart Checkout Reminder Banner -->
    <transition name="slide-left-fade">
        <div v-if="showCheckoutReminder && theme === 'frontend'" class="checkout-reminder-popup fixed top-[100px] sm:top-[120px] right-4 sm:right-6 z-50 w-[70%] max-w-[240px] sm:w-[20vw] sm:max-w-[260px] bg-white/95 backdrop-blur-md border border-red-100 p-3 rounded-2xl shadow-[0_8px_30px_rgba(0,0,0,0.12)] flex items-start gap-2 transition-all duration-300">
            <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center flex-shrink-0 text-red-500 animate-pulse text-base">
                <i class="lab-line-bag"></i>
            </div>
            <div class="flex-grow pr-1">
                <h5 class="font-bold text-gray-900 text-xs mb-0.5">Don't leave! 🛒</h5>
                <p class="text-[10px] text-gray-600 leading-tight mb-2">Complete your checkout to secure your items!</p>
                <div class="flex items-center">
                    <router-link :to="{name: 'frontend.checkout.checkout'}" @click="showCheckoutReminder = false" class="w-full text-center px-3 py-1.5 rounded-lg bg-primary text-white text-[10px] font-extrabold shadow-sm hover:bg-heading active:scale-[0.97] transition-all duration-300">
                        Checkout Now ➔
                    </router-link>
                </div>
            </div>
            <button type="button" @click="showCheckoutReminder = false" class="text-gray-400 hover:text-gray-600 flex-shrink-0 transition-all duration-300 text-xs mt-0.5">
                <i class="lab-line-circle-cross"></i>
            </button>
        </div>
    </transition>

    <!-- Native Reactive Floating WhatsApp Button -->
    <a v-if="showWhatsappFloating"
       :href="'https://wa.me/' + (setting.whatsapp_calling_code + setting.whatsapp_number).replace(/[^0-9]/g, '')"
       class="whatsapp-btn fixed z-[10000] flex items-center justify-center rounded-full bg-[#25D366] text-white hover:scale-110 active:scale-95 transition-all duration-300 shadow-[0_4px_15px_rgba(37,211,102,0.35)]"
       :class="whatsappClass"
       target="_blank"
       title="Chat with us on WhatsApp">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 sm:w-10 sm:h-10 fill-current" viewBox="0 0 16 16">
          <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
        </svg>
    </a>
</template>

<script>
import BackendNavbarComponent from "./layouts/backend/BackendNavbarComponent";
import BackendMenuComponent from "./layouts/backend/BackendMenuComponent";
import FrontendNavbarComponent from "./layouts/frontend/FrontendNavBarComponent";
import FrontendFooterComponent from "./layouts/frontend/FrontendFooterComponent";
import FrontendCartComponent from "./layouts/frontend/FrontendCartComponent";
import FrontendMobileNavBarComponent from "./layouts/frontend/FrontendMobileNavBarComponent";
import FrontendMobileCategoryComponent from "./layouts/frontend/FrontendMobileCategoryComponent";
import FrontendMobileAccountComponent from "./layouts/frontend/FrontendMobileAccountComponent";
import FrontendMobileSideBarComponent from "./layouts/frontend/FrontendMobileSideBarComponent";
import FrontendCookiesComponent from "./layouts/frontend/FrontendCookiesComponent";
import { identifyAnalyticsUser } from "../services/analyticsEcommerceBridge";
import { resolveThemeFromRoute } from "../services/themeResolver";
import DisplayModeEnum from "../enums/modules/displayModeEnum";
import env from "../config/env";
import BackendAiSidebarComponent from "./layouts/backend/BackendAiSidebarComponent.vue";

export default {
    name: "DefaultComponent",
    components: {
        FrontendMobileSideBarComponent,
        FrontendMobileAccountComponent,
        FrontendMobileCategoryComponent,
        FrontendMobileNavBarComponent,
        FrontendCartComponent,
        FrontendNavbarComponent,
        FrontendFooterComponent,
        BackendNavbarComponent,
        BackendMenuComponent,
        FrontendCookiesComponent,
        BackendAiSidebarComponent,
    },
    data() {
        return {
            theme: "frontend",
            showCheckoutReminder: false,
            hasShownReminder: false,
            reminderTimer: null,
            tickerInterval: null,
            originalTitle: "",
            visibilityListenerAdded: false
        }
    },
    created() {
        this.routeClassDefine(this.$route);
    },
    beforeMount() {
        this.displayModeDefine();
        this.$store.dispatch('frontendSetting/lists').then(res => {
            this.$store.dispatch("globalState/init", {
                language_id: res.data.data.site_default_language,
                search_restaurant: "",
                location: null,
                latitude: null,
                longitude: null
            });
        }).catch();
    },
    computed: {
        logged: function () {
            return this.$store.getters.authStatus;
        },
        displayMode: function () {
            return this.$store.getters['globalState/lists'].display_mode;
        },
        cartList: function () {
            return this.$store.getters['frontendCart/lists'];
        },
        setting: function () {
            return this.$store.getters['frontendSetting/lists'];
        },
        showWhatsappFloating: function () {
            if (this.theme !== 'frontend') {
                return false;
            }
            if (!this.setting || this.setting.whatsapp_status !== 5 || this.setting.whatsapp_floating_status !== 5 || !this.setting.whatsapp_number) {
                return false;
            }
            const routeName = this.$route.name;
            if (routeName && routeName.startsWith('frontend.checkout')) {
                return false;
            }
            return true;
        },
        whatsappClass: function () {
            // Desktop dimensions: w-16 h-16 bottom-6 right-6
            // Mobile dimensions: w-14 h-14 right-4/right-6
            let baseClasses = "right-4 sm:right-6 w-14 h-14 sm:w-16 sm:h-16";
            const routeName = this.$route.name;
            
            // On Product Details page on mobile
            if (routeName === 'frontend.product.details') {
                // Keep it well above the sticky mobile Add to Cart / Buy Now bar (bottom-[78px])
                return `${baseClasses} bottom-[158px] sm:bottom-6`;
            }
            
            // On Checkout / Cart pages on mobile
            else if (routeName && routeName.startsWith('frontend.checkout')) {
                // Keep it above sticky mobile checkout buttons/bars
                return `${baseClasses} bottom-[96px] sm:bottom-6`;
            }
            
            // On other standard frontend pages on mobile
            else {
                // Floating above the mobile bottom nav (height includes safe-area)
                return `${baseClasses} bottom-[calc(var(--mobile-bottom-nav-height,5rem)+0.75rem)] sm:bottom-6`;
            }
        }
    },
    methods: {
        routeClassDefine: function (route) {
            if (!route) return;
            document.body.classList.remove('theme-frontend', 'product-details-active', 'checkout-active');
            this.theme = resolveThemeFromRoute(route);
            if (this.theme === 'frontend') {
                document.body.classList.add('theme-frontend');
                if (route.name === 'frontend.product.details') {
                    document.body.classList.add('product-details-active');
                } else if (route.name && route.name.startsWith('frontend.checkout')) {
                    document.body.classList.add('checkout-active');
                }
            }
        },
        displayModeDefine: function () {
            let dir = "ltr";
            if (this.$store.getters['globalState/lists'].display_mode === DisplayModeEnum.RTL) {
                dir = "rtl";
            }
            document.documentElement.setAttribute("dir", dir);
        },
        startAbandonedCartTimer() {
            this.stopAbandonedCartTimer();
            if (this.hasShownReminder || (this.$route.name && this.$route.name.startsWith('frontend.checkout'))) return;

            this.reminderTimer = setTimeout(() => {
                if (this.cartList && this.cartList.length > 0 && (!this.$route.name || !this.$route.name.startsWith('frontend.checkout'))) {
                    this.showCheckoutReminder = true;
                    this.hasShownReminder = true;
                }
            }, 45000); // 45 seconds delay
        },
        stopAbandonedCartTimer() {
            if (this.reminderTimer) {
                clearTimeout(this.reminderTimer);
                this.reminderTimer = null;
            }
        },
        startTabTicker() {
            if (typeof window === 'undefined') return;
            if (!this.visibilityListenerAdded) {
                document.addEventListener('visibilitychange', this.handleVisibilityChange);
                this.visibilityListenerAdded = true;
            }
        },
        stopTabTicker() {
            if (this.tickerInterval) {
                clearInterval(this.tickerInterval);
                this.tickerInterval = null;
            }
            if (this.originalTitle) {
                document.title = this.originalTitle;
                this.originalTitle = "";
            }
        },
        handleVisibilityChange() {
            if (document.visibilityState === 'hidden') {
                if (this.cartList && this.cartList.length > 0) {
                    this.originalTitle = document.title;
                    let count = 0;
                    this.tickerInterval = setInterval(() => {
                        if (this.cartList && this.cartList.length > 0) {
                            const totalItems = this.cartList.reduce((sum, item) => sum + item.quantity, 0);
                            const titles = [
                                `🛒 (${totalItems}) Item${totalItems > 1 ? 's' : ''} in cart!`,
                                `⚡ Secure them now!`,
                                this.originalTitle
                            ];
                            document.title = titles[count % titles.length];
                            count++;
                        } else {
                            this.stopTabTicker();
                        }
                    }, 2000);
                }
            } else {
                this.stopTabTicker();
            }
        }
    },
    watch: {
        $route: {
            handler(e) {
                this.routeClassDefine(e);
                if (e.name && e.name.startsWith('frontend.checkout')) {
                    this.showCheckoutReminder = false;
                }
            },
            immediate: true
        },
        displayMode() {
            this.displayModeDefine();
        },
        cartList: {
            handler(newList) {
                if (newList && newList.length > 0) {
                    this.startAbandonedCartTimer();
                    this.startTabTicker();
                } else {
                    this.stopAbandonedCartTimer();
                    this.stopTabTicker();
                    this.showCheckoutReminder = false;
                }
            },
            deep: true,
            immediate: true
        }
    },
    mounted() {
        this.startTabTicker();
        const user = this.$store.getters.authInfo;
        if (this.logged && user?.id) {
            identifyAnalyticsUser(user.id);
        }
    },
    beforeUnmount() {
        this.stopAbandonedCartTimer();
        this.stopTabTicker();
        if (this.visibilityListenerAdded) {
            document.removeEventListener('visibilitychange', this.handleVisibilityChange);
        }
    }
}
</script>

<style>
body.overflow-hidden .checkout-reminder-popup {
    display: none !important;
}

body.checkout-active .whatsapp-btn,
body.cart-canvas-open .whatsapp-btn,
body.media-lightbox-open .whatsapp-btn,
body.image-preview-open .whatsapp-btn {
    display: none !important;
    visibility: hidden !important;
    pointer-events: none !important;
}
</style>

<style scoped>
.slide-left-fade-enter-active,
.slide-left-fade-leave-active {
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}
.slide-left-fade-enter-from,
.slide-left-fade-leave-to {
    transform: translateX(20px);
    opacity: 0;
}
</style>
