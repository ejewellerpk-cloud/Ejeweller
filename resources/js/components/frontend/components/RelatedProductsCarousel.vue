<template>
    <div
        class="related-products-slider"
        @mouseenter="onHoverEnter"
        @mouseleave="onHoverLeave"
    >
        <div
            v-if="showMarqueeFades"
            class="related-products-slider__fade related-products-slider__fade--left"
            aria-hidden="true"
        ></div>
        <div
            v-if="showMarqueeFades"
            class="related-products-slider__fade related-products-slider__fade--right"
            aria-hidden="true"
        ></div>

        <div
            v-if="showStaticGrid"
            class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-5"
        >
            <RelatedProductCard
                v-for="product in products"
                :key="'static-' + product.id"
                :product="product"
            />
        </div>

        <Swiper
            v-else-if="slideCount > 0"
            :key="marqueeKey"
            dir="ltr"
            v-bind="touchProps"
            :modules="modules"
            :slides-per-view="2"
            :space-between="12"
            :speed="marqueeSpeed"
            :autoplay="marqueeAutoplay"
            :loop="true"
            :loop-additional-slides="8"
            :breakpoints="breakpoints"
            :grab-cursor="true"
            :watch-overflow="true"
            :resistance-ratio="0.82"
            class="related-products-swiper continuous-marquee homepage-touch-swiper"
            @swiper="onSwiperReady"
            @touchStart="onTouchStart"
            @sliderFirstMove="onTouchStart"
            @touchEnd="onTouchEnd"
            @touchCancel="onTouchEnd"
        >
            <SwiperSlide
                v-for="(product, index) in marqueeSlides"
                :key="product.id + '-' + index"
            >
                <RelatedProductCard :product="product" />
            </SwiperSlide>
        </Swiper>
    </div>
</template>

<script>
import { Autoplay } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';
import 'swiper/css';
import RelatedProductCard from './RelatedProductCard.vue';
import activityEnum from '../../../enums/modules/activityEnum';
import { homepageReviewsSwiperProps } from '../../../utils/homepageSwiper';
import {
    MIN_RELATED_MARQUEE_PRODUCTS,
    RELATED_PRODUCTS_DEFAULT_VELOCITY,
    relatedMarqueeAutoplayConfig,
    buildRelatedMarqueeSlides,
    resolveRelatedMarqueeSpeed,
    configureRelatedMarqueeSwiper,
    detectMarqueeDirectionFromTouch,
    pauseRelatedMarqueeTouch,
    resumeRelatedMarqueeTouch,
    pauseRelatedMarqueeHover,
    resumeRelatedMarqueeHover,
    destroyRelatedMarqueeSwiper,
    supportsHoverPause,
} from '../../../utils/continuousSwiper';

export default {
    name: 'RelatedProductsCarousel',
    components: {
        Swiper,
        SwiperSlide,
        RelatedProductCard,
    },
    props: {
        products: {
            type: Array,
            default: () => [],
        },
    },
    setup() {
        return {
            modules: [Autoplay],
            touchProps: homepageReviewsSwiperProps,
            breakpoints: {
                0: { slidesPerView: 2, spaceBetween: 12 },
                640: { slidesPerView: 2, spaceBetween: 16 },
                768: { slidesPerView: 3, spaceBetween: 20 },
                1024: { slidesPerView: 4, spaceBetween: 24 },
            },
        };
    },
    data() {
        return {
            relatedSwiper: null,
            marqueeDirection: 'forward',
            viewportWidth: typeof window !== 'undefined' ? window.innerWidth : 1024,
            hoverPauseEnabled: false,
        };
    },
    computed: {
        setting() {
            return this.$store.getters['frontendSetting/lists'] || {};
        },
        carouselEnabled() {
            const status = this.setting.related_products_carousel_status;
            if (status === undefined || status === null || status === '') {
                return true;
            }
            return Number(status) === activityEnum.ENABLE;
        },
        adminVelocity() {
            const speed = Number(this.setting.related_products_carousel_speed);
            if (!speed || Number.isNaN(speed)) {
                return RELATED_PRODUCTS_DEFAULT_VELOCITY;
            }
            return speed;
        },
        slideCount() {
            return (this.products || []).length;
        },
        showStaticGrid() {
            return !this.carouselEnabled || this.slideCount < MIN_RELATED_MARQUEE_PRODUCTS;
        },
        showMarquee() {
            return this.carouselEnabled && this.slideCount >= MIN_RELATED_MARQUEE_PRODUCTS;
        },
        showMarqueeFades() {
            return this.showMarquee && this.slideCount > 2;
        },
        marqueeSlides() {
            return buildRelatedMarqueeSlides(this.products);
        },
        marqueeSpeed() {
            return resolveRelatedMarqueeSpeed(this.adminVelocity, this.viewportWidth);
        },
        marqueeAutoplay() {
            if (!this.showMarquee) {
                return false;
            }
            return { ...relatedMarqueeAutoplayConfig };
        },
        marqueeKey() {
            const ids = (this.products || []).map((p) => p.id).join('-');
            return `marquee-${ids}`;
        },
    },
    mounted() {
        this.hoverPauseEnabled = supportsHoverPause();
        this.onResize = () => {
            this.viewportWidth = window.innerWidth;
            if (this.relatedSwiper && !this.relatedSwiper.destroyed) {
                this.relatedSwiper.params.speed = this.marqueeSpeed;
                configureRelatedMarqueeSwiper(this.relatedSwiper, {
                    speed: this.marqueeSpeed,
                    direction: this.marqueeDirection,
                });
            }
        };
        window.addEventListener('resize', this.onResize, { passive: true });
    },
    beforeUnmount() {
        window.removeEventListener('resize', this.onResize);
        destroyRelatedMarqueeSwiper(this.relatedSwiper);
        this.relatedSwiper = null;
    },
    methods: {
        onSwiperReady(swiper) {
            this.relatedSwiper = swiper;
            configureRelatedMarqueeSwiper(swiper, {
                speed: this.marqueeSpeed,
                direction: this.marqueeDirection,
            });
            this.$nextTick(() => {
                if (swiper.autoplay && !swiper.destroyed) {
                    swiper.autoplay.start();
                }
            });
        },
        onTouchStart() {
            pauseRelatedMarqueeTouch(this.relatedSwiper);
        },
        onTouchEnd() {
            const detected = detectMarqueeDirectionFromTouch(this.relatedSwiper);
            if (detected) {
                this.marqueeDirection = detected;
            }
            resumeRelatedMarqueeTouch(this.relatedSwiper, {
                speed: this.marqueeSpeed,
                direction: this.marqueeDirection,
                delayMs: 420,
            });
        },
        onHoverEnter() {
            if (!this.hoverPauseEnabled || !this.showMarquee) {
                return;
            }
            pauseRelatedMarqueeHover(this.relatedSwiper);
        },
        onHoverLeave() {
            if (!this.hoverPauseEnabled || !this.showMarquee) {
                return;
            }
            resumeRelatedMarqueeHover(this.relatedSwiper);
        },
    },
};
</script>

<style scoped>
.related-products-slider {
    position: relative;
    overflow: hidden;
}

.related-products-slider__fade {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 3.5rem;
    z-index: 2;
    pointer-events: none;
}

.related-products-slider__fade--left {
    left: 0;
    background: linear-gradient(
        to right,
        #ffffff 0%,
        rgba(255, 255, 255, 0.92) 35%,
        rgba(255, 255, 255, 0) 100%
    );
}

.related-products-slider__fade--right {
    right: 0;
    background: linear-gradient(
        to left,
        #ffffff 0%,
        rgba(255, 255, 255, 0.92) 35%,
        rgba(255, 255, 255, 0) 100%
    );
}

.related-products-swiper {
    touch-action: pan-x pan-y pinch-zoom;
    -webkit-overflow-scrolling: touch;
    overflow: hidden;
}

.continuous-marquee :deep(.swiper-wrapper) {
    transition-timing-function: linear !important;
    will-change: transform;
}

.continuous-marquee :deep(.swiper-slide) {
    height: auto;
    contain: layout style;
}

@media (max-width: 639px) {
    .related-products-slider__fade {
        width: 1.75rem;
    }
}

@media (min-width: 1024px) {
    .related-products-slider__fade {
        width: 4.5rem;
    }
}
</style>
