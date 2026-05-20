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
 
            <div class="absolute top-3 left-3 z-10 flex flex-col gap-1.5 items-start">
                <span v-if="product.is_offer && discountPercentage(product) > 0" 
                    class="bg-primary text-white text-[10px] sm:text-xs font-extrabold px-2.5 py-1 rounded-full shadow-[0_4px_12px_rgba(255,92,0,0.25)] flex items-center gap-1 animate-pulse">
                    <i class="fa-solid fa-tags text-[9px] sm:text-[11px]"></i>
                    {{ discountPercentage(product) }}% OFF
                </span>
                <span v-if="product.is_offer && product.flash_sale"
                    class="capitalize text-[10px] sm:text-xs font-semibold rounded-full py-1 px-2.5 shadow-badge bg-secondary text-white">
                    {{ $t('label.flash_sale') }}
                </span>
                <span v-if="product.stock > 0 && product.stock <= 5"
                    class="capitalize text-[10px] sm:text-xs font-extrabold rounded-full py-1 px-2.5 shadow-[0_4px_12px_rgba(239,68,68,0.25)] bg-red-600 text-white flex items-center gap-1.5 animate-pulse">
                    <i class="fa-solid fa-fire text-yellow-300 text-[9px] sm:text-[11px]"></i>
                    Only {{ product.stock }} Left!
                </span>
            </div>
 
            <button type="button" @click.prevent.stop="wishlist(product)"
                :class="isWishlisted(product) ? 'lab-fill-heart text-primary animate-heart-pulse shadow-[0_4px_12px_rgba(255,92,0,0.45)]' : 'lab-line-heart text-secondary hover:text-primary hover:shadow-[0_4px_10px_rgba(0,0,0,0.1)]'"
                class="w-8 h-8 leading-8 rounded-full text-center text-lg shadow-badge absolute top-3 right-3 z-10 bg-white hover:scale-110 active:scale-95 transition-all duration-300 flex items-center justify-center">
            </button>

            <div v-if="product.videos && product.videos.length > 0" 
                class="absolute bottom-8 left-1/2 -translate-x-1/2 z-10 w-7 h-7 flex items-center justify-center rounded-full bg-primary text-white shadow-sm pointer-events-none transition-all duration-300">
                <svg class="w-3 h-3 fill-current ml-0.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7 6v12l10-6z"/>
                </svg>
            </div>

            <div class="overflow-hidden rounded-xl w-full block relative aspect-[4/5] product-card-slider">
                <!-- Main Slider for Product Images -->
                <Swiper v-if="product.previews && product.previews.length > 1"
                    :dir="'ltr'"
                    :pagination="{ clickable: true }"
                    :modules="modules"
                    :loop="true"
                    @swiper="onSwiperInit($event, product.id)"
                    @click="(swiper, event) => onSwiperClick(swiper, event, product)"
                    class="w-full h-full">
                    
                    <!-- 1st Slide: First Image -->
                    <SwiperSlide v-if="product.previews.length > 0">
                        <router-link :to="{ name: 'frontend.product.details', params: { slug: product.slug } }" class="w-full h-full block relative">
                            <img :src="product.previews[0]" alt="product" class="w-full h-full object-cover transition-all duration-700 group-hover:scale-105">
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
                        <router-link :to="{ name: 'frontend.product.details', params: { slug: product.slug } }" class="w-full h-full block relative">
                            <img :src="image" alt="product" class="w-full h-full object-cover transition-all duration-700 group-hover:scale-105">
                            <div class="absolute inset-0 z-20 cursor-pointer"></div>
                        </router-link>
                    </SwiperSlide>
                </Swiper>
                
                <!-- Single Image Fallback -->
                <router-link v-else class="w-full h-full block"
                    :to="{ name: 'frontend.product.details', params: { slug: product.slug } }">
                    <img :src="product.cover" alt="product"
                        class="w-full h-full object-cover transition-all duration-700 group-hover:scale-105">
                </router-link>
            </div>
        </div>

        <router-link class="block overflow-hidden text-ellipsis" :to="{ name: 'frontend.product.details', params: { slug: product.slug } }">
            <div class="px-1.5 sm:px-2 pt-2">
                <!-- 1. Price and Add to Cart Row -->
                <div class="flex items-center justify-between">
                    <div class="flex flex-col">
                        <div class="flex flex-wrap items-baseline gap-1" v-if="product.is_offer">
                            <span class="text-base sm:text-lg font-black text-primary leading-none">
                                {{ product.discounted_price }}
                            </span>
                            <span class="text-[11px] sm:text-xs font-semibold text-shopperz-red line-through leading-none">
                                {{ product.currency_price }}
                            </span>
                        </div>
                        <span class="text-base sm:text-lg font-black text-primary leading-none" v-else>
                            {{ product.currency_price }}
                        </span>
                    </div>

                    <!-- Add to Cart Round Icon Button (replacing the Buy Now button) -->
                    <button type="button" @click.prevent.stop="addToCart(product)" title="Add to Cart"
                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#ff5c00] text-white flex items-center justify-center shadow-[0_3px_8px_rgba(255,92,0,0.15)] hover:scale-105 active:scale-95 transition-all duration-300">
                        <i class="fa-solid fa-cart-plus text-white text-sm sm:text-base"></i>
                    </button>
                </div>

                <!-- 2. Product Name (smaller size, mt-1.5) -->
                <h3 class="capitalize text-xs sm:text-sm font-semibold transition-all duration-300 hover:text-primary overflow-hidden text-ellipsis leading-tight mt-1.5 mb-1 text-gray-800">
                    {{ product.name }}
                </h3>

                <!-- 3. Rating & Sold Count Row -->
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-gray-500 font-medium">
                    <div class="flex items-center gap-1">
                        <i class="fa-solid fa-star text-[#FFBC1F] text-[10px]"></i>
                        <span class="text-gray-900 font-bold">
                            {{ product.rating_star_count > 0 ? (product.rating_star / product.rating_star_count).toFixed(1) : '5.0' }}
                        </span>
                        <span>
                            ({{ product.rating_star_count }})
                        </span>
                    </div>
                    <span v-if="shouldShowSoldCount(product)" class="text-gray-200">|</span>
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
            localWishlist: JSON.parse(localStorage.getItem('local_wishlist') || '[]')
        }
    },
    methods: {
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
                this.localWishlist = localWish; // triggers reactivity
            }
        },
        getYouTubeId(url) {
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
            const match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : null;
        },
        discountPercentage(product) {
            if (product.old_price && product.price && product.old_price > product.price) {
                return Math.round(((product.old_price - product.price) / product.old_price) * 100);
            }
            return 0;
        },
        buyNow: function (product) {
            if (product.variation_count > 0) {
                this.$router.push({ name: 'frontend.product.details', params: { slug: product.slug } });
            } else {
                // Increment social proof sold count
                if (product.id) {
                    const storageKey = 'sold_count_' + product.id;
                    let localCount = localStorage.getItem(storageKey);
                    if (!localCount) {
                        localCount = (product.id * 53) % 450 + 138;
                    }
                    let count = parseInt(localCount) + 1;
                    localStorage.setItem(storageKey, count);
                }

                this.$store.dispatch("frontendCart/lists", {
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
                    discount: product.discount,
                    price: product.price,
                    old_price: product.old_price,
                    total_price: product.price,
                    maximum_purchase_quantity: product.maximum_purchase_quantity,
                    skipCartDrawer: true
                }).then((res) => {
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
        addToCart: function (product) {
            // If product has variations, redirect to detail page (same as ProductDetailsComponent)
            if (product.variation_count > 0) {
                this.$router.push({ name: 'frontend.product.details', params: { slug: product.slug } });
                return;
            }

            // Increment social proof sold count (exact logic from ProductDetailsComponent)
            if (product.id) {
                const storageKey = 'sold_count_' + product.id;
                let localCount = localStorage.getItem(storageKey);
                if (!localCount) {
                    localCount = (product.id * 53) % 450 + 138;
                }
                let count = parseInt(localCount) + 1;
                localStorage.setItem(storageKey, count);
            }

            // Build product payload (exact structure from ProductDetailsComponent)
            const productPayload = {
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
                discount: product.discount,
                price: product.price,
                old_price: product.old_price,
                total_price: product.price,
                maximum_purchase_quantity: product.maximum_purchase_quantity
            };

            // Dispatch to cart (exact pattern from ProductDetailsComponent else branch)
            this.$store.dispatch("frontendCart/lists", productPayload).then((res) => {
                // success - cart drawer opens automatically via store action
            }).catch((err) => {
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
            if (product && (parseInt(product.use_random_sale) === 10 || parseInt(product.use_random_sale) === 0)) {
                return product.actual_sales || 0;
            }
            const storageKey = 'sold_count_' + product.id;
            let localCount = localStorage.getItem(storageKey);
            if (!localCount) {
                localCount = (product.id * 53) % 450 + 138;
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
    }
}
</script>

<style scoped>
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
</style>