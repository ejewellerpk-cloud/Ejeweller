<template>
    <div ref="lazySection" class="relative min-h-[50px]">
        <LoadingComponent v-if="loading.isActive" :props="loading" :is-full-screen="false" />
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

                <Swiper dir="ltr" :speed="600" :space-between="16" :breakpoints="breakpoints" class="reviews-swiper">
                    <SwiperSlide v-for="review in reviews" :key="review.id">
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
import { Swiper, SwiperSlide } from 'swiper/vue';
import starRating from 'vue-star-rating';
import LoadingComponent from '../components/LoadingComponent';

export default {
    name: 'HomeReviewsComponent',
    components: { Swiper, SwiperSlide, starRating, LoadingComponent },
    data() {
        return {
            loading: { isActive: false },
            reviews: [],
            stats: { total: 0, average: 0 },
            breakpoints: {
                0: { slidesPerView: 1.1, spaceBetween: 12 },
                640: { slidesPerView: 2, spaceBetween: 16 },
                1024: { slidesPerView: 3, spaceBetween: 20 },
            },
        };
    },
    mounted() {
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                this.load();
                observer.disconnect();
            }
        }, { rootMargin: '100px' });
        if (this.$refs.lazySection) {
            observer.observe(this.$refs.lazySection);
        }
    },
    methods: {
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
            }).catch(() => {
                this.loading.isActive = false;
            });
        },
    },
};
</script>
