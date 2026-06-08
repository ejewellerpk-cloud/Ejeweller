<template>
    <div class="related-products-slider">
        <div
            v-if="carouselEnabled && slideCount > 2"
            class="related-products-slider__fade related-products-slider__fade--left"
            aria-hidden="true"
        ></div>
        <div
            v-if="carouselEnabled && slideCount > 2"
            class="related-products-slider__fade related-products-slider__fade--right"
            aria-hidden="true"
        ></div>

        <div
            v-if="!carouselEnabled"
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
            :key="swiperKey"
            dir="ltr"
            v-bind="touchProps"
            :modules="modules"
            :slides-per-view="2"
            :space-between="12"
            :speed="transitionSpeed"
            :autoplay="autoplayConfig"
            :loop="canLoop"
            :loop-additional-slides="4"
            :navigation="slideCount > 2"
            :breakpoints="breakpoints"
            :grab-cursor="true"
            :watch-overflow="true"
            class="related-products-swiper homepage-touch-swiper !pb-10"
            @swiper="onSwiperReady"
            @touchStart="onManualInteraction"
            @sliderFirstMove="onManualInteraction"
            @touchEnd="onManualInteractionEnd"
            @touchCancel="onManualInteractionEnd"
            @navigationNext="onNavInteraction"
            @navigationPrev="onNavInteraction"
        >
            <SwiperSlide
                v-for="(product, index) in carouselSlides"
                :key="product.id + '-' + index"
            >
                <RelatedProductCard :product="product" />
            </SwiperSlide>
        </Swiper>
    </div>
</template>

<script>
import { Navigation, Autoplay } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';
import 'swiper/css';
import 'swiper/css/navigation';
import RelatedProductCard from './RelatedProductCard.vue';
import activityEnum from '../../../enums/modules/activityEnum';
import { homepageProductRowSwiperProps } from '../../../utils/homepageSwiper';
import {
    RELATED_PRODUCTS_SWIPER_SPEED,
    RELATED_PRODUCTS_AUTOPLAY_DELAY,
    relatedProductsAutoplayConfig,
    pauseRelatedProductsSwiper,
    resumeRelatedProductsSwiper,
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
            modules: [Navigation, Autoplay],
            touchProps: homepageProductRowSwiperProps,
            transitionSpeed: RELATED_PRODUCTS_SWIPER_SPEED,
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
        autoplayDelay() {
            const speed = Number(this.setting.related_products_carousel_speed);
            if (!speed || Number.isNaN(speed)) {
                return RELATED_PRODUCTS_AUTOPLAY_DELAY;
            }
            return Math.min(10000, Math.max(2000, speed));
        },
        autoplayConfig() {
            if (!this.carouselEnabled || this.slideCount < 3) {
                return false;
            }
            return {
                ...relatedProductsAutoplayConfig,
                delay: this.autoplayDelay,
            };
        },
        slideCount() {
            return (this.products || []).length;
        },
        carouselSlides() {
            const items = this.products || [];
            if (!items.length) {
                return [];
            }
            if (items.length >= 8) {
                return items;
            }
            let out = [];
            const target = Math.max(8, items.length * 2);
            while (out.length < target) {
                out = out.concat(items);
            }
            return out;
        },
        canLoop() {
            return this.carouselEnabled && this.carouselSlides.length > 4;
        },
        swiperKey() {
            return `related-${this.slideCount}-${this.autoplayDelay}-${this.carouselEnabled ? 1 : 0}`;
        },
    },
    beforeUnmount() {
        if (this.relatedSwiper?._relatedResumeTimer) {
            clearTimeout(this.relatedSwiper._relatedResumeTimer);
            this.relatedSwiper._relatedResumeTimer = null;
        }
        this.relatedSwiper = null;
    },
    methods: {
        onSwiperReady(swiper) {
            this.relatedSwiper = swiper;
            if (this.autoplayConfig) {
                this.$nextTick(() => resumeRelatedProductsSwiper(swiper, 400));
            }
        },
        onManualInteraction() {
            pauseRelatedProductsSwiper(this.relatedSwiper);
        },
        onManualInteractionEnd() {
            if (!this.autoplayConfig) {
                return;
            }
            resumeRelatedProductsSwiper(this.relatedSwiper, 2200);
        },
        onNavInteraction() {
            pauseRelatedProductsSwiper(this.relatedSwiper);
            this.onManualInteractionEnd();
        },
    },
};
</script>

<style scoped>
.related-products-slider {
    position: relative;
}

.related-products-slider__fade {
    position: absolute;
    top: 0;
    bottom: 2.5rem;
    width: 2.75rem;
    z-index: 2;
    pointer-events: none;
}

.related-products-slider__fade--left {
    left: 0;
    background: linear-gradient(to right, #ffffff 20%, rgba(255, 255, 255, 0));
}

.related-products-slider__fade--right {
    right: 0;
    background: linear-gradient(to left, #ffffff 20%, rgba(255, 255, 255, 0));
}

.related-products-swiper {
    touch-action: pan-x pan-y pinch-zoom;
    -webkit-overflow-scrolling: touch;
    overflow: hidden;
}

.related-products-swiper :deep(.swiper-button-next),
.related-products-swiper :deep(.swiper-button-prev) {
    color: #ff5c00 !important;
    background: #ffffff;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
    top: 38% !important;
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.related-products-swiper :deep(.swiper-button-next):hover,
.related-products-swiper :deep(.swiper-button-prev):hover {
    transform: scale(1.05);
}

.related-products-swiper :deep(.swiper-button-next):after,
.related-products-swiper :deep(.swiper-button-prev):after {
    font-size: 16px;
    font-weight: bold;
}

.related-products-swiper :deep(.swiper-button-disabled) {
    opacity: 0;
    pointer-events: none;
}

.related-products-swiper :deep(.swiper-slide) {
    height: auto;
}

@media (max-width: 639px) {
    .related-products-slider__fade {
        width: 1.25rem;
    }

    .related-products-swiper :deep(.swiper-button-next),
    .related-products-swiper :deep(.swiper-button-prev) {
        display: none;
    }
}
</style>
