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

            <div
                v-else-if="useCssMarquee"
                ref="sliderViewport"
                class="related-marquee-viewport"
                :aria-label="$t('label.related_products')"
                @touchstart.passive="onMarqueeTouchStart"
                @touchend="onMarqueeTouchEnd"
                @touchcancel="onMarqueeTouchEnd"
                @mouseenter="onMarqueeHoverEnter"
                @mouseleave="onMarqueeHoverLeave"
            >
                <div
                    class="related-marquee-track"
                    :class="marqueeTrackClasses"
                    :style="marqueeTrackStyle"
                >
                    <div
                        v-for="copy in 2"
                        :key="'marquee-set-' + copy"
                        class="related-marquee-set"
                        aria-hidden="copy > 1"
                    >
                        <div
                            v-for="(product, index) in products"
                            :key="copy + '-' + product.id + '-' + index"
                            class="related-marquee-item"
                        >
                            <ProductListComponent :products="[product]" />
                        </div>
                    </div>
                </div>
            </div>

            <div v-else ref="sliderViewport" class="product-section-slider-container relative">
                <Swiper
                    :key="swiperKey"
                    dir="ltr"
                    v-bind="swiperTouch"
                    :modules="swiperModules"
                    :slides-per-view="2"
                    :space-between="6"
                    :speed="550"
                    :loop="products.length > 1"
                    :navigation="true"
                    :allow-touch-move="touchEnabled"
                    :simulate-touch="touchEnabled"
                    :grab-cursor="touchEnabled"
                    :breakpoints="breakpoints"
                    class="related-products-swiper homepage-touch-swiper"
                    :aria-label="$t('label.related_products')"
                >
                    <SwiperSlide v-for="product in products" :key="product.id">
                        <ProductListComponent :products="[product]" />
                    </SwiperSlide>
                </Swiper>
            </div>
        </div>
    </section>
</template>

<script>
import { Navigation } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/vue";
import "swiper/css";
import "swiper/css/navigation";
import activityEnum from "../../../enums/modules/activityEnum";
import ProductListComponent from "../components/ProductListComponent.vue";
import RelatedProductsSliderSkeleton from "./RelatedProductsSliderSkeleton.vue";
import { homepageProductRowSwiperProps } from "../../../utils/homepageSwiper";
import { MARQUEE_RESUME_DELAY_MS, supportsHoverPause } from "../../../utils/continuousSwiper";

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
            observer: null,
            visibilityObserver: null,
            resumeTimer: null,
            touchStartX: 0,
            marqueeDirection: "forward",
            marqueeTouchPaused: false,
            marqueeHoverPaused: false,
            marqueeVisibilityPaused: false,
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
        useCssMarquee() {
            return this.autoScrollEnabled && this.products.length > 1;
        },
        swiperModules() {
            return [Navigation];
        },
        swiperTouch() {
            if (!this.touchEnabled) {
                return {
                    allowTouchMove: false,
                    simulateTouch: false,
                };
            }
            return homepageProductRowSwiperProps;
        },
        isMarqueeAnimating() {
            return (
                this.useCssMarquee
                && !this.marqueeTouchPaused
                && !this.marqueeHoverPaused
                && !this.marqueeVisibilityPaused
            );
        },
        marqueeTrackClasses() {
            return {
                "related-marquee-track--reverse": this.marqueeDirection === "reverse",
                "related-marquee-track--paused": !this.isMarqueeAnimating,
            };
        },
        marqueeTrackStyle() {
            const count = Math.max(this.products.length, 1);
            return {
                "--marquee-duration": `${(count * this.scrollSpeed) / 1000}s`,
            };
        },
        skeletonCount() {
            return 4;
        },
        swiperKey() {
            return [this.productSlug, this.touchEnabled ? 1 : 0, this.products.length].join("-");
        },
    },
    watch: {
        productSlug() {
            this.resetAndObserve();
        },
        configuredDirection() {
            this.marqueeDirection = this.configuredDirection;
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
        this.clearResumeTimer();
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
            this.clearResumeTimer();
            this.marqueeTouchPaused = false;
            this.marqueeHoverPaused = false;
            this.marqueeVisibilityPaused = false;
            this.teardownVisibilityObserver();
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
                    limit: 32,
                })
                .then((res) => {
                    this.products = res.data.data || [];
                    this.loaded = true;
                    this.loading = false;
                    this.$nextTick(() => this.setupVisibilityObserver());
                })
                .catch(() => {
                    this.products = [];
                    this.loaded = true;
                    this.loading = false;
                });
        },
        setupVisibilityObserver() {
            this.teardownVisibilityObserver();
            if (!this.useCssMarquee || typeof IntersectionObserver === "undefined") {
                return;
            }
            this.visibilityObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        this.marqueeVisibilityPaused = !entry.isIntersecting;
                    });
                },
                { threshold: 0.1 }
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
        clearResumeTimer() {
            if (this.resumeTimer) {
                clearTimeout(this.resumeTimer);
                this.resumeTimer = null;
            }
        },
        onMarqueeTouchStart(event) {
            if (!this.useCssMarquee || !this.touchEnabled) {
                return;
            }
            this.touchStartX = event.touches?.[0]?.clientX ?? 0;
            this.clearResumeTimer();
            this.marqueeTouchPaused = true;
        },
        onMarqueeTouchEnd(event) {
            if (!this.useCssMarquee || !this.touchEnabled || !this.marqueeTouchPaused) {
                return;
            }
            const endX = event.changedTouches?.[0]?.clientX ?? 0;
            const diff = endX - this.touchStartX;
            if (Math.abs(diff) >= 20) {
                this.marqueeDirection = diff > 0 ? "reverse" : "forward";
            }
            this.clearResumeTimer();
            this.resumeTimer = setTimeout(() => {
                this.marqueeTouchPaused = false;
                this.resumeTimer = null;
            }, MARQUEE_RESUME_DELAY_MS);
        },
        onMarqueeHoverEnter() {
            if (!this.useCssMarquee || !this.hoverPauseSupported) {
                return;
            }
            this.marqueeHoverPaused = true;
        },
        onMarqueeHoverLeave() {
            if (!this.useCssMarquee || !this.hoverPauseSupported) {
                return;
            }
            this.marqueeHoverPaused = false;
        },
    },
};
</script>

<style scoped>
.related-marquee-viewport {
    overflow: hidden;
    width: 100%;
    container-type: inline-size;
}

.related-marquee-track {
    display: flex;
    width: max-content;
    will-change: transform;
    animation: related-marquee-forward var(--marquee-duration, 40s) linear infinite;
}

.related-marquee-track--reverse {
    animation-name: related-marquee-reverse;
}

.related-marquee-track--paused {
    animation-play-state: paused;
}

.related-marquee-set {
    display: flex;
    gap: 6px;
}

.related-marquee-item {
    flex: 0 0 calc((100cqw - 6px) / 2);
    min-width: 0;
}

@container (min-width: 640px) {
    .related-marquee-set {
        gap: 20px;
    }

    .related-marquee-item {
        flex: 0 0 calc((100cqw - 20px) / 2);
    }
}

@container (min-width: 768px) {
    .related-marquee-set {
        gap: 24px;
    }

    .related-marquee-item {
        flex: 0 0 calc((100cqw - 48px) / 3);
    }
}

@container (min-width: 1024px) {
    .related-marquee-item {
        flex: 0 0 calc((100cqw - 72px) / 4);
    }
}

@keyframes related-marquee-forward {
    from {
        transform: translate3d(0, 0, 0);
    }
    to {
        transform: translate3d(-50%, 0, 0);
    }
}

@keyframes related-marquee-reverse {
    from {
        transform: translate3d(-50%, 0, 0);
    }
    to {
        transform: translate3d(0, 0, 0);
    }
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
