<template>
    <div class="relative">
        <LoadingComponent v-if="loading.isActive" :props="loading" :is-full-screen="false" skeleton="reviews" />
        <section v-if="reviews.length > 0" class="mb-10 sm:mb-20">
            <div class="container">
                <div class="text-center mb-8 sm:mb-10">
                    <h2 class="capitalize text-2xl sm:text-4xl font-bold text-heading mb-2">
                        {{ $t('label.customer_reviews') }}
                    </h2>
                    <p class="text-sm sm:text-base text-gray-500 max-w-xl mx-auto">
                        {{ $t('message.home_reviews_subtitle') }}
                    </p>
                    <div v-if="stats.total > 0" class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary/10 text-primary font-semibold text-sm">
                        <i class="fa-solid fa-star text-amber-500"></i>
                        <span>{{ stats.average }} / 5</span>
                        <span class="text-gray-500 font-medium">({{ stats.total }}+ {{ $t('label.reviews') }})</span>
                    </div>
                </div>

                <Swiper
                    v-if="carouselReviews.length > 0"
                    dir="ltr"
                    v-bind="carouselTouch"
                    :modules="modules"
                    :slides-per-view="1.1"
                    :space-between="12"
                    :speed="continuousSpeed"
                    :loop="true"
                    :loop-additional-slides="6"
                    :autoplay="reviewsAutoplay"
                    :breakpoints="breakpoints"
                    class="reviews-swiper continuous-slider !pb-2"
                    @swiper="onReviewsSwiper"
                    @touchStart="onReviewsManualStart"
                    @sliderFirstMove="onReviewsManualStart"
                    @touchEnd="onReviewsManualEnd"
                    @touchCancel="onReviewsManualEnd"
                    @slideChangeTransitionEnd="onReviewsManualEnd"
                >
                    <SwiperSlide v-for="(review, idx) in carouselReviews" :key="review.id + '-' + idx">
                        <article class="h-full p-5 sm:p-6 rounded-2xl border border-slate-100 bg-white shadow-sm flex flex-col">
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <h4 class="font-bold text-heading capitalize truncate">{{ review.name }}</h4>
                                <star-rating :read-only="true" :max-rating="5" :rating="review.star" :star-size="16" active-color="#FD8B0E" />
                            </div>
                            <p class="text-sm text-gray-600 leading-relaxed flex-grow line-clamp-4 mb-4">{{ review.review }}</p>
                            <router-link v-if="review.product_slug"
                                :to="{ name: 'frontend.product.details', params: { slug: review.product_slug } }"
                                class="text-sm font-semibold text-primary hover:underline mt-auto">
                                {{ review.product_name }}
                            </router-link>
                        </article>
                    </SwiperSlide>
                </Swiper>
            </div>
        </section>
    </div>
</template>

<script>
import axios from 'axios';
import { Autoplay } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';
import 'swiper/css';
import starRating from 'vue-star-rating';
import LoadingComponent from '../components/LoadingComponent';
import {
    continuousAutoplayConfig,
    CONTINUOUS_SWIPER_SPEED,
    continuousCarouselTouchProps,
    pauseContinuousSwiper,
    resumeContinuousSwiper,
} from '../../../utils/continuousSwiper';

export default {
    name: 'HomeReviewsComponent',
    components: { Swiper, SwiperSlide, starRating, LoadingComponent },
    setup() {
        return {
            modules: [Autoplay],
            carouselTouch: continuousCarouselTouchProps,
        };
    },
    data() {
        return {
            loading: { isActive: false },
            reviews: [],
            stats: { total: 0, average: 0 },
            reviewsSwiper: null,
            continuousSpeed: CONTINUOUS_SWIPER_SPEED,
            reviewsAutoplay: { ...continuousAutoplayConfig },
            breakpoints: {
                0: { slidesPerView: 1.1, spaceBetween: 12 },
                640: { slidesPerView: 2, spaceBetween: 16 },
                1024: { slidesPerView: 3, spaceBetween: 20 },
            },
        };
    },
    computed: {
        carouselReviews() {
            const items = this.reviews || [];
            if (!items.length) {
                return [];
            }
            if (items.length >= 6) {
                return items;
            }
            let out = [];
            while (out.length < 8) {
                out = out.concat(items);
            }
            return out;
        },
    },
    mounted() {
        this.load();
    },
    methods: {
        onReviewsSwiper(swiper) {
            this.reviewsSwiper = swiper;
            this.onReviewsManualEnd();
        },
        onReviewsManualStart() {
            pauseContinuousSwiper(this.reviewsSwiper);
        },
        onReviewsManualEnd() {
            this.$nextTick(() => resumeContinuousSwiper(this.reviewsSwiper));
        },
        load() {
            if (this.reviews.length > 0) {
                return;
            }
            this.loading.isActive = true;
            axios.get('frontend/featured-reviews', { params: { limit: 8 } }).then((res) => {
                const items = res.data.data || [];
                this.reviews = items.map((r) => ({
                    ...r,
                    product_slug: r.product?.slug,
                    product_name: r.product?.name,
                }));
                if (this.reviews.length > 0) {
                    const sum = this.reviews.reduce((acc, r) => acc + (r.star || 0), 0);
                    this.stats = {
                        total: this.reviews.length,
                        average: (sum / this.reviews.length).toFixed(1),
                    };
                }
                this.loading.isActive = false;
                this.$nextTick(() => this.onReviewsManualEnd());
            }).catch((err) => {
                this.loading.isActive = false;
                if (err?.response?.status === 508) {
                    console.warn('[home] featured-reviews: server redirect loop (508) — check SSL/.htaccess on host');
                }
            });
        },
    },
};
</script>

<style scoped>
.continuous-slider :deep(.swiper-wrapper) {
    transition-timing-function: linear !important;
}
.continuous-slider :deep(.swiper-slide) {
    height: auto;
}
</style>
