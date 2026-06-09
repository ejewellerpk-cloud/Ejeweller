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

            <div v-else ref="sliderViewport" class="product-section-slider-container relative">
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
                    :looped-slides="loopedSlidesCount"
                    :watch-slides-progress="autoScrollEnabled"
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
                    @sliderFirstMove="onManualStart"
                    @touchEnd="onManualEnd"
                    @touchCancel="onManualEnd"
                    @transitionEnd="onTransitionEnd"
                    @loopFix="onLoopFix"
                    @mouseenter="onHoverEnter"
                    @mouseleave="onHoverLeave"
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
    ensureMarqueeAutoplayRunning,
    MARQUEE_MAX_VISIBLE_SLIDES,
    MARQUEE_RESUME_DELAY_MS,
    marqueeMinSlideCount,
    pauseRelatedMarqueeHover,
    pauseRelatedMarqueeTouch,
    pauseRelatedMarqueeVisibility,
    resumeRelatedMarqueeHover,
    resumeRelatedMarqueeTouch,
    resumeRelatedMarqueeVisibility,
    supportsHoverPause,
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
            visibilityObserver: null,
            marqueeDirection: "forward",
            hoverPauseSupported: false,
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
            return this.autoScrollEnabled
                ? this.carouselProducts.length > 1
                : this.products.length > 1;
        },
        loopAdditionalSlides() {
            return this.carouselProducts.length;
        },
        loopedSlidesCount() {
            return Math.max(this.products.length, MARQUEE_MAX_VISIBLE_SLIDES);
        },
        carouselProducts() {
            if (!this.products.length) {
                return [];
            }
            if (!this.autoScrollEnabled) {
                return this.products;
            }
            return duplicateMarqueeSlides(
                this.products,
                marqueeMinSlideCount(this.products.length)
            );
        },
        marqueeCanRun() {
            return this.autoScrollEnabled && this.products.length > 1;
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
                this.carouselProducts.length,
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
        this.hoverPauseSupported = supportsHoverPause();
        this.setupObserver();
    },
    beforeUnmount() {
        this.teardownObserver();
        this.teardownVisibilityObserver();
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
            this.teardownVisibilityObserver();
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
                    this.$nextTick(() => {
                        this.bootstrapMarquee();
                        this.setupVisibilityObserver();
                    });
                })
                .catch(() => {
                    this.products = [];
                    this.loaded = true;
                    this.loading = false;
                });
        },
        setupVisibilityObserver() {
            this.teardownVisibilityObserver();
            if (!this.marqueeCanRun || typeof IntersectionObserver === "undefined") {
                return;
            }
            this.visibilityObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (!this.swiperInstance || this.swiperInstance.destroyed) {
                            return;
                        }
                        if (entry.isIntersecting) {
                            resumeRelatedMarqueeVisibility(this.swiperInstance, {
                                speed: this.scrollSpeed,
                                direction: this.marqueeDirection,
                            });
                        } else {
                            pauseRelatedMarqueeVisibility(this.swiperInstance);
                        }
                    });
                },
                { threshold: 0.15 }
            );
            this.$nextTick(() => {
                if (this.$refs.sliderViewport) {
                    this.visibilityObserver.observe(this.$refs.sliderViewport);
                }
            });
        },
        teardownVisibilityObserver() {
            if (this.visibilityObserver) {
                this.visibilityObserver.disconnect();
                this.visibilityObserver = null;
            }
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
            if (this.marqueeCanRun) {
                configureRelatedMarqueeSwiper(this.swiperInstance, {
                    speed: this.scrollSpeed,
                    direction: this.marqueeDirection,
                });
                ensureMarqueeAutoplayRunning(this.swiperInstance);
            }
        },
        applyMarqueeSettings() {
            if (!this.marqueeCanRun || !this.swiperInstance || this.swiperInstance.destroyed) {
                return;
            }
            configureRelatedMarqueeSwiper(this.swiperInstance, {
                speed: this.scrollSpeed,
                direction: this.marqueeDirection,
            });
        },
        onManualStart() {
            if (!this.marqueeCanRun || !this.touchEnabled) {
                return;
            }
            pauseRelatedMarqueeTouch(this.swiperInstance);
        },
        onManualEnd() {
            if (!this.marqueeCanRun || !this.touchEnabled || !this.swiperInstance?._marqueeTouchActive) {
                return;
            }
            const swipeDirection = detectMarqueeDirectionFromTouch(this.swiperInstance);
            if (swipeDirection) {
                this.marqueeDirection = swipeDirection;
            }
            resumeRelatedMarqueeTouch(this.swiperInstance, {
                speed: this.scrollSpeed,
                direction: this.marqueeDirection,
                delayMs: MARQUEE_RESUME_DELAY_MS,
            });
        },
        onHoverEnter() {
            if (!this.marqueeCanRun || !this.hoverPauseSupported) {
                return;
            }
            pauseRelatedMarqueeHover(this.swiperInstance);
        },
        onHoverLeave() {
            if (!this.marqueeCanRun || !this.hoverPauseSupported) {
                return;
            }
            resumeRelatedMarqueeHover(this.swiperInstance);
        },
        onLoopFix() {
            if (!this.marqueeCanRun || !this.swiperInstance) {
                return;
            }
            applyMarqueeLinearMotion(this.swiperInstance);
            ensureMarqueeAutoplayRunning(this.swiperInstance);
        },
        onTransitionEnd() {
            if (!this.marqueeCanRun || !this.swiperInstance) {
                return;
            }
            applyMarqueeLinearMotion(this.swiperInstance);
            ensureMarqueeAutoplayRunning(this.swiperInstance);
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
