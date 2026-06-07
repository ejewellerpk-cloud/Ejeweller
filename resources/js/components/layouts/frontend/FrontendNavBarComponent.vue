<template>
    <div v-if="setting.top_bar_status === 'active' && !$route.meta.hideTopBar && topBarTitles.length > 0" 
         id="frontend-top-bar"
         :style="{ backgroundColor: setting.top_bar_bg_color || '#ff5c00', color: setting.top_bar_text_color || '#ffffff' }" 
         class="w-full py-2 z-40 relative min-h-[40px] overflow-hidden">
        <div class="topbar-marquee-viewport w-full overflow-hidden">
            <component
                :is="setting.top_bar_link ? 'a' : 'div'"
                :href="setting.top_bar_link ? setting.top_bar_link : undefined"
                class="topbar-marquee-row flex items-center whitespace-nowrap w-max"
                :class="setting.top_bar_link ? 'hover:opacity-90' : ''"
                :style="{ animationDuration: marqueeDuration + 's' }">
                <template v-for="copy in 2" :key="'marquee-copy-' + copy">
                    <span v-for="(title, index) in topBarTitles"
                        :key="'marquee-title-' + copy + '-' + index"
                        class="inline-flex items-center shrink-0">
                        <span class="px-6 sm:px-10 text-sm font-medium tracking-wide">{{ title }}</span>
                        <span class="opacity-70 text-xs">•</span>
                    </span>
                </template>
            </component>
        </div>
    </div>

    <header
        id="frontend-main-header"
        class="frontend-main-header relative z-40 overflow-visible max-lg:sticky max-lg:top-0 w-full mb-5 sm:mb-8 shadow-xs bg-white"
        :class="isSticky === true ? 'lg:fixed lg:top-0 lg:left-0 lg:right-0' : ''">
        <div class="container py-3 px-3 sm:py-3.5 sm:px-4 lg:py-0 overflow-visible">
            <div class="flex items-center justify-between gap-3 sm:gap-5 overflow-visible">
                <!--  Logo & Mobile Responsive Start -->
                <div class="flex items-center flex-shrink-0 gap-2 sm:gap-5 min-w-0">
                    <button type="button" class="mobile-header-touch leading-none block lg:hidden flex-shrink-0"
                        @touchend="onActionTap($event, openMobileSidebar)"
                        @click="onActionTap($event, openMobileSidebar)">
                        <i class="lab-line-humburger text-xl pointer-events-none"></i>
                    </button>

                    <router-link
                        custom
                        v-slot="{ navigate, href }"
                        :to="{ name: 'frontend.home' }"
                        class="flex-shrink-0 min-w-0"
                    >
                        <a
                            :href="href"
                            class="block"
                            @touchend="onNavTap($event, navigate)"
                            @click="onNavTap($event, navigate)"
                        >
                            <img class="w-24 sm:w-28 md:w-32 max-h-10 sm:max-h-none object-contain pointer-events-none" :src="setting.theme_logo" alt="logo" loading="lazy">
                        </a>
                    </router-link>
                </div>

                <div class="flex items-center gap-1 lg:hidden">
                    <button type="button" class="mobile-header-touch leading-none"
                        @touchend="onActionTap($event, openSearch)"
                        @click="onActionTap($event, openSearch)">
                        <i class="lab-line-search text-xl text-heading pointer-events-none"></i>
                    </button>

                    <router-link
                        custom
                        v-slot="{ navigate, href }"
                        :to="{ name: 'frontend.wishlist' }"
                    >
                        <a
                            :href="href"
                            class="mobile-header-touch relative flex-shrink-0 leading-none"
                            @touchend="onNavTap($event, navigate)"
                            @click="onNavTap($event, navigate)"
                        >
                            <i class="lab-line-heart text-xl text-heading pointer-events-none"></i>
                            <span v-if="wishlists.length > 0"
                                class="absolute -top-2 -right-2 text-[10px] font-bold h-4 min-w-[16px] px-1 flex items-center justify-center rounded-full border border-white text-white bg-primary pointer-events-none">
                                {{ wishlists.length }}
                            </span>
                        </a>
                    </router-link>

                    <button v-if="logged" type="button"
                        class="mobile-header-touch relative flex-shrink-0 leading-none"
                        @touchend="onActionTap($event, openMobileProfile)"
                        @click="onActionTap($event, openMobileProfile)">
                        <img v-if="profile && profile.image" :src="profile.image" alt="avatar" class="w-6 h-6 rounded-full object-cover border border-primary/50 shadow-sm pointer-events-none" loading="lazy" />
                        <i v-else class="lab-line-user text-xl text-heading pointer-events-none"></i>
                    </button>

                    <router-link
                        v-else
                        custom
                        v-slot="{ navigate, href }"
                        :to="{ name: 'auth.login' }"
                    >
                        <a
                            :href="href"
                            class="mobile-header-touch relative flex-shrink-0 leading-none"
                            @touchend="onNavTap($event, navigate)"
                            @click="onNavTap($event, navigate)"
                        >
                            <i class="lab-line-user text-xl text-heading pointer-events-none"></i>
                        </a>
                    </router-link>

                    <button type="button"
                        class="mobile-header-touch relative flex-shrink-0 leading-none"
                        @touchend="onActionTap($event, openMobileCart)"
                        @click="onActionTap($event, openMobileCart)">
                        <i class="lab-line-bag text-xl text-heading pointer-events-none"></i>
                        <span v-if="carts.length > 0"
                            class="absolute -top-2 -right-2 text-[10px] font-bold h-4 min-w-[16px] px-1 flex items-center justify-center rounded-full border border-white text-white bg-primary pointer-events-none">
                            {{ carts.length }}
                        </span>
                    </button>
                </div>
                <!--  Logo & Mobile Responsive End -->

                <!-- MenuBar Start -->
                <nav class="header-nav hidden lg:block">
                    <ul class="header-nav-list">
                        <li class="header-nav-item">
                            <router-link class="header-nav-menu"
                                :class="checkIsPathAndRoutePathSame('/') ? 'router-link-active router-link-exact-active' : ''"
                                :to="{ name: 'frontend.home' }">
                                {{ $t("label.home") }}
                            </router-link>
                        </li>

                        <li class="header-nav-item header-nav-item--mega" @mouseenter="ensureActiveCategoryTab">
                            <button type="button" class="header-nav-menu down-arrow">
                                {{ $t('label.categories') }}
                            </button>
                            <div class="header-nav-mega">
                                <div class="header-nav-mega-panel">
                                    <nav v-if="categories.length > 0" class="header-nav-mega-tabs">
                                        <button
                                            v-for="category in categories"
                                            :key="category.id"
                                            type="button"
                                            class="header-nav-mega-tab"
                                            :class="{ 'is-active': activeTab === 'category_' + category.slug }"
                                            @mouseenter="activeTab = 'category_' + category.slug"
                                            @click="goToCategory(category.slug)">
                                            {{ category.name }}
                                        </button>
                                    </nav>

                                    <div v-for="category in categories" :key="'panel-' + category.id">
                                        <div
                                            v-show="activeTab === 'category_' + category.slug"
                                            class="header-nav-mega-body">
                                            <router-link
                                                v-if="category.cover"
                                                :to="{ name: 'frontend.product', query: { category: category.slug } }"
                                                class="header-nav-mega-image">
                                                <img
                                                    class="w-full h-full object-cover object-top rounded-lg"
                                                    loading="lazy"
                                                    :src="category.cover"
                                                    :alt="category.name" />
                                            </router-link>

                                            <div v-if="category.children.length > 0" class="header-nav-mega-columns">
                                                <div
                                                    v-for="children in category.children"
                                                    :key="children.id"
                                                    class="header-nav-mega-column">
                                                    <h3 class="header-nav-mega-column-title">
                                                        <router-link
                                                            :to="{ name: 'frontend.product', query: { category: children.slug } }"
                                                            class="hover:text-primary transition-all duration-300">
                                                            {{ children.name }}
                                                        </router-link>
                                                    </h3>
                                                    <nav v-if="children.children.length > 0" class="header-nav-mega-links">
                                                        <MenuChildrenComponent :categories="children.children" />
                                                    </nav>
                                                </div>
                                            </div>

                                            <div v-else class="header-nav-mega-empty">
                                                <router-link
                                                    :to="{ name: 'frontend.product', query: { category: category.slug } }"
                                                    class="text-sm font-semibold text-primary hover:underline">
                                                    Browse {{ category.name }}
                                                </router-link>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>

                        <li class="header-nav-item">
                            <router-link class="header-nav-menu"
                                :class="checkIsPathAndRoutePathSame('/offers') ? 'router-link-active router-link-exact-active' : ''"
                                :to="{ name: 'frontend.offers' }">
                                {{ $t("label.offers") }}
                            </router-link>
                        </li>

                        <li class="header-nav-item">
                            <router-link class="header-nav-menu"
                                :class="checkIsPathAndRoutePathSame('/track-order') ? 'router-link-active router-link-exact-active' : ''"
                                :to="{ name: 'frontend.trackOrder' }">
                                {{ $t("label.track_order") }}
                            </router-link>
                        </li>
                    </ul>
                    <div ref="pwaModal" v-if="!isPwaViewed" class="modal active ff-modal">
                        <div class="modal-dialog max-w-[360px] p-6 text-center relative">
                            <button class="modal-close absolute top-4 right-4" @click.prevent="closePwaModal">
                                <i class="fa-regular fa-circle-xmark"></i>
                            </button>
                            <h3 class="text-[18px] font-semibold leading-8 mb-6">
                                {{ $t("label.install_app") }} ?
                            </h3>
                            <div class="flex gap-3 justify-center text-center">
                                <button type="button" class=" modal-close modal-btn-outline "
                                    @click.prevent="closePwaModal">
                                    <i class="lab lab-fill-close-circle"></i>
                                    <span>{{ $t("button.close") }}</span>
                                </button>
                                <button id="installPWA" class="db-btn py-2 text-white bg-primary"
                                    @click.prevent="installPWA">
                                    <i class="lab lab-fill-save"></i><span> {{$t('button.install') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </nav>
                <!-- MenuBar End -->

                <!-- Mobile Search Start -->
                <form @submit.prevent="search"
                    class="hidden w-full lg:w-80 h-10 rounded-3xl lg:flex items-center gap-2 px-4 border border-gray-100 bg-gray-100 transition-all duration-300 focus-within:border-primary focus-within:bg-white">
                    <button class="lab-line-search text-lg flex-shrink-0"></button>
                    <input v-model="searchProduct" class="w-full h-full" type="search"
                        :placeholder="$t('label.search') + '...'" />
                    <button @click="resetSearch" type="button" v-if="searchProduct" class="text-sm text-red-500 fa-regular fa-circle-xmark" ></button>
                </form>
                <!-- Mobile Search Start -->

                <!-- Language Start -->
                <div v-if="setting.site_language_switch === enums.activityEnum.ENABLE"
                    class="relative group hidden lg:block header-account-dropdown">
                    <button type="button" class="flex items-center gap-2 py-5 down-arrow">
                        <img :src="language.image" alt="language" class="w-4 h-4 rounded-full" />
                        <span class="font-semibold capitalize">{{ language.name }}</span>
                    </button>

                    <ul
                        class="header-account-dropdown-menu w-40 absolute top-16 ltr:right-0 rtl:left-0 shadow-paper rounded-lg p-2 bg-white transition-all duration-300 origin-top scale-y-0 group-hover:scale-y-100">
                        <li v-for="(LoopLanguage, index) in languages" :key="index"
                            @click.prevent="changeLanguage(LoopLanguage.id, LoopLanguage.code, LoopLanguage.display_mode)"
                            class="flex items-center gap-3 px-2 py-1.5 rounded-lg relative w-full cursor-pointer transition-all duration-300 hover:bg-slate-100">
                            <img :src="LoopLanguage.image" alt="flags" class="w-4 flex-shrink-0" />
                            <span class="text-sm font-medium capitalize flex-auto">{{ LoopLanguage.name }}</span>
                        </li>
                    </ul>
                </div>
                <!-- Language End -->


                <!-- Wishlist Start -->
                <router-link class="hidden lg:block relative" :to="{ name: 'frontend.wishlist' }">
                    <i class="lab-line-heart text-xl"></i>
                    <span v-if="wishlists.length > 0"
                        class="absolute top-2 ltr:-right-2 rtl:-left-2 text-[10px] font-medium h-4 px-1 !leading-[14px] text-center rounded-full border border-white text-white bg-primary">
                        {{ wishlists.length }}
                    </span>
                </router-link>
                <!-- WishList End -->


                <!-- My Account Start -->
                <div class="relative hidden lg:block group header-account-dropdown">
                    <button type="button" class="lab-line-user text-xl py-5"></button>
                    <div v-if="logged"
                        class="header-account-dropdown-menu w-60 absolute top-15 ltr:-right-10 rtl:-left-10 rounded-2xl overflow-hidden shadow-card bg-white transition-all duration-300 origin-top scale-y-0 group-hover:scale-y-100">
                        <div class="flex items-center gap-3 p-4 border-b border-[#EFF0F6]">
                            <img :src="profile.image" alt="avatar" loading="lazy"
                                class="w-11 h-11 rounded-full object-cover flex-shrink-0">
                            <dl class="w-full">
                                <dt class="font-semibold capitalize whitespace-nowrap mb-0.5">
                                    {{ textShortener(profile.name, 20) }}
                                </dt>
                                <dd class="text-sm font-medium whitespace-nowrap text-text" v-if="profile.phone">
                                    <span dir="ltr">{{ profile.country_code }}{{ profile.phone }}</span>
                                </dd>
                            </dl>
                        </div>
                        <nav class="flex flex-col py-2">
                            <router-link
                                v-if="profile.role_id !== enums.roleEnum.CUSTOMER && Object.keys(authDefaultPermission).length > 0"
                                class="flex items-center gap-3 px-4 py-2 transition-all duration-500 hover:bg-gray-100"
                                :to="{ path: '/admin/' + defaultMenu?.url }">
                                <i class="text-sm text-[#A0A3BD]" :class="defaultMenu?.icon"></i>
                                <span class="text-sm font-medium capitalize whitespace-nowrap">
                                    {{ $t('menu.' + defaultMenu?.language) }}
                                </span>
                            </router-link>

                            <router-link
                                class="flex items-center gap-3 px-4 py-2 transition-all duration-500 hover:bg-gray-100"
                                :to="{ name: 'frontend.account.orderHistory' }">
                                <i class="text-sm text-[#A0A3BD] lab-fill-bag"></i>
                                <span class="text-sm font-medium capitalize whitespace-nowrap">
                                    {{ $t('menu.order_history') }}
                                </span>
                            </router-link>

                            <router-link
                                class="flex items-center gap-3 px-4 py-2 transition-all duration-500 hover:bg-gray-100"
                                :to="{ name: 'frontend.account.returnOrders' }">
                                <i class="text-sm text-[#A0A3BD] lab-fill-refresh"></i>
                                <span class="text-sm font-medium capitalize whitespace-nowrap">
                                    {{ $t('menu.return_orders') }}
                                </span>
                            </router-link>

                            <router-link
                                class="flex items-center gap-3 px-4 py-2 transition-all duration-500 hover:bg-gray-100"
                                :to="{ name: 'frontend.account.accountInfo' }">
                                <i class="text-sm text-[#A0A3BD] lab-fill-user"></i>
                                <span class="text-sm font-medium capitalize whitespace-nowrap">
                                    {{ $t('menu.account_info') }}
                                </span>
                            </router-link>

                            <router-link
                                class="flex items-center gap-3 px-4 py-2 transition-all duration-500 hover:bg-gray-100"
                                :to="{ name: 'frontend.account.changePassword' }">
                                <i class="text-sm text-[#A0A3BD] lab-fill-key"></i>
                                <span class="text-sm font-medium capitalize whitespace-nowrap">
                                    {{ $t('menu.change_password') }}
                                </span>
                            </router-link>

                            <router-link
                                class="flex items-center gap-3 px-4 py-2 transition-all duration-500 hover:bg-gray-100"
                                :to="{ name: 'frontend.account.address' }">
                                <i class="text-sm text-[#A0A3BD] lab-fill-location"></i>
                                <span class="text-sm font-medium capitalize whitespace-nowrap">
                                    {{ $t('menu.address') }}
                                </span>
                            </router-link>

                            <button @click.prevent="logout()"
                                class="flex items-center gap-3 px-4 py-2 transition-all duration-500 hover:bg-gray-100">
                                <i class="text-sm text-[#A0A3BD] lab-fill-logout"></i>
                                <span class="text-sm font-medium capitalize whitespace-nowrap">
                                    {{ $t('button.logout') }}
                                </span>
                            </button>
                        </nav>
                    </div>

                    <div v-else
                        class="header-account-dropdown-menu w-64 absolute top-15 ltr:-right-10 rtl:-left-10 p-4 rounded-2xl overflow-hidden shadow-card bg-white transition-all duration-300 origin-top scale-y-0 group-hover:scale-y-100">
                        <router-link
                            class="!text-primary !bg-[#FFF4F1] w-full text-center h-12 leading-12 font-semibold tracking-wide rounded-full whitespace-nowrap"
                            :to="{ name: 'auth.signup' }">
                            {{ $t('button.register_your_account') }}
                        </router-link>
                        <span class="block font-medium uppercase text-center py-3">{{ $t('label.or') }}</span>
                        <router-link
                            class="w-full text-center h-12 leading-12 font-semibold tracking-wide rounded-full whitespace-nowrap text-white bg-primary"
                            :to="{ name: 'auth.login' }">
                            {{ $t('button.login_to_your_account') }}
                        </router-link>
                    </div>
                </div>
                <!-- My Account End -->

                <!-- Card Button Start -->
                <button @click.prevent="openCanvas('cart-canvas')" type="button"
                    class="hidden lg:block flex-shrink-0 relative">
                    <i
                        class="lab-line-bag text-xl w-10 h-10 !leading-10 text-center rounded-full bg-secondary text-white"></i>
                    <span v-if="carts.length > 0"
                        class="absolute top-4 ltr:right-1 rtl:left-1 text-[10px] font-medium h-4 px-1 leading-[14px] text-center rounded-full border border-heading text-white bg-primary">
                        {{ carts.length }}
                    </span>
                </button>
                <!-- Card Button End -->
            </div>
        </div>
    </header>

    <!-- Mobile Search Start -->
    <form @submit.prevent="search" id="search"
        class="w-full  lg:w-auto fixed inset-0 z-50 py-5 px-4 bg-white transition-all duration-500 origin-top scale-y-0">
        <div class="flex items-center justify-between mb-4">
            <router-link :to="{ name: 'frontend.home' }"
                class="router-link-active router-link-exact-active flex-shrink-0">
                <img class="w-28 sm:w-32" :src="setting.theme_logo" alt="logo" loading="lazy">
            </router-link>
            <button type="button">
                <i @click.prevent="hideTarget('search', 'search-active')"
                    class="lab-line-circle-cross text-xl text-danger"></i>
            </button>
        </div>
        <div
            class="w-full h-10 rounded-3xl flex items-center gap-2 px-4 mb-4 border border-gray-100 bg-gray-100 transition-all duration-300 focus-within:border-primary focus-within:bg-white">
            <button class="lab-line-search text-lg flex-shrink-0"></button>
            <input id="searchSomething" v-model="searchProduct" @keyup="searchElement" class="w-full h-full"
                type="search" :placeholder="$t('label.search') + '...'">
        </div>
        <div class="lg:hidden h-[calc(100vh_-_140px)] rounded-xl overflow-y-auto p-4 bg-gray-100">
            <ul v-if="searchProductLists.length > 0" id="searchProductLists">
                <li :key="searchProductList.name"
                    class="py-1 hover:px-2 whitespace-nowrap overflow-hidden text-ellipsis rounded-lg transition-all duration-300 hover:bg-white hover:text-primary"
                    @click.prevent="goSearchProduct(searchProductList.slug)"
                    v-for="searchProductList in searchProductLists">{{ searchProductList.name }}</li>
            </ul>
        </div>
    </form>
    <!-- Mobile Search End -->

    <!-- Notification Start -->
    <div id="order-modal" v-if="orderNotificationStatus" ref="orderNotificationModal" class="modal active ff-modal">
        <div class="modal-dialog max-w-[360px] p-6 text-center relative">
            <button @click.prevent="closeOrderNotificationModal('order-modal', 'modal-active')"
                class="modal-close absolute top-4 right-4">
                <i class="fa-regular fa-circle-xmark"></i>
            </button>
            <h3 class="text-[18px] font-semibold leading-8 mb-6">
                {{ orderNotificationMessage }}
                <span class="block">{{ $t('message.please_check_your_order_list') }}</span>
            </h3>
            <router-link :to="{ path: '/admin/' + orderNotification.url }"
                class="db-btn h-[38px] shadow-[0px_8px_15px_rgba(253,_139,_14,_0.18)] bg-primary text-white">
                {{ $t('button.let_me_check') }}
            </router-link>
        </div>
    </div>
    <!-- Notification End -->

</template>

<script>
import statusEnum from "../../../enums/modules/statusEnum";
import { onMounted, onBeforeUnmount, ref } from "vue";
import targetService from "../../../services/targetService";
import appService from "../../../services/appService";
import activityEnum from "../../../enums/modules/activityEnum";
import roleEnum from "../../../enums/modules/roleEnum";
import MenuChildrenComponent from "../../frontend/components/MenuChildrenComponent";
import orderTypeEnum from "../../../enums/modules/orderTypeEnum";
import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";
import _ from "lodash";
import axios from 'axios';
import {useCanvas} from "../../../composables/canvas";
import { onInstantAction, onInstantNavigate } from "../../../utils/instantTap";


export default {
    name: "FrontendNavbarComponent",
    components: { MenuChildrenComponent },
    setup() {
        const isSticky = ref();
        const {openCanvas} = useCanvas();

        const syncLayoutOffsets = () => {
            const header = document.getElementById('frontend-main-header');
            if (!header) {
                return;
            }
            const height = Math.ceil(header.getBoundingClientRect().height);
            document.documentElement.style.setProperty('--frontend-header-height', `${height}px`);
            document.documentElement.style.setProperty('--shop-sticky-top', `${height}px`);
        };

        let headerResizeObserver = null;

        onMounted(() => {
            window.addEventListener('scroll', function () {
                let windowScroll = this.scrollY;
                if (windowScroll > 0) {
                    isSticky.value = true;
                } else {
                    isSticky.value = false;
                }
            });

            syncLayoutOffsets();
            window.addEventListener('resize', syncLayoutOffsets);
            window.addEventListener('orientationchange', syncLayoutOffsets);

            const header = document.getElementById('frontend-main-header');
            if (header && typeof ResizeObserver !== 'undefined') {
                headerResizeObserver = new ResizeObserver(syncLayoutOffsets);
                headerResizeObserver.observe(header);
            }
        });

        onBeforeUnmount(() => {
            window.removeEventListener('resize', syncLayoutOffsets);
            window.removeEventListener('orientationchange', syncLayoutOffsets);
            headerResizeObserver?.disconnect();
        });

        return {
            isSticky,
            openCanvas
        }
    },
    data() {
        return {
            loading: {
                isActive: false,
            },
            searchProductLists: [],
            currentRoute: "",
            defaultLanguage: null,
            enums: {
                activityEnum: activityEnum,
                roleEnum: roleEnum
            },
            languageProps: {
                paginate: 0,
                order_column: "id",
                order_type: "asc",
                status: statusEnum.ACTIVE
            },
            categoryTabStatus: false,
            activeTab: null,
            searchProduct: "",
            orderNotificationStatus: false,
            orderNotificationMessage: "",
            orderNotification: {
                permission: false,
                url: ""
            },
        }
    },
    computed: {
        logged: function () {
            return this.$store.getters.authStatus;
        },
        authDefaultPermission: function () {
            return this.$store.getters.authDefaultPermission;
        },
        profile: function () {
            return this.$store.getters.authInfo;
        },
        setting: function () {
            return this.$store.getters['frontendSetting/lists'];
        },
        language: function () {
            return this.$store.getters['frontendLanguage/show'];
        },
        languages: function () {
            return this.$store.getters['frontendLanguage/lists'];
        },
        categories: function () {
            return this.$store.getters['frontendProductCategory/trees'];
        },
        wishlists: function () {
            return this.$store.getters['frontendWishlist/lists'];
        },
        carts: function () {
            return this.$store.getters['frontendCart/lists'];
        },
        isPwaViewed: function () {
            return localStorage.getItem('pwa_viewed') ? true : false;
        },
        defaultMenu: function () {
            return this.$store.getters.authDefaultMenu;
        },
        topBarTitles: function () {
            if (this.setting && this.setting.top_bar_text) {
                try {
                    const parsed = JSON.parse(this.setting.top_bar_text);
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        return parsed;
                    }
                    return [this.setting.top_bar_text];
                } catch (e) {
                    return [this.setting.top_bar_text];
                }
            }
            return [];
        },
        marqueeDuration: function () {
            const totalLength = this.topBarTitles.reduce((sum, title) => sum + String(title).length, 0);
            const perTitlePadding = this.topBarTitles.length * 8;
            return Math.max(10, Math.min(40, Math.round((totalLength + perTitlePadding) * 0.5)));
        },
    },
    mounted() {
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.pwaInstallPrompt = e;
        });
        this.currentRoute = this.$route.path;
        this.loading.isActive = true;
        this.orderPermissionCheck();
        this.$store.dispatch('frontendSetting/lists').then(res => {
            this.defaultLanguage = res.data.data.site_default_language;
            const globalState = this.$store.getters['globalState/lists'];
            if (globalState.language_id > 0) {
                this.defaultLanguage = globalState.language_id;
            }

            this.loading.isActive = false;
            this.$store.dispatch('frontendLanguage/lists', this.languageProps).then().catch();
            this.$store.dispatch('frontendLanguage/show', this.defaultLanguage).then(res => {
                this.$i18n.locale = res.data.data.code;
                this.$store.dispatch("globalState/init", {
                    language_code: res.data.data.code,
                    display_mode: res.data.data.display_mode
                });
            }).catch();

            window.setTimeout(() => {
                this.$store.dispatch('frontendCart/initOrderType', { order_type: orderTypeEnum.DELIVERY });

                if (this.$store.getters.authStatus && res.data.data.notification_fcm_api_key && res.data.data.notification_fcm_auth_domain && res.data.data.notification_fcm_project_id && res.data.data.notification_fcm_storage_bucket && res.data.data.notification_fcm_messaging_sender_id && res.data.data.notification_fcm_app_id && res.data.data.notification_fcm_measurement_id) {
                    initializeApp({
                        apiKey: res.data.data.notification_fcm_api_key,
                        authDomain: res.data.data.notification_fcm_auth_domain,
                        projectId: res.data.data.notification_fcm_project_id,
                        storageBucket: res.data.data.notification_fcm_storage_bucket,
                        messagingSenderId: res.data.data.notification_fcm_messaging_sender_id,
                        appId: res.data.data.notification_fcm_app_id,
                        measurementId: res.data.data.notification_fcm_measurement_id
                    });
                    const messaging = getMessaging();

                    Notification.requestPermission().then((permission) => {
                        if (permission === 'granted') {
                            getToken(messaging, { vapidKey: res.data.data.notification_fcm_public_vapid_key }).then((currentToken) => {
                                if (currentToken) {
                                    localStorage.setItem('fcm_web_token', currentToken);
                                    axios.post('/frontend/device-token/web', {
                                        token: currentToken,
                                        platform: 'web',
                                        device_id: appService.fcmDeviceId(),
                                        device_name: appService.fcmDeviceName(),
                                    }).then().catch((error) => {
                                        if (error.response.data.message === 'Unauthenticated.') {
                                            this.$store.dispatch('loginDataReset');
                                        }
                                    });
                                }
                            }).catch();
                        }
                    });

                    onMessage(messaging, (payload) => {
                        const notificationTitle = payload.notification.title;
                        const notificationOptions = {
                            body: payload.notification.body,
                            icon: '/images/required/firebase-logo.png'
                        };
                        new Notification(notificationTitle, notificationOptions);

                        if (payload.data.topicName === 'new-order-found' && this.orderNotification.permission) {
                            this.orderNotificationStatus = true;
                            this.orderNotificationMessage = payload.notification.body;
                            const audio = new Audio(res.data.data.notification_audio);
                            audio.play();
                        }
                    });
                }
            }, 3000);

            this.loading.isActive = false;
        }).catch((err) => {
            this.loading.isActive = false;
        });

        this.loading.isActive = true;
        this.$store.dispatch('frontendProductCategory/trees').then(res => {
            this.initCategoryMegaTab();
            this.loading.isActive = false;
        }).catch((err) => {
            this.loading.isActive = false;
        });

        if (this.logged) {
            this.loading.isActive = true;
            this.$store.dispatch("frontendWishlist/lists").then((res) => {
                this.loading.isActive = false;
            }).catch((err) => {
                this.loading.isActive = false;
                if (err.response && err.response.status === 401) {
                    this.$store.dispatch("loginDataReset");
                }
            });
        }
    },
    methods: {
        onNavTap(event, navigate) {
            onInstantNavigate(event, navigate);
        },
        onActionTap(event, action) {
            onInstantAction(event, action);
        },
        openMobileSidebar() {
            targetService.showTarget('mobile-sidebar-canvas', 'canvas-active');
        },
        openSearch() {
            targetService.showTarget('search', 'search-active');
        },
        openMobileProfile() {
            targetService.showTarget('mobile-profile-canvas', 'canvas-active');
        },
        openMobileCart() {
            this.openCanvas('cart-canvas');
        },
        async installPWA() {
            if (!this.pwaInstallPrompt) {
                this.closePwaModal();
                return;
            }

            await this.pwaInstallPrompt.prompt();
            this.pwaInstallPrompt = null;
            this.closePwaModal();
        },
        closePwaModal: function () {
            const modalTarget = this.$refs.pwaModal;
            modalTarget?.classList?.remove("active");
            document.body.style.overflowY = "auto";
            localStorage.setItem('pwa_viewed', true);
        },
        showTarget: function (id, cClass) {
            targetService.showTarget(id, cClass);
        },
        hideTarget: function (id, cClass) {
            targetService.hideTarget(id, cClass);
        },
        textShortener: function (text, number = 30) {
            return appService.textShortener(text, number);
        },
        checkIsPathAndRoutePathSame(path) {
            if (path === '/' && (this.currentRoute === '/' || this.currentRoute === '/home')) {
                return true;
            }
            return this.currentRoute === path;
        },
        changeLanguage: function (id, code, mode) {
            this.defaultLanguage = id;
            this.$store.dispatch("globalState/set", {
                language_id: id,
                language_code: code,
                display_mode: mode
            }).then(res => {
                this.$store.dispatch('frontendLanguage/show', id).then(res => {
                    this.$i18n.locale = res.data.data.code;
                }).catch();
            }).catch();
        },
        logout: function () {
            this.$store.dispatch("logout").then(res => {
                this.$store.dispatch("frontendWishlist/reset");
                this.$router.push({ name: "frontend.home" });
            }).catch();
        },
        search: function () {
            if (typeof this.searchProduct !== "undefined" && this.searchProduct !== "") {
                this.$router.push({ name: "frontend.product", query: { name: this.searchProduct } });
                this.searchProduct = "";
                this.hideTarget('search', 'search-active')
            }
        },
        orderPermissionCheck: function () {
            const permissions = this.$store.getters.authPermission;
            if (permissions.length > 0) {
                _.forEach(permissions, (permission) => {
                    if (permission.name === 'online-orders') {
                        if (permission.access === true) {
                            this.orderNotification.permission = true;
                            this.orderNotification.url = permission.url;
                        }
                    }
                });
            }
        },
        closeOrderNotificationModal: function (id, cClass) {
            targetService.hideTarget(id, cClass);
            this.orderNotificationStatus = false;
        },
        searchElement: function () {
            if (this.searchProduct && this.searchProduct.length > 2) {
                let url = `frontend/product`;
                url = url + appService.requestHandler({ name: this.searchProduct });
                axios.get(url).then((res) => {
                    this.searchProductLists = res.data.data;
                }).catch();
            } else {
                this.searchProductLists = [];
            }
        },
        goSearchProduct: function (slug) {
            targetService.hideTarget('search', 'search-active');
            this.$router.push({ name: 'frontend.product.details', params: { slug: slug } })
        },
        resetSearch: function(){
            this.searchProduct = "";
        },
        initCategoryMegaTab: function () {
            const categories = this.categories;
            if (categories && categories.length > 0) {
                this.activeTab = 'category_' + categories[0].slug;
            }
        },
        ensureActiveCategoryTab: function () {
            if (!this.activeTab) {
                this.initCategoryMegaTab();
            }
        },
        goToCategory: function (slug) {
            this.$router.push({ name: 'frontend.product', query: { category: slug } });
        }
    },
    watch: {
        $route(to, from) {
            this.currentRoute = to.path;
        },
    }
}
</script>

<style scoped>
.topbar-marquee-viewport {
    display: flex;
    align-items: center;
    min-height: 24px;
}

.topbar-marquee-row {
    animation-name: topbar-marquee-rtl;
    animation-timing-function: linear;
    animation-iteration-count: infinite;
    will-change: transform;
}

@keyframes topbar-marquee-rtl {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}

.mobile-header-touch {
    min-width: 2.75rem;
    min-height: 2.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    -webkit-user-select: none;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    transition: transform 0.1s ease, opacity 0.1s ease;
}

.mobile-header-touch:active {
    transform: scale(0.9);
    opacity: 0.75;
}
</style>