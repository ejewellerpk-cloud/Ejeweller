<template>
    <div v-if="products.length > 0" class="product-list-root contents">
        <article
            v-for="product in products"
            :key="product.id"
            :data-card-product-id="product.id"
            class="product-card group p-1 sm:p-1.5 bg-white rounded-2xl border border-gray-200/80 shadow-[0_4px_16px_rgba(0,0,0,0.05)] duration-300 transition-all ease-out cursor-pointer relative isolate z-0 block"
            @touchstart.passive="onCardTouchStart(product.id, $event)"
            @touchend="onCardActivate(product, $event)"
            @click="onCardActivate(product, $event)"
            @mouseenter="onDesktopHoverEnter(product.id)"
            @mouseleave="onDesktopHoverLeave(product.id)"
        >
            <div class="relative overflow-hidden rounded-xl isolate">
                <div v-if="animatingWishlists[product.id]" class="absolute inset-0 flex items-center justify-center bg-black/10 z-30 pointer-events-none rounded-xl animate-fade-overlay">
                    <div class="w-16 h-16 rounded-full bg-white/95 flex items-center justify-center shadow-2xl animate-heart-burst">
                        <i class="lab-fill-heart text-primary text-3xl animate-heart-pulse"></i>
                    </div>
                </div>

                <div class="absolute top-2 left-2 z-30 flex flex-col gap-1 items-start pointer-events-none max-w-[calc(100%-3rem)]">
                    <span v-for="badge in getProductBadges(product)" :key="badge.key"
                        class="product-card-badge inline-flex items-center gap-0.5 font-extrabold rounded-full pointer-events-auto max-w-full truncate"
                        :class="badgeClass(badge.type)">
                        <i v-if="badge.icon" :class="badge.icon" class="text-[8px] shrink-0"></i>
                        <span class="truncate">{{ badge.label }}</span>
                    </span>
                </div>

                <button type="button" @click.stop="wishlist(product)"
                    :class="isWishlisted(product) ? 'lab-fill-heart text-primary animate-heart-pulse shadow-[0_4px_12px_rgba(255,92,0,0.45)]' : 'lab-line-heart text-secondary hover:text-primary hover:shadow-[0_4px_10px_rgba(0,0,0,0.1)]'"
                    class="w-8 h-8 leading-8 rounded-full text-center text-lg shadow-badge absolute top-3 right-3 z-10 bg-white hover:scale-110 active:scale-95 transition-all duration-300 flex items-center justify-center">
                </button>

                <div class="overflow-hidden rounded-xl w-full block relative aspect-[4/5] product-card-slider">
                    <Swiper v-if="hasMediaSlider(product)"
                        v-bind="cardSwiperProps"
                        :dir="'ltr'"
                        :pagination="getPaginationConfig(product)"
                        :modules="modules"
                        :loop="!(product.videos && product.videos.length > 0)"
                        class="w-full h-full product-card-swiper"
                        @swiper="onSwiperReady($event, product.id)"
                        @slideChange="onCardSlideChange($event, product.id)"
                        @slideChangeTransitionEnd="onCardSlideChange($event, product.id)"
                        @sliderFirstMove="onSliderDragStart(product.id)"
                        @touchEnd="onSliderTouchEnd(product.id)">

                        <SwiperSlide v-if="product.previews.length > 0">
                            <div class="w-full h-full relative bg-gray-50">
                                <div class="absolute inset-0 flex items-center justify-center" v-if="!loadedImages[product.id + '-img-0']">
                                    <div class="flex gap-1.5">
                                        <div class="w-2 h-2 bg-primary/40 rounded-full animate-bounce"></div>
                                        <div class="w-2 h-2 bg-primary/40 rounded-full animate-bounce" style="animation-delay: 0.15s"></div>
                                        <div class="w-2 h-2 bg-primary/40 rounded-full animate-bounce" style="animation-delay: 0.3s"></div>
                                    </div>
                                </div>
                                <img :src="product.previews[0]" alt="product" loading="lazy"
                                    @load="onImageLoad(product.id + '-img-0')"
                                    @error="onImageError($event, product.id + '-img-0')"
                                    :class="loadedImages[product.id + '-img-0'] ? 'opacity-100' : 'opacity-0'"
                                    class="product-card__image w-full h-full object-cover transition-all duration-700 relative z-10 pointer-events-none">
                            </div>
                        </SwiperSlide>

                        <SwiperSlide v-if="product.videos && product.videos.length > 0">
                            <div class="w-full h-full relative">
                                <div class="w-full h-full bg-black relative aspect-[4/5]">
                                    <iframe v-if="isEmbedVideo(product.videos[0])"
                                        :key="`card-embed-${product.id}`"
                                        :src="isCardVideoActive(product.id) ? formatProductCardVideoLink(product.videos[0]) : ''"
                                        class="w-full h-full pointer-events-none"
                                        frameborder="0"
                                        allow="autoplay; encrypted-media; picture-in-picture"
                                        loading="lazy">
                                    </iframe>
                                    <video v-else
                                        :key="`card-video-${product.id}`"
                                        data-card-video
                                        :src="product.videos[0].link"
                                        :poster="getVideoPosterForCard(product.videos[0], product)"
                                        :autoplay="isCardVideoActive(product.id)"
                                        muted
                                        loop
                                        playsinline
                                        webkit-playsinline
                                        preload="auto"
                                        class="w-full h-full object-cover pointer-events-none"
                                        @canplay="onCardVideoCanPlay(product.id, $event)">
                                    </video>
                                </div>
                            </div>
                        </SwiperSlide>

                        <SwiperSlide v-for="(image, index) in product.previews.slice(1)" :key="index">
                            <div class="w-full h-full relative bg-gray-50">
                                <div class="absolute inset-0 flex items-center justify-center" v-if="!loadedImages[product.id + '-img-' + (index + 1)]">
                                    <div class="flex gap-1.5">
                                        <div class="w-2 h-2 bg-primary/40 rounded-full animate-bounce"></div>
                                        <div class="w-2 h-2 bg-primary/40 rounded-full animate-bounce" style="animation-delay: 0.15s"></div>
                                        <div class="w-2 h-2 bg-primary/40 rounded-full animate-bounce" style="animation-delay: 0.3s"></div>
                                    </div>
                                </div>
                                <img :src="image" alt="product" loading="lazy"
                                    @load="onImageLoad(product.id + '-img-' + (index + 1))"
                                    @error="onImageError($event, product.id + '-img-' + (index + 1))"
                                    :class="loadedImages[product.id + '-img-' + (index + 1)] ? 'opacity-100' : 'opacity-0'"
                                    class="product-card__image w-full h-full object-cover transition-all duration-700 relative z-10 pointer-events-none">
                            </div>
                        </SwiperSlide>
                    </Swiper>

                    <div v-else class="w-full h-full block relative bg-gray-50">
                        <template v-if="product.cover && !product.cover.includes('default/product')">
                            <div class="absolute inset-0 flex items-center justify-center" v-if="!loadedImages[product.id + '-cover']">
                                <div class="flex gap-1.5">
                                    <div class="w-2 h-2 bg-primary/40 rounded-full animate-bounce"></div>
                                    <div class="w-2 h-2 bg-primary/40 rounded-full animate-bounce" style="animation-delay: 0.15s"></div>
                                    <div class="w-2 h-2 bg-primary/40 rounded-full animate-bounce" style="animation-delay: 0.3s"></div>
                                </div>
                            </div>
                            <img :src="product.cover" alt="product" loading="lazy"
                                @load="onImageLoad(product.id + '-cover')"
                                @error="onImageError($event, product.id + '-cover')"
                                :class="loadedImages[product.id + '-cover'] ? 'opacity-100' : 'opacity-0'"
                                class="product-card__image w-full h-full object-cover transition-all duration-700 relative z-10 pointer-events-none">
                        </template>
                        <div v-else class="w-full h-full flex items-center justify-center bg-gray-50/50 absolute inset-0 z-10">
                            <img :src="$store.getters['frontendSetting/lists'].theme_logo" alt="logo" loading="lazy"
                                class="w-3/4 h-3/4 object-contain opacity-40 transition-all duration-700 pointer-events-none">
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-1.5 sm:px-2 pt-2 overflow-hidden text-ellipsis product-card__body">
            <div class="product-card__actions flex items-center justify-between relative z-0">
                <div class="flex flex-col min-w-0">
                    <div class="flex flex-wrap items-baseline gap-1" v-if="hasActiveDiscount(product)">
                        <span class="text-lg sm:text-xl font-black text-primary leading-none">
                            {{ product.discounted_price }}
                        </span>
                        <span class="text-xs sm:text-sm font-semibold text-shopperz-red line-through leading-none">
                            {{ product.currency_price }}
                        </span>
                        <span v-if="discountPercentage(product) > 0"
                            class="text-[10px] sm:text-xs font-bold text-shopperz-red leading-none">
                            {{ discountPercentage(product) }}% OFF
                        </span>
                    </div>
                    <span class="text-lg sm:text-xl font-black text-primary leading-none" v-else>
                        {{ product.currency_price }}
                    </span>
                </div>

                <button v-if="!isOutOfStock(product)" type="button" @click.stop="addToCart(product)"
                    :title="product.variation_count > 0 ? ($t('label.choose_options') || 'Choose options') : ($t('button.add_to_cart') || 'Add to Cart')"
                    :class="animatingCartIds[product.id] ? 'animate-cart-bounce' : ''"
                    class="product-card__cart-btn w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#ff5c00] text-white flex items-center justify-center shadow-[0_3px_8px_rgba(255,92,0,0.15)] hover:scale-105 active:scale-95 transition-all duration-300 shrink-0">
                    <i class="fa-solid fa-cart-plus text-white text-sm sm:text-base"></i>
                </button>
                <span v-else-if="isOutOfStock(product)"
                    class="inline-flex items-center justify-center min-w-[4.5rem] sm:min-w-[5rem] h-9 sm:h-10 px-2 rounded-xl bg-gray-100 text-gray-600 text-[10px] sm:text-xs font-bold uppercase tracking-wide shrink-0 pointer-events-none">
                    {{ $t('label.sold_out') || 'Sold Out' }}
                </span>
            </div>

            <h3 class="product-card__title capitalize text-xs sm:text-sm font-semibold transition-all duration-300 mt-1.5 mb-1 text-gray-800 line-clamp-2 leading-snug">
                {{ product.name }}
            </h3>

            <div v-if="product.stock > 0 && product.stock <= 5" class="mt-1 mb-1">
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-bold text-red-600 bg-red-50 border border-red-100/50 animate-pulse w-full">
                    <i class="fa-solid fa-fire text-red-500"></i>
                    Only {{ product.stock }} Left in Stock!
                </span>
            </div>

            <div class="flex items-center gap-1.5 mt-1 text-[11px] text-gray-500 font-medium flex-nowrap overflow-hidden">
                <div class="flex items-center gap-1 shrink-0 min-w-0">
                    <div class="flex items-center gap-0.5 shrink-0" :aria-label="formatListProductRating(product) + ' out of 5'">
                        <i v-for="star in 5" :key="star"
                            :class="star <= getStarFillCount(product) ? 'fa-solid text-primary' : 'fa-regular text-gray-300'"
                            class="fa-star text-[9px] sm:text-[10px]"></i>
                    </div>
                    <span class="text-gray-900 font-bold whitespace-nowrap">{{ formatListProductRating(product) }}</span>
                    <span class="whitespace-nowrap">({{ getListReviewCount(product) }})</span>
                </div>
                <template v-if="getProductSoldCount(product) > 0">
                    <span class="text-gray-200 shrink-0">|</span>
                    <span class="whitespace-nowrap shrink-0">
                        <span class="text-gray-900 font-bold">{{ getProductSoldCount(product) }}</span> sold
                    </span>
                </template>
            </div>
            </div>
        </article>
    </div>
</template>

<script>
import starRating from "vue-star-rating";
import router from "../../../router";
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/pagination';
import targetService from "../../../services/targetService";
import alertService from "../../../services/alertService";
import {
    discountPercentage as calcDiscountPercentage,
    hasActiveDiscount as calcHasActiveDiscount,
    withCartLinePricing,
} from "../../../utils/productOffer";
import {
    getDisplaySoldCount as calcDisplaySoldCount,
} from "../../../utils/productSoldCount";
import {
    getVideoPoster as resolveVideoPoster,
    formatProductCardVideoLink,
    isEmbedVideo,
    getYouTubeId,
} from "../../../utils/videoPoster";
import {
    getProductAverageRating,
    getStarFillCount,
    formatListProductRating,
    getListReviewCount,
} from "../../../utils/productRating";
import activityEnum from "../../../enums/modules/activityEnum";
import { trackWishlistToggle } from "../../../services/analyticsEcommerceBridge";
import {
    productCardSwiperProps,
    isFinePointerDevice,
    isInteractiveCardTarget,
    readTouchPoint,
    createTouchSession,
    markSliderDragged,
    noteSliderTouchEnd,
    shouldOpenProductFromTouch,
    shouldSkipDuplicateClick,
    recordTouchNavigation,
} from "../../../utils/productCardTouch";

export default {
    name: "ProductListComponent",
    components: {
        starRating,
        Swiper,
        SwiperSlide
    },
    setup() {
        return {
            modules: [Pagination],
            cardSwiperProps: productCardSwiperProps,
        };
    },
    props: {
        "products": "object",
    },
    data() {
        return {
            swiperInstances: {},
            touchSessions: {},
            touchNavTimestamps: {},
            animatingWishlists: {},
            animatingCartIds: {},
            localWishlist: JSON.parse(localStorage.getItem('local_wishlist') || '[]'),
            loadedImages: {},
            cardVideoActiveIds: {},
        }
    },
    computed: {
        setting() {
            return this.$store.getters['frontendSetting/lists'];
        },
    },
    methods: {
        onImageLoad(key) {
            this.loadedImages[key] = true;
        },
        onImageError(event, key) {
            this.loadedImages[key] = true;
            event.target.src = this.$store.getters['frontendSetting/lists'].theme_logo;
            event.target.classList.remove('object-cover');
            event.target.classList.add('object-contain', 'bg-white', 'p-4');
        },
        onSwiperReady(swiper, productId) {
            this.swiperInstances[productId] = swiper;
            this.syncCardVideoPlayback(productId, swiper);
        },
        getCardRoot(productId) {
            return this.$el?.querySelector?.(`[data-card-product-id="${productId}"]`) || null;
        },
        scheduleCardVideoActivation(productId) {
            this.activateCardVideo(productId);
            window.setTimeout(() => this.playCardVideos(productId), 180);
        },
        onCardVideoCanPlay(productId, event) {
            if (!this.isCardVideoActive(productId)) {
                return;
            }

            const video = event.target;
            if (!video || video.paused === false) {
                return;
            }

            video.muted = true;
            const playPromise = video.play();
            if (playPromise?.catch) {
                playPromise.catch(() => {});
            }
        },
        getVideoSlideIndex(product) {
            return product.previews?.length > 0 ? 1 : 0;
        },
        goToCardSlide(productId, index) {
            const swiper = this.swiperInstances[productId];
            if (!swiper || swiper.slides.length <= index) {
                return;
            }

            if (swiper.params.loop && typeof swiper.slideToLoop === 'function') {
                swiper.slideToLoop(index);
                return;
            }

            swiper.slideTo(index);
        },
        onCardSlideChange(swiper, productId) {
            this.syncCardVideoPlayback(productId, swiper);
        },
        syncCardVideoPlayback(productId, swiper) {
            const product = this.products.find((item) => item.id === productId);
            if (!product?.videos?.length || !swiper) {
                return;
            }

            const videoIndex = this.getVideoSlideIndex(product);
            const activeIndex = swiper.params.loop ? swiper.realIndex : swiper.activeIndex;

            if (activeIndex === videoIndex) {
                this.scheduleCardVideoActivation(productId);
            } else {
                this.deactivateCardVideo(productId);
            }
        },
        isCardVideoActive(productId) {
            return !!this.cardVideoActiveIds[productId];
        },
        activateCardVideo(productId) {
            const product = this.products.find((item) => item.id === productId);
            if (!product?.videos?.length) {
                return;
            }

            this.cardVideoActiveIds = { ...this.cardVideoActiveIds, [productId]: true };
            this.$nextTick(() => {
                this.playCardVideos(productId);
                window.setTimeout(() => this.playCardVideos(productId), 120);
            });
        },
        deactivateCardVideo(productId) {
            if (!this.cardVideoActiveIds[productId]) {
                return;
            }

            const next = { ...this.cardVideoActiveIds };
            delete next[productId];
            this.cardVideoActiveIds = next;
            this.pauseCardVideos(productId);
        },
        playCardVideos(productId) {
            const root = this.getCardRoot(productId);
            if (!root) {
                return;
            }

            root.querySelectorAll('video[data-card-video]').forEach((video) => {
                video.muted = true;
                const playPromise = video.play();
                if (playPromise?.catch) {
                    playPromise.catch(() => {});
                }
            });
        },
        pauseCardVideos(productId) {
            const root = this.getCardRoot(productId);
            if (!root) {
                return;
            }

            root.querySelectorAll('video[data-card-video]').forEach((video) => {
                video.pause();
            });
        },
        hasMediaSlider(product) {
            return product.previews
                && product.previews.length > 0
                && (product.previews.length > 1 || (product.videos && product.videos.length > 0));
        },
        productRoute(product) {
            return { name: 'frontend.product.details', params: { slug: product.slug } };
        },
        prefetchProductDetails() {
            if (!this._detailChunkPrefetched) {
                this._detailChunkPrefetched = true;
                import('../product/ProductDetailsComponent.vue');
            }
        },
        onCardTouchStart(productId, event) {
            const point = readTouchPoint(event);
            if (!point) {
                return;
            }
            this.touchSessions[productId] = createTouchSession(point);
        },
        onSliderDragStart(productId) {
            markSliderDragged(this.touchSessions[productId]);
        },
        onSliderTouchEnd(productId) {
            noteSliderTouchEnd(this.touchSessions[productId]);
        },
        onCardActivate(product, event) {
            if (isInteractiveCardTarget(event.target)) {
                return;
            }

            if (event.type === 'touchend' || event.type === 'touchcancel') {
                const endPoint = readTouchPoint(event);
                const session = this.touchSessions[product.id];

                if (!shouldOpenProductFromTouch(session, endPoint)) {
                    delete this.touchSessions[product.id];
                    return;
                }

                event.preventDefault();
                recordTouchNavigation(product.id, this.touchNavTimestamps);
                delete this.touchSessions[product.id];
                this.navigateToProduct(product);
                return;
            }

            if (event.type === 'click') {
                if (shouldSkipDuplicateClick(product.id, this.touchNavTimestamps)) {
                    return;
                }
                this.navigateToProduct(product);
            }
        },
        navigateToProduct(product) {
            this.prefetchProductDetails();
            this.$router.push(this.productRoute(product));
        },
        onDesktopHoverEnter(productId) {
            if (!isFinePointerDevice()) {
                return;
            }
            const product = this.products.find((item) => item.id === productId);
            if (product?.videos?.length) {
                this.goToCardSlide(productId, this.getVideoSlideIndex(product));
                this.scheduleCardVideoActivation(productId);
            } else if (product) {
                this.goToCardSlide(productId, 1);
            }
            this.prefetchProductDetails();
        },
        onDesktopHoverLeave(productId) {
            if (!isFinePointerDevice()) {
                return;
            }
            const product = this.products.find((item) => item.id === productId);
            if (product?.videos?.length) {
                this.deactivateCardVideo(productId);
                this.goToCardSlide(productId, 0);
            } else if (product) {
                this.goToCardSlide(productId, 0);
            }
        },
        isWishlisted(product) {
            if (!product) return false;
            if (this.$store.getters.authStatus) {
                return product.wishlist;
            }
            return this.localWishlist.includes(product.id);
        },
        wishlist: function (product) {
            const currentStatus = this.isWishlisted(product);
            const nextStatus = !currentStatus;
            
            if (this.$store.getters.authStatus) {
                this.$store.dispatch("frontendWishlist/toggle", {
                    product_id: product.id,
                    toggle: nextStatus
                }).then((res) => {
                    if (nextStatus) {
                        this.animatingWishlists[product.id] = true;
                        setTimeout(() => {
                            this.animatingWishlists[product.id] = false;
                        }, 800);
                    }
                    product.wishlist = nextStatus;
                }).catch((err) => {
                    if (err.response && err.response.status === 401) {
                        product.wishlist = false;
                        localStorage.setItem('pending_wishlist_product_id', product.id);
                        router.push({ name: "auth.login" });
                    }
                });
            } else {
                // Guest logic!
                let localWish = JSON.parse(localStorage.getItem('local_wishlist') || '[]');
                const prodId = product.id;
                if (localWish.includes(prodId)) {
                    localWish = localWish.filter(id => id !== prodId);
                } else {
                    localWish.push(prodId);
                    this.animatingWishlists[product.id] = true;
                    setTimeout(() => {
                        this.animatingWishlists[product.id] = false;
                    }, 800);
                }
                localStorage.setItem('local_wishlist', JSON.stringify(localWish));
                this.localWishlist = localWish;
                trackWishlistToggle(
                    { id: product.id, product_id: product.id, sku: product.sku },
                    localWish.includes(prodId)
                );
            }
        },
        getYouTubeId,
        formatProductCardVideoLink,
        isEmbedVideo,
        getProductSoldCount(product) {
            return calcDisplaySoldCount(product);
        },
        getVideoPosterForCard(video, product) {
            return resolveVideoPoster(video, product.previews?.[0] || product.cover || '');
        },
        getProductAverageRating,
        getStarFillCount,
        formatListProductRating,
        getListReviewCount,
        hasActiveDiscount(product) {
            return calcHasActiveDiscount(product);
        },
        showSaleBadge(product) {
            return calcHasActiveDiscount(product);
        },
        discountPercentage(product) {
            return calcDiscountPercentage(product);
        },
        buyNow: function (product) {
            if (product.variation_count > 0) {
                alertService.error(this.$t('message.please_select_a_variation') || 'Please select a variation first!');
                this.$router.push({ name: 'frontend.product.details', params: { slug: product.slug } });
            } else {
                // Increment social proof sold count
                if (product.id) {
                    const storageKey = 'sold_count_' + product.id;
                    let count = this.getProductSoldCount(product) + 1;
                    localStorage.setItem(storageKey, count);
                }

                this.$store.dispatch("frontendCart/lists", withCartLinePricing({
                    name: product.name,
                    product_id: product.id,
                    image: product.cover,
                    variation_names: '',
                    variation_id: null,
                    sku: product.sku,
                    stock: product.stock,
                    taxes: product.taxes,
                    shipping: product.shipping,
                    quantity: 1,
                    maximum_purchase_quantity: product.maximum_purchase_quantity,
                    in_baskets: product.in_baskets || 0,
                    bought_last_24_hours: product.bought_last_24_hours || 0,
                    actual_sales: product.actual_sales || 0,
                    skipCartDrawer: true,
                }, product)).then((res) => {
                    this.$router.push({ name: "frontend.checkout.checkout" });
                }).catch((err) => {
                    if (err && err.message === "stockOut") {
                        alertService.error("This product is out of stock!");
                    } else if (err && err.message === "maximum_quantity") {
                        alertService.error("Maximum purchase quantity reached!");
                    }
                });
            }
        },
        isOutOfStock: function (product) {
            if (!product) return false;
            const siteShow = this.setting?.site_show_stock_out;
            if (siteShow !== undefined && siteShow !== null && parseInt(siteShow, 10) !== activityEnum.ENABLE) {
                return false;
            }
            return parseInt(product.stock, 10) <= 0;
        },
        getProductBadges: function (product) {
            if (!product) return [];
            const candidates = [];
            if (product.is_last_day_of_sale) {
                candidates.push({ key: 'last_day', type: 'last_day', label: 'Last day', icon: null });
            }
            if (product.flash_sale) {
                candidates.push({ key: 'flash', type: 'flash', label: this.$t('label.flash_sale'), icon: null });
            }
            if (this.showSaleBadge(product)) {
                candidates.push({
                    key: 'sale',
                    type: 'sale',
                    label: `${this.discountPercentage(product)}% OFF`,
                    icon: 'fa-solid fa-tags',
                });
            }
            if (this.isTrending(product)) {
                candidates.push({ key: 'hot', type: 'hot', label: 'HOT', icon: 'fa-solid fa-fire-flame-curved' });
            }
            if (this.isNew(product)) {
                candidates.push({ key: 'new', type: 'new', label: 'NEW', icon: 'fa-solid fa-sparkles' });
            }
            return candidates.slice(0, 2);
        },
        badgeClass: function (type) {
            const map = {
                new: 'bg-blue-500 text-white shadow-[0_2px_8px_rgba(59,130,246,0.25)]',
                hot: 'bg-orange-500 text-white shadow-[0_2px_8px_rgba(249,115,22,0.25)]',
                sale: 'bg-primary text-white shadow-[0_2px_8px_rgba(255,92,0,0.2)]',
                flash: 'bg-secondary text-white capitalize',
                last_day: 'bg-red-600 text-white animate-pulse',
            };
            return map[type] || 'bg-gray-600 text-white';
        },
        addToCart: function (product) {
            if (this.isOutOfStock(product)) {
                alertService.error(this.$t('message.out_of_stock') || 'This item is out of stock!');
                return;
            }
            // If product has variations, redirect to detail page (same as ProductDetailsComponent)
            if (product.variation_count > 0) {
                alertService.error(this.$t('message.please_select_a_variation') || 'Please select a variation first!');
                this.$router.push({ name: 'frontend.product.details', params: { slug: product.slug } });
                return;
            }

            // Increment social proof sold count (exact logic from ProductDetailsComponent)
            if (product.id) {
                const storageKey = 'sold_count_' + product.id;
                let count = this.getProductSoldCount(product) + 1;
                localStorage.setItem(storageKey, count);
            }

            const productPayload = withCartLinePricing({
                name: product.name,
                product_id: product.id,
                image: product.cover,
                variation_names: '',
                variation_id: null,
                sku: product.sku,
                stock: product.stock,
                taxes: product.taxes,
                shipping: product.shipping,
                quantity: 1,
                maximum_purchase_quantity: product.maximum_purchase_quantity,
                in_baskets: product.in_baskets || 0,
                bought_last_24_hours: product.bought_last_24_hours || 0,
                actual_sales: product.actual_sales || 0,
            }, product);

            // Dispatch to cart (exact pattern from ProductDetailsComponent else branch)
            this.animatingCartIds[product.id] = true;
            setTimeout(() => {
                this.animatingCartIds[product.id] = false;
            }, 600);

            this.$store.dispatch("frontendCart/lists", productPayload).catch((err) => {
                if (err && err.message === "stockOut") {
                    alertService.error(this.$t('message.out_of_stock') || "This product is out of stock!");
                } else {
                    alertService.error(this.$t('message.maximum_quantity') || "Maximum purchase quantity reached!");
                }
            });
        },
        shareProductCard: function (product) {
            const shareUrl = window.location.origin + '/product/' + product.slug;
            const shareData = {
                title: product.name,
                text: product.name,
                url: shareUrl
            };
            
            if (navigator.share) {
                navigator.share(shareData)
                    .then(() => {})
                    .catch((err) => {});
            } else {
                navigator.clipboard.writeText(shareUrl).then(() => {
                    alertService.success("Product link copied to clipboard!");
                }).catch(() => {
                    alertService.error("Failed to copy link.");
                });
            }
        },
        getPaginationConfig: function (product) {
            const hasVideo = product.videos && product.videos.length > 0;
            if (!hasVideo) {
                return { clickable: true };
            }
            // video slide index: always at position 1 (after 1st image slide)
            const videoSlideIndex = 1;
            return {
                clickable: true,
                renderBullet: function (index, className) {
                    if (index === videoSlideIndex) {
                        return `<span class="${className} video-dot"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M7 6v12l10-6z"/></svg></span>`;
                    }
                    return `<span class="${className}"></span>`;
                }
            };
        },
        isNew: function (product) {
            if (!product || !product.created_at) return false;
            const createdAt = new Date(product.created_at);
            const now = new Date();
            const diffTime = Math.abs(now - createdAt);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            return diffDays <= 7;
        },
        isTrending: function (product) {
            if (!product) return false;
            if (product.is_last_day_of_sale) return false;
            const salesCount = this.getProductSoldCount(product);
            const isHighlyRated = product.rating_star_count > 0 && (product.rating_star / product.rating_star_count) >= 4.5;
            
            // It's trending if it has simulated or actual sales over 150 OR high rating with some sales
            return salesCount >= 150 || (isHighlyRated && salesCount >= 50);
        }
    }
}
</script>

<style scoped>
.product-card {
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
    isolation: isolate;
    z-index: 0;
}

.product-card__body,
.product-card__actions,
.product-card__cart-btn {
    position: relative;
    z-index: 0;
}

@media (hover: hover) and (pointer: fine) {
    .product-card:hover {
        transform: translateY(-0.375rem);
        border-color: rgb(255 92 0 / 0.4);
        box-shadow: 0 16px 32px rgb(255 92 0 / 0.08);
    }

    .product-card:hover .product-card__image {
        transform: scale(1.05);
    }

    .product-card:hover .product-card__title {
        color: #ff5c00;
    }
}

.product-card__image {
    transform: scale(1);
}

.product-card-slider {
    touch-action: pan-y pinch-zoom;
    -webkit-tap-highlight-color: transparent;
}

.product-card-swiper {
    touch-action: pan-y pinch-zoom;
}

.product-card-badge {
    font-size: 9px;
    line-height: 1.1;
    padding: 3px 7px;
}

@media (min-width: 640px) {
    .product-card-badge {
        font-size: 10px;
        padding: 4px 8px;
    }
}

.product-card-slider :deep(.swiper-pagination) {
    bottom: 8px !important;
    opacity: 0 !important;
    transition: all 0.3s ease;
}

.group:hover .product-card-slider :deep(.swiper-pagination) {
    opacity: 1 !important;
}

@media (hover: none), (pointer: coarse) {
    .group:hover .product-card-slider :deep(.swiper-pagination) {
        opacity: 1 !important;
    }
}

@media (max-width: 640px) {
    .product-card-slider :deep(.swiper-pagination) {
        opacity: 1 !important;
    }
}

.product-card-slider :deep(.swiper-pagination-bullet) {
    background: #ff5c00 !important;
    width: 6px;
    height: 6px;
    opacity: 0.6;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
    transition: all 0.3s ease;
    margin: 0 5px !important;
    position: relative;
}

.product-card-slider :deep(.swiper-pagination-bullet)::before {
    content: '';
    position: absolute;
    inset: -10px;
}

.product-card-slider :deep(.swiper-pagination-bullet-active) {
    background: #ff5c00 !important;
    opacity: 1;
    width: 16px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

/* Video play icon replacing the 2nd pagination dot */
.product-card-slider :deep(.video-dot) {
    background: rgba(255, 92, 0, 0.55) !important;
    width: 14px !important;
    height: 14px !important;
    border-radius: 50% !important;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    padding: 2px !important;
}

.product-card-slider :deep(.video-dot svg) {
    width: 8px;
    height: 8px;
    fill: white;
    display: block;
    margin-left: 1px;
}

.product-card-slider :deep(.video-dot.swiper-pagination-bullet-active) {
    background: #ff5c00 !important;
    width: 14px !important;
    border-radius: 50% !important;
}

.animate-heart-pulse {
    animation: heartPulse 0.45s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
}

.animate-fade-overlay {
    animation: fadeOverlay 0.8s ease-in-out forwards;
}

.animate-heart-burst {
    animation: heartBurst 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

@keyframes fadeOverlay {
    0% { opacity: 0; }
    15% { opacity: 1; }
    75% { opacity: 1; }
    100% { opacity: 0; }
}

@keyframes heartBurst {
    0% { transform: scale(0.3); opacity: 0; }
    25% { transform: scale(1.1); opacity: 1; }
    35% { transform: scale(0.95); }
    75% { transform: scale(1); opacity: 1; }
    100% { transform: scale(0.8); opacity: 0; }
}

@keyframes heartPulse {
    0% {
        transform: scale(1);
    }
    35% {
        transform: scale(1.45);
    }
    70% {
        transform: scale(0.85);
    }
    100% {
        transform: scale(1);
    }
}

.animate-cart-bounce {
    animation: cartBounce 0.55s ease-out;
}

@keyframes cartBounce {
    0% { transform: scale(1); }
    30% { transform: scale(1.2) rotate(-8deg); }
    55% { transform: scale(0.92) rotate(4deg); }
    100% { transform: scale(1) rotate(0deg); }
}
</style>