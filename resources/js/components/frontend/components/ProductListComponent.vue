<template>
    <div v-if="products.length > 0" v-for="product in products" :key="product.id"
        class="p-1 sm:p-1.5 bg-white rounded-2xl border border-gray-200/80 shadow-[0_4px_16px_rgba(0,0,0,0.05)] duration-300 hover:-translate-y-1.5 hover:border-primary/40 hover:shadow-[0_16px_32px_rgba(255,92,0,0.08)] transition-all ease-out group cursor-pointer relative"
        @click="goToProductDetails(product, $event)"
        @mouseenter="onMouseEnter(product.id)" 
        @mouseleave="onMouseLeave(product.id)">
        <div class="relative overflow-hidden rounded-xl isolate">
            <!-- Heart Screen Overlay Animation -->
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
 
            <button type="button" @click.prevent.stop="wishlist(product)"
                :class="isWishlisted(product) ? 'lab-fill-heart text-primary animate-heart-pulse shadow-[0_4px_12px_rgba(255,92,0,0.45)]' : 'lab-line-heart text-secondary hover:text-primary hover:shadow-[0_4px_10px_rgba(0,0,0,0.1)]'"
                class="w-8 h-8 leading-8 rounded-full text-center text-lg shadow-badge absolute top-3 right-3 z-10 bg-white hover:scale-110 active:scale-95 transition-all duration-300 flex items-center justify-center">
            </button>

            <div class="overflow-hidden rounded-xl w-full block relative aspect-[4/5] product-card-slider">
                <!-- Main Slider for Product Images + Video -->
                <Swiper v-if="product.previews && product.previews.length > 0 && (product.previews.length > 1 || (product.videos && product.videos.length > 0))"
                    :dir="'ltr'"
                    :pagination="getPaginationConfig(product)"
                    :modules="modules"
                    :loop="true"
                    @swiper="onSwiperInit($event, product.id)"
                    @click="(swiper, event) => onSwiperClick(swiper, event, product)"
                    class="w-full h-full">
                    
                    <!-- 1st Slide: First Image -->
                    <SwiperSlide v-if="product.previews.length > 0">
                        <router-link :to="{ name: 'frontend.product.details', params: { slug: product.slug } }" class="w-full h-full block relative bg-gray-50">
                            <!-- Loading Dots Indicator -->
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
                                class="w-full h-full object-cover transition-all duration-700 group-hover:scale-105 relative z-10">
                            <div class="absolute inset-0 z-20 cursor-pointer"></div>
                        </router-link>
                    </SwiperSlide>

                    <!-- 2nd Slide: Video (if exists) -->
                    <SwiperSlide v-if="product.videos && product.videos.length > 0">
                        <router-link :to="{ name: 'frontend.product.details', params: { slug: product.slug } }" class="w-full h-full block relative">
                             <div class="w-full h-full bg-black relative aspect-[4/5]">
                                 <iframe v-if="product.videos[0].video_provider === 5" 
                                    :src="product.videos[0].link + '?autoplay=1&mute=1&loop=1&playlist=' + getYouTubeId(product.videos[0].link) + '&controls=0&showinfo=0&modestbranding=1&playsinline=1'" 
                                    class="w-full h-full pointer-events-none" 
                                    frameborder="0" allow="autoplay; encrypted-media">
                                 </iframe>
                                 <video v-else :src="product.videos[0].link" autoplay="true" muted="true" loop="true" playsinline="true" webkit-playsinline="true" class="w-full h-full object-cover pointer-events-none"></video>
                             </div>
                             <div class="absolute inset-0 z-20 cursor-pointer"></div>
                        </router-link>
                    </SwiperSlide>

                    <!-- Rest of Slides: Remaining Images -->
                    <SwiperSlide v-for="(image, index) in product.previews.slice(1)" :key="index">
                        <router-link :to="{ name: 'frontend.product.details', params: { slug: product.slug } }" class="w-full h-full block relative bg-gray-50">
                            <!-- Loading Dots Indicator -->
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
                                class="w-full h-full object-cover transition-all duration-700 group-hover:scale-105 relative z-10">
                            <div class="absolute inset-0 z-20 cursor-pointer"></div>
                        </router-link>
                    </SwiperSlide>
                </Swiper>
                
                <!-- Single Image Fallback (no video, only 1 image) -->
                <router-link v-else class="w-full h-full block relative bg-gray-50"
                    :to="{ name: 'frontend.product.details', params: { slug: product.slug } }">
                    
                    <template v-if="product.cover && !product.cover.includes('default/product')">
                        <!-- Loading Dots Indicator -->
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
                            class="w-full h-full object-cover transition-all duration-700 group-hover:scale-105 relative z-10">
                    </template>
                    <div v-else class="w-full h-full flex items-center justify-center bg-gray-50/50 absolute inset-0 z-10">
                        <img :src="$store.getters['frontendSetting/lists'].theme_logo" alt="logo" loading="lazy"
                            class="w-3/4 h-3/4 object-contain opacity-40 transition-all duration-700 group-hover:scale-105 group-hover:opacity-70">
                    </div>
                </router-link>
            </div>
        </div><!-- /.relative.overflow-hidden.rounded-xl.isolate -->

        <router-link class="block overflow-hidden text-ellipsis" :to="{ name: 'frontend.product.details', params: { slug: product.slug } }">
            <div class="px-1.5 sm:px-2 pt-2">
                <!-- 1. Price and Add to Cart Row -->
                <div class="flex items-center justify-between">
                    <div class="flex flex-col">
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

                    <!-- Add to Cart / Sold out -->
                    <button v-if="!isOutOfStock(product)" type="button" @click.prevent.stop="addToCart(product)"
                        :title="product.variation_count > 0 ? ($t('label.choose_options') || 'Choose options') : ($t('button.add_to_cart') || 'Add to Cart')"
                        :class="animatingCartIds[product.id] ? 'animate-cart-bounce' : ''"
                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#ff5c00] text-white flex items-center justify-center shadow-[0_3px_8px_rgba(255,92,0,0.15)] hover:scale-105 active:scale-95 transition-all duration-300">
                        <i class="fa-solid fa-cart-plus text-white text-sm sm:text-base"></i>
                    </button>
                    <span v-else-if="isOutOfStock(product)"
                        class="inline-flex items-center justify-center min-w-[4.5rem] sm:min-w-[5rem] h-9 sm:h-10 px-2 rounded-xl bg-gray-100 text-gray-600 text-[10px] sm:text-xs font-bold uppercase tracking-wide shrink-0 pointer-events-none">
                        {{ $t('label.sold_out') || 'Sold Out' }}
                    </span>
                </div>

                <!-- 2. Product Name (smaller size, mt-1.5) -->
                <h3 class="capitalize text-xs sm:text-sm font-semibold transition-all duration-300 hover:text-primary overflow-hidden text-ellipsis leading-tight mt-1.5 mb-1 text-gray-800">
                    {{ product.name }}
                </h3>

                <!-- Stock Left Alert -->
                <div v-if="product.stock > 0 && product.stock <= 5" class="mt-1 mb-1">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-bold text-red-600 bg-red-50 border border-red-100/50 animate-pulse w-full">
                        <i class="fa-solid fa-fire text-red-500"></i>
                        Only {{ product.stock }} Left in Stock!
                    </span>
                </div>

                <!-- 3. Rating & Sold Count Row (real reviews only — no fake 5.0) -->
                <div v-if="hasProductRating(product) || shouldShowSoldCount(product)"
                    class="flex items-center gap-1.5 mt-1 text-[11px] text-gray-500 font-medium">
                    <div v-if="hasProductRating(product)" class="flex items-center gap-1">
                        <div class="flex items-center gap-0.5" :aria-label="formatProductRating(product) + ' out of 5'">
                            <i v-for="star in 5" :key="star"
                                :class="star <= getStarFillCount(product) ? 'fa-solid text-primary' : 'fa-regular text-gray-300'"
                                class="fa-star text-[9px] sm:text-[10px]"></i>
                        </div>
                        <span class="text-gray-900 font-bold">{{ formatProductRating(product) }}</span>
                        <span>({{ product.rating_star_count }})</span>
                    </div>
                    <span v-if="hasProductRating(product) && shouldShowSoldCount(product)" class="text-gray-200">|</span>
                    <span v-if="shouldShowSoldCount(product)">
                        {{ getProductSoldCount(product) }} sold
                    </span>
                </div>
            </div>
        </router-link>
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
    hasProductRating,
    getProductAverageRating,
    getStarFillCount,
    formatProductRating,
} from "../../../utils/productRating";
import activityEnum from "../../../enums/modules/activityEnum";
import { trackWishlistToggle } from "../../../services/analyticsEcommerceBridge";

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
        };
    },
    props: {
        "products": "object",
    },
    data() {
        return {
            swiperInstances: {},
            animatingWishlists: {},
            animatingCartIds: {},
            localWishlist: JSON.parse(localStorage.getItem('local_wishlist') || '[]'),
            loadedImages: {}
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
        onSwiperInit(swiper, productId) {
            this.swiperInstances[productId] = swiper;
        },
        onMouseEnter(productId) {
            const swiper = this.swiperInstances[productId];
            if (swiper && swiper.slides.length > 1) {
                swiper.slideToLoop(1);
            }
        },
        onMouseLeave(productId) {
            const swiper = this.swiperInstances[productId];
            if (swiper) {
                swiper.slideToLoop(0);
            }
        },
        onSwiperClick(swiper, event, product) {
            const target = event.target;
            if (target && (target.classList.contains('swiper-pagination-bullet') || target.closest('.swiper-pagination'))) {
                return;
            }
            this.$router.push({ name: 'frontend.product.details', params: { slug: product.slug } });
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
        getYouTubeId(url) {
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
            const match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : null;
        },
        hasProductRating,
        getProductAverageRating,
        getStarFillCount,
        formatProductRating,
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
        goToProductDetails: function (product, event) {
            const path = event.composedPath() || [];
            for (let i = 0; i < path.length; i++) {
                const target = path[i];
                if (target.tagName === 'BUTTON' || target.tagName === 'A' || (target.classList && (target.classList.contains('swiper-pagination-bullet') || target.classList.contains('swiper-button-next') || target.classList.contains('swiper-button-prev')))) {
                    return;
                }
                if (target === event.currentTarget) {
                    break;
                }
            }
            this.$router.push({ name: 'frontend.product.details', params: { slug: product.slug } });
        },
        getProductSoldCount: function (product) {
            const randomSaleValue = parseInt(product.use_random_sale);
            if (!product || randomSaleValue === 10 || randomSaleValue === 0) {
                return product.actual_sales || 0;
            }
            
            let startingPoint = randomSaleValue === 5 ? ((product.id * 53) % 450 + 138) : randomSaleValue;
            
            const storageKey = 'sold_count_' + product.id;
            let localCount = localStorage.getItem(storageKey);
            if (!localCount || parseInt(localCount) < startingPoint) {
                localCount = startingPoint + (product.actual_sales || 0);
                localStorage.setItem(storageKey, localCount);
            }
            return parseInt(localCount);
        },
        shouldShowSoldCount: function (product) {
            if (!product) return false;
            const isRandomSaleOff = parseInt(product.use_random_sale) === 10 || parseInt(product.use_random_sale) === 0;
            if (isRandomSaleOff && (!product.actual_sales || parseInt(product.actual_sales) === 0)) {
                return false;
            }
            return true;
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