<template>
    <div class="relative">
        <section v-if="loading.isActive" class="mb-10 sm:mb-16" aria-hidden="true">
            <div class="container">
                <div class="home-reviews-card animate-pulse">
                    <div class="h-6 w-28 bg-white/20 rounded-full mx-auto mb-8"></div>
                    <div class="h-24 w-full max-w-md bg-white/15 rounded-2xl mx-auto mb-6"></div>
                    <div class="h-5 w-32 bg-white/20 rounded-full mx-auto"></div>
                </div>
            </div>
        </section>

        <section v-else-if="reviews.length > 0" class="mb-10 sm:mb-16" aria-labelledby="home-reviews-heading">
            <div class="container">
                <div class="home-reviews-card">
                    <h2 id="home-reviews-heading" class="home-reviews-title">
                        {{ $t('label.reviews') }}
                    </h2>

                    <Swiper
                        dir="ltr"
                        :modules="modules"
                        :slides-per-view="1"
                        :space-between="0"
                        :speed="500"
                        :loop="reviews.length > 1"
                        :autoplay="autoplayConfig"
                        :allow-touch-move="true"
                        class="home-reviews-swiper"
                        @swiper="onSwiper"
                        @slideChange="onSlideChange"
                    >
                        <SwiperSlide v-for="review in reviews" :key="review.id">
                            <div class="home-reviews-slide">
                                <div class="home-reviews-quote" aria-hidden="true">"</div>
                                <p class="home-reviews-text">{{ review.review }}</p>
                                <div class="home-reviews-stars" :aria-label="`${review.star} ${$t('label.reviews')}`">
                                    <i
                                        v-for="starIndex in 5"
                                        :key="starIndex"
                                        class="fa-solid fa-star"
                                        :class="starIndex <= review.star ? 'opacity-100' : 'opacity-35'"
                                    ></i>
                                </div>
                                <p class="home-reviews-handle">{{ displayHandle(review) }}</p>
                                <p v-if="displayCity(review)" class="home-reviews-city">{{ displayCity(review) }}</p>
                            </div>
                        </SwiperSlide>
                    </Swiper>

                    <div v-if="reviews.length > 1" class="home-reviews-nav">
                        <button
                            type="button"
                            class="home-reviews-nav-btn"
                            :aria-label="$t('label.previous')"
                            @click="goPrev"
                        >
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <span class="home-reviews-counter">{{ activeIndex }} / {{ reviews.length }}</span>
                        <button
                            type="button"
                            class="home-reviews-nav-btn"
                            :aria-label="$t('label.next')"
                            @click="goNext"
                        >
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script>
import axios from 'axios';
import { Autoplay } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';
import 'swiper/css';
import frontendSectionFetch from '../../../mixins/frontendSectionFetch';

const AUTOPLAY_DELAY_MS = 3000;

export default {
    name: 'HomeReviewsComponent',
    components: { Swiper, SwiperSlide },
    mixins: [frontendSectionFetch],
    setup() {
        return {
            modules: [Autoplay],
            autoplayConfig: {
                delay: AUTOPLAY_DELAY_MS,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
            },
        };
    },
    data() {
        return {
            loading: { isActive: false },
            reviews: [],
            swiper: null,
            activeIndex: 1,
        };
    },
    methods: {
        fetchData() {
            if (this.reviews.length > 0) {
                return;
            }
            this.loading.isActive = true;
            axios
                .get('frontend/featured-reviews')
                .then((res) => {
                    this.reviews = (res.data.data || []).filter((review) => {
                        const star = Number(review.star);
                        return star === 4 || star === 5;
                    });
                    this.loading.isActive = false;
                })
                .catch(() => {
                    this.loading.isActive = false;
                });
        },
        onSwiper(swiper) {
            this.swiper = swiper;
            this.syncActiveIndex(swiper);
        },
        onSlideChange(swiper) {
            this.syncActiveIndex(swiper);
        },
        syncActiveIndex(swiper) {
            if (!swiper) {
                return;
            }
            this.activeIndex = (swiper.realIndex ?? swiper.activeIndex) + 1;
        },
        goPrev() {
            this.swiper?.slidePrev();
        },
        goNext() {
            this.swiper?.slideNext();
        },
        displayHandle(review) {
            const username = (review.username || '').trim();
            if (username) {
                return username.startsWith('@') ? username : `@${username}`;
            }
            const name = (review.name || '').trim();
            if (name) {
                const handle = name.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');
                return handle ? `@${handle}` : '@customer';
            }
            return '@customer';
        },
        displayCity(review) {
            return (review.city || '').trim();
        },
    },
};
</script>

<style scoped>
.home-reviews-card {
    position: relative;
    overflow: hidden;
    border-radius: 1.75rem;
    padding: 2.5rem 1.5rem 2rem;
    color: #fff;
    text-align: center;
    background: linear-gradient(
        180deg,
        rgb(var(--primary) / 1) 0%,
        rgb(var(--primary-light) / 1) 55%,
        rgb(var(--primary) / 0.92) 100%
    );
    box-shadow: 0 18px 40px rgb(var(--primary) / 0.22);
}

@media (min-width: 640px) {
    .home-reviews-card {
        padding: 3rem 2.5rem 2.25rem;
    }
}

.home-reviews-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1.75rem;
}

.home-reviews-slide {
    min-height: 14rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0 0.5rem;
}

.home-reviews-quote {
    font-size: 4.5rem;
    line-height: 1;
    font-weight: 700;
    opacity: 0.35;
    margin-bottom: 0.5rem;
    font-family: Georgia, 'Times New Roman', serif;
}

.home-reviews-text {
    max-width: 34rem;
    font-size: 0.98rem;
    line-height: 1.7;
    margin: 0 auto 1.25rem;
    opacity: 0.98;
}

@media (min-width: 640px) {
    .home-reviews-text {
        font-size: 1.05rem;
    }
}

.home-reviews-stars {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    margin-bottom: 1rem;
    font-size: 1rem;
}

.home-reviews-handle {
    font-size: 1.05rem;
    font-weight: 700;
    margin-bottom: 0.2rem;
}

.home-reviews-city {
    font-size: 0.95rem;
    opacity: 0.9;
}

.home-reviews-nav {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.25rem;
    margin-top: 1.5rem;
}

.home-reviews-nav-btn {
    width: 2rem;
    height: 2rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    opacity: 0.95;
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.home-reviews-nav-btn:hover {
    opacity: 1;
    transform: scale(1.05);
}

.home-reviews-counter {
    min-width: 3.5rem;
    font-size: 0.95rem;
    font-weight: 600;
    letter-spacing: 0.02em;
}

.home-reviews-swiper :deep(.swiper-slide) {
    height: auto;
}
</style>
