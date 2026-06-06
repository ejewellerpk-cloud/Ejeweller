<template>
    <nav class="mobile-bottom-nav lg:hidden w-full flex items-end justify-between px-2 sm:px-3 pt-1.5 pb-2 fixed bottom-0 left-0 z-50 shadow-widget bg-white border-t border-gray-100/80">
        <router-link class="mobile-bottom-nav__item flex flex-col items-center justify-center gap-0.5 text-text transition-all duration-200 hover:text-primary" :class="checkIsPathAndRoutePathSame('/') ? 'router-link-active router-link-exact-active !text-primary' : ''" :to="{name : 'frontend.home'}">
            <i class="lab-line-home text-lg leading-none"></i>
            <span class="mobile-bottom-nav__label text-xs font-medium capitalize">{{ $t('label.home') }}</span>
        </router-link>

        <router-link class="mobile-bottom-nav__item flex flex-col items-center justify-center gap-0.5 text-text transition-all duration-200 hover:text-primary" :class="checkIsPathAndRoutePathSame('/categories') ? 'router-link-active router-link-exact-active !text-primary' : ''" :to="{name : 'frontend.categories'}">
            <i class="lab-line-category text-lg leading-none"></i>
            <span class="mobile-bottom-nav__label text-xs font-medium capitalize">{{ $t('label.categories') }}</span>
        </router-link>

        <button @click="showTarget('cart-canvas', 'canvas-active')" type="button" class="mobile-bottom-nav__cart relative isolate -mt-8 sm:-mt-11 flex-shrink-0">
            <i class="lab-line-bag text-lg w-11 h-11 sm:w-12 sm:h-12 !leading-[2.75rem] sm:!leading-[3rem] text-center rounded-full shadow-cart bg-primary text-white"></i>
            <span v-if="carts.length > 0" class="absolute top-4 sm:top-5 ltr:right-0 rtl:left-0 text-[10px] font-medium h-4 min-w-[16px] px-1 leading-[14px] text-center rounded-full border border-primary bg-[#FFBC1F] pointer-events-none">
                {{ carts.length }}
            </span>
        </button>

        <router-link class="mobile-bottom-nav__item flex flex-col items-center justify-center gap-0.5 text-text transition-all duration-200 hover:text-primary" :class="checkIsPathAndRoutePathSame('/product') ? 'router-link-active router-link-exact-active !text-primary' : ''" :to="{ name: 'frontend.product' }">
            <i class="lab-fill-shop text-lg leading-none"></i>
            <span class="mobile-bottom-nav__label text-xs font-medium capitalize">Shop</span>
        </router-link>

        <button v-if="logged" @click.prevent="showTarget('mobile-profile-canvas', 'canvas-active')" type="button" class="mobile-bottom-nav__item flex flex-col items-center justify-center gap-0.5 text-text transition-all duration-200 hover:text-primary">
            <i class="lab-line-user text-lg leading-none"></i>
            <span class="mobile-bottom-nav__label text-xs font-medium capitalize">{{ $t('menu.profile') }}</span>
        </button>

        <router-link v-else class="mobile-bottom-nav__item flex flex-col items-center justify-center gap-0.5 text-text transition-all duration-200 hover:text-primary" :class="checkIsPathAndRoutePathSame('/login') ? 'router-link-active router-link-exact-active !text-primary' : ''" :to="{ name: 'auth.login' }">
            <i class="lab-line-user text-lg leading-none"></i>
            <span class="mobile-bottom-nav__label text-xs font-medium capitalize">{{ $t('menu.login') }}</span>
        </router-link>

        <div v-if="!isPwaViewed" ref="pwaStickyFooter"
            class="lg:hidden border-none bg-white p-4 fixed bottom-0 left-0 w-full z-[60] rounded-tl-3xl rounded-tr-3xl shadow-paper"
            style="padding-bottom: max(1rem, env(safe-area-inset-bottom));">
            <div class="flex items-start gap-3 mb-3">
                <img :src="setting.theme_favicon_logo" alt="theme-favicon-logo"
                    class="w-8 h-8 rounded-lg flex-shrink-0 shadow-xl">
                <h3 class="text-sm flex-auto text-[#008BBA]">
                    {{ $t('message.add') }}
                    {{ setting.company_name }}
                    {{ $t('message.app_to_your_home_screen') }} ?
                </h3>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button @click.prevent="closePwaModal"
                    class="mobile-bottom-nav__pwa-btn py-2 px-3 rounded-md capitalize text-sm border border-gray-200 text-primary">{{ $t('button.cancel') }}</button>
                <button @click.prevent="installPWA" id="installPWAsm"
                    class="mobile-bottom-nav__pwa-btn py-2 px-3 rounded-md capitalize text-sm bg-primary text-white">{{ $t('button.install') }}</button>
            </div>
        </div>

    </nav>
</template>
<script>
import targetService from "../../../services/targetService";

export default {
    name: "FrontendMobileNavBarComponent",
    data() {
        return {
            loading: {
                isActive: false,
            },
            currentRoute: "",
        }
    },
    computed: {
        logged: function () {
            return this.$store.getters.authStatus;
        },
        carts: function () {
            return this.$store.getters['frontendCart/lists'];
        },
        isPwaViewed: function () {
            return localStorage.getItem('pwa_viewed') ? true : false;
        },
        setting: function () {
            return this.$store.getters['frontendSetting/lists'];
        },
    },
    mounted() {
        this.currentRoute = this.$route.path;
        this.syncNavHeight();

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.pwaInstallPrompt = e;
        });

        window.addEventListener('appinstalled', () => {
            this.pwaInstallPrompt = null;
            this.closePwaModal();
        });

        window.addEventListener('resize', this.syncNavHeight);
        window.addEventListener('orientationchange', this.syncNavHeight);
    },
    beforeUnmount() {
        window.removeEventListener('resize', this.syncNavHeight);
        window.removeEventListener('orientationchange', this.syncNavHeight);
    },
    methods: {
        syncNavHeight() {
            this.$nextTick(() => {
                const nav = this.$el;
                if (!nav || !(nav instanceof HTMLElement)) {
                    return;
                }
                const height = Math.ceil(nav.getBoundingClientRect().height);
                document.documentElement.style.setProperty('--mobile-bottom-nav-height', `${height}px`);
            });
        },
        checkIsPathAndRoutePathSame(path) {
            if (path === '/' && (this.currentRoute === '/' || this.currentRoute === '/home')) {
                return true;
            }
            return this.currentRoute === path;
        },
        showTarget: function (id, cClass) {
            targetService.showTarget(id, cClass);
        },
        async installPWA() {
            if (!this.pwaInstallPrompt) {
                this.closePwaModal();
                return;
            }
            try {
                const result = await this.pwaInstallPrompt.prompt();
                this.pwaInstallPrompt = null;
                this.closePwaModal();
            } catch (error) {
                this.closePwaModal();
            }
        },
        closePwaModal: function () {
            const modalTarget = this.$refs.pwaStickyFooter;
            modalTarget?.classList?.add("hidden");
            localStorage.setItem('pwa_viewed', true);
        },
    },
    watch: {
        $route(to, from) {
            this.currentRoute = to.path;
            this.syncNavHeight();
        },
    }
}
</script>

<style scoped>
.mobile-bottom-nav {
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    padding-bottom: max(0.5rem, env(safe-area-inset-bottom));
    padding-left: max(0.5rem, env(safe-area-inset-left));
    padding-right: max(0.5rem, env(safe-area-inset-right));
    min-height: calc(3.75rem + env(safe-area-inset-bottom, 0px));
}

.mobile-bottom-nav__item {
    flex: 1 1 0;
    min-width: 0;
    max-width: 4.5rem;
    min-height: 3rem;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
}

.mobile-bottom-nav__label {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.1;
}

.mobile-bottom-nav__item:active,
.mobile-bottom-nav__cart:active,
.mobile-bottom-nav__pwa-btn:active {
    transform: scale(0.92);
    opacity: 0.82;
    transition: transform 0.12s ease, opacity 0.12s ease;
}

.mobile-bottom-nav__cart {
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    min-width: 2.75rem;
    min-height: 2.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.mobile-bottom-nav__pwa-btn {
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    min-height: 2.75rem;
    min-width: 4.5rem;
}

@media (max-width: 390px) {
    .mobile-bottom-nav__label {
        font-size: 10px;
    }

    .mobile-bottom-nav__item {
        min-height: 2.75rem;
    }
}
</style>
