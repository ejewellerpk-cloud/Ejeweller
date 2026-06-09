<template>
    <section
        v-if="sectionEnabled"
        ref="sentinel"
        class="mb-12 sm:mb-16"
        aria-labelledby="related-products-heading"
    >
        <div class="container">
            <h2 id="related-products-heading" class="text-2xl sm:text-4xl font-bold capitalize mb-5 sm:mb-7">
                {{ $t("label.related_products") }}
            </h2>

            <RelatedProductsSliderSkeleton v-if="loading" :count="skeletonCount" />

            <p v-else-if="!products.length" class="text-sm text-gray-500">
                {{ $t("message.no_related_products") }}
            </p>

            <div v-else class="product-section-slider-container relative">
                <Swiper
                    :key="swiperKey"
                    dir="ltr"
                    v-bind="swiperTouch"
                    :modules="swiperModules"
                    :slides-per-view="2"
                    :space-between="6"
                    :speed="scrollSpeed"
                    :loop="loopEnabled"
                    :loop-additional-slides="loopAdditionalSlides"
                    :autoplay="autoplayConfig"
                    :navigation="!autoScrollEnabled"
                    :allow-touch-move="touchEnabled"
                    :simulate-touch="touchEnabled"
                    :grab-cursor="touchEnabled"
                    :breakpoints="breakpoints"
                    class="related-products-swiper homepage-touch-swiper"
                    :class="{ 'related-products-swiper--marquee': autoScrollEnabled }"
                    :aria-label="$t('label.related_products')"
                    @swiper="onSwiper"
                    @touchStart="onManualStart"
                    @sliderFirstMove="onManualStart"
                    @touchEnd="onManualEnd"
                    @touchCancel="onManualEnd"
                    @transitionEnd="onTransitionEnd"
                >
                    <SwiperSlide v-for="(product, index) in carouselProducts" :key="product.id + '-' + index">
                        <ProductListComponent :products="[product]" />
                    </SwiperSlide>
                </Swiper>
            </div>
        </div>
    </section>
</template>

<script>
import { Autoplay, Navigation } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/vue";
import "swiper/css";
import "swiper/css/navigation";
import activityEnum from "../../../enums/modules/activityEnum";
import ProductListComponent from "../components/ProductListComponent.vue";
import RelatedProductsSliderSkeleton from "./RelatedProductsSliderSkeleton.vue";
import { homepageProductRowSwiperProps } from "../../../utils/homepageSwiper";
import {
    applyMarqueeLinearMotion,
    configureRelatedMarqueeSwiper,
    continuousAutoplayConfig,
    destroyRelatedMarqueeSwiper,
    detectMarqueeDirectionFromTouch,
    duplicateMarqueeSlides,
    pauseRelatedMarqueeTouch,
    resumeRelatedMarqueeTouch,
    touchFriendlySwiperProps,
} from "../../../utils/continuousSwiper";

export default {
    name: "RelatedProductsSection",
    components: {
        ProductListComponent,
        RelatedProductsSliderSkeleton,
        Swiper,
        SwiperSlide,
    },
    props: {
        productSlug: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            loading: false,
            loaded: false,
            products: [],
            swiperInstance: null,
            observer: null,
            marqueeDirection: "forward",
            breakpoints: {
                640: { slidesPerView: 2, spaceBetween: 20 },
                768: { slidesPerView: 3, spaceBetween: 24 },
                1024: { slidesPerView: 4, spaceBetween: 24 },
            },
        };
    },
    computed: {
        settings() {
            return this.$store.getters["frontendSetting/lists"] || {};
        },
        sectionEnabled() {
            const status = Number(this.settings.product_page_related_status);
            return status !== activityEnum.DISABLE;
        },
        autoScrollEnabled() {
            return Number(this.settings.product_page_related_autoscroll) !== activityEnum.DISABLE;
        },
        touchEnabled() {
            return Number(this.settings.product_page_related_touch) !== activityEnum.DISABLE;
        },
        scrollSpeed() {
            const speed = Number(this.settings.product_page_related_speed);
            return Number.isFinite(speed) && speed >= 2000 ? speed : 3800;
        },
        configuredDirection() {
            return this.settings.product_page_related_direction === "ltr" ? "reverse" : "forward";
        },
        swiperModules() {
            return this.autoScrollEnabled ? [Autoplay] : [Navigation];
        },
        swiperTouch() {
            if (!this.touchEnabled) {
                return {
                    allowTouchMove: false,
                    simulateTouch: false,
                };
            }
            return this.autoScrollEnabled
                ? { ...touchFriendlySwiperProps, grabCursor: true }
                : homepageProductRowSwiperProps;
        },
        autoplayConfig() {
            if (!this.autoScrollEnabled) {
                return false;
            }
            return { ...continuousAutoplayConfig };
        },
        loopEnabled() {
            return this.carouselProducts.length > 1;
        },
        loopAdditionalSlides() {
            return Math.min(Math.max(this.carouselProducts.length, 4), 12);
        },
        carouselProducts() {
            if (!this.products.length) {
                return [];
            }
            if (!this.autoScrollEnabled) {
                return this.products;
            }
            return duplicateMarqueeSlides(this.products, 8);
        },
        skeletonCount() {
            return 4;
        },
        swiperKey() {
            return [
                this.productSlug,
                this.autoScrollEnabled ? 1 : 0,
                this.touchEnabled ? 1 : 0,
                this.scrollSpeed,
                this.configuredDirection,
                this.products.length,
            ].join("-");
        },
    },
    watch: {
        productSlug() {
            this.resetAndObserve();
        },
        scrollSpeed() {
            this.applyMarqueeSettings();
        },
        configuredDirection() {
            this.marqueeDirection = this.configuredDirection;
            this.applyMarqueeSettings();
        },
        autoScrollEnabled() {
            this.applyMarqueeSettings();
        },
    },
    mounted() {
        if (!this.sectionEnabled) {
            return;
        }
        this.marqueeDirection = this.configuredDirection;
        this.setupObserver();
    },
    beforeUnmount() {
        this.teardownObserver();
        destroyRelatedMarqueeSwiper(this.swiperInstance);
        if (this.swiperInstance && !this.swiperInstance.destroyed) {
            try {
                this.swiperInstance.destroy(true, true);
            } catch (e) {}
        }
        this.swiperInstance = null;
    },
    methods: {
        setupObserver() {
            this.teardownObserver();
            if (typeof IntersectionObserver === "undefined") {
                this.fetchProducts();
                return;
            }
            this.observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            this.fetchProducts();
                            this.teardownObserver();
                        }
                    });
                },
                { rootMargin: "200px 0px", threshold: 0.01 }
            );
            this.$nextTick(() => {
                if (this.$refs.sentinel) {
                    this.observer.observe(this.$refs.sentinel);
                }
            });
        },
        teardownObserver() {
            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }
        },
        resetAndObserve() {
            this.loaded = false;
            this.products = [];
            destroyRelatedMarqueeSwiper(this.swiperInstance);
            this.swiperInstance = null;
            if (this.sectionEnabled) {
                this.setupObserver();
            }
        },
        fetchProducts() {
            if (this.loaded || this.loading || !this.productSlug) {
                return;
            }
            this.loading = true;
            this.$store
                .dispatch("frontendProduct/relatedProducts", {
                    slug: this.productSlug,
                    limit: 20,
                })
                .then((res) => {
                    this.products = res.data.data || [];
                    this.loaded = true;
                    this.loading = false;
                    this.$nextTick(() => this.bootstrapMarquee());
                })
                .catch(() => {
                    this.products = [];
                    this.loaded = true;
                    this.loading = false;
                });
        },
        onSwiper(swiper) {
            this.swiperInstance = swiper;
            this.bootstrapMarquee();
        },
        bootstrapMarquee() {
            if (!this.swiperInstance || this.swiperInstance.destroyed) {
                return;
            }
            applyMarqueeLinearMotion(this.swiperInstance);
            if (this.autoScrollEnabled) {
                configureRelatedMarqueeSwiper(this.swiperInstance, {
                    speed: this.scrollSpeed,
                    direction: this.marqueeDirection,
                });
                if (!this.swiperInstance.autoplay?.running) {
                    this.swiperInstance.autoplay?.start();
                }
            }
        },
        applyMarqueeSettings() {
            if (!this.autoScrollEnabled || !this.swiperInstance || this.swiperInstance.destroyed) {
                return;
            }
            configureRelatedMarqueeSwiper(this.swiperInstance, {
                speed: this.scrollSpeed,
                direction: this.marqueeDirection,
            });
        },
        onManualStart() {
            if (!this.autoScrollEnabled || !this.touchEnabled) {
                return;
            }
            pauseRelatedMarqueeTouch(this.swiperInstance);
        },
        onManualEnd() {
            if (!this.autoScrollEnabled || !this.touchEnabled || !this.swiperInstance) {
                return;
            }
            const swipeDirection = detectMarqueeDirectionFromTouch(this.swiperInstance);
            if (swipeDirection) {
                this.marqueeDirection = swipeDirection;
            }
            resumeRelatedMarqueeTouch(this.swiperInstance, {
                speed: this.scrollSpeed,
                direction: this.marqueeDirection,
            });
        },
        onTransitionEnd() {
            if (!this.autoScrollEnabled || !this.swiperInstance) {
                return;
            }
            applyMarqueeLinearMotion(this.swiperInstance);
        },
    },
};
</script>

<style scoped>
.related-products-swiper--marquee :deep(.swiper-wrapper) {
    transition-timing-function: linear !important;
}

.related-products-swiper :deep(.swiper-slide) {
    height: auto;
    overflow: hidden;
}

.related-products-swiper :deep(.swiper-button-next),
.related-products-swiper :deep(.swiper-button-prev) {
    color: #ff5c00 !important;
    background: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    top: 40% !important;
}

.related-products-swiper :deep(.swiper-button-next):after,
.related-products-swiper :deep(.swiper-button-prev):after {
    font-size: 18px;
    font-weight: bold;
}

.related-products-swiper :deep(.swiper-button-disabled) {
    opacity: 0;
    cursor: auto;
    pointer-events: none;
}
</style>
