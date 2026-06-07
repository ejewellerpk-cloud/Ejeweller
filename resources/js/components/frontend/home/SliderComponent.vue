<template>
    <LoadingComponent v-if="showSkeleton" :props="loading" :is-full-screen="false" skeleton="hero" />
    <section v-else-if="sliders.length > 0" class="hero-banner-section mb-10 sm:mb-12">
        <div class="container">
            <div class="hero-banner-shell overflow-hidden rounded-2xl border border-gray-100 bg-gray-50">
                <Swiper
                    dir="ltr"
                    v-bind="heroTouch"
                    :slides-per-view="1"
                    :speed="heroSpeed"
                    :loop="sliders.length > 1"
                    effect="fade"
                    :fadeEffect="{ crossFade: true }"
                    :navigation="sliders.length > 1"
                    :pagination="sliders.length > 1 ? { clickable: true } : false"
                    :autoplay="sliders.length > 1 ? { delay: 4000, disableOnInteraction: false } : false"
                    :modules="modules"
                    class="banner-swiper homepage-touch-swiper"
                >
                    <SwiperSlide v-for="(slider, index) in sliders" :key="slider.id">
                        <router-link v-if="slider.link" :to="slider.link" class="block w-full hero-banner-frame">
                            <img
                                class="hero-banner-image w-full h-full object-cover block"
                                :src="slider.image"
                                :alt="slider.title || 'banner'"
                                v-bind="heroImageAttrs(index)"
                            >
                        </router-link>
                        <div v-else class="w-full hero-banner-frame">
                            <img
                                class="hero-banner-image w-full h-full object-cover block"
                                :src="slider.image"
                                :alt="slider.title || 'banner'"
                                v-bind="heroImageAttrs(index)"
                            >
                        </div>
                    </SwiperSlide>
                </Swiper>
            </div>
        </div>
    </section>
</template>

<script>
import 'swiper/css';
import 'swiper/css/effect-fade';
import {Navigation, Pagination, Autoplay, EffectFade} from 'swiper/modules';
import {Swiper, SwiperSlide} from 'swiper/vue';
import statusEnum from "../../../enums/modules/statusEnum";
import LoadingComponent from "../components/LoadingComponent";
import { homepageHeroSwiperProps, HOMEPAGE_HERO_SWIPER_SPEED } from '../../../utils/homepageSwiper';

export default {
    name: "SliderComponent",
    components: {
        Swiper,
        SwiperSlide,
        LoadingComponent
    },
    setup() {
        return {
            modules: [Navigation, Pagination, Autoplay, EffectFade],
            heroTouch: homepageHeroSwiperProps,
            heroSpeed: HOMEPAGE_HERO_SWIPER_SPEED,
        }
    },
    data() {
        return {
            loading: {
                isActive: false
            },
            sliderProps: {
                search: {
                    paginate: 0,
                    order_column: 'id',
                    order_type: 'desc',
                    status: statusEnum.ACTIVE
                }
            }
        }
    },
    computed: {
        sliders: function () {
            return this.$store.getters['frontendSlider/lists'];
        },
        showSkeleton: function () {
            return this.loading.isActive && this.sliders.length === 0;
        },
    },
    created() {
        if (this.sliders.length > 0) {
            return;
        }
        this.loading.isActive = true;
        this.$store.dispatch("frontendSlider/lists", this.sliderProps.search).then(() => {
            this.loading.isActive = false;
        }).catch(() => {
            this.loading.isActive = false;
        });
    },
    methods: {
        heroImageAttrs(index) {
            if (index === 0) {
                return {
                    loading: 'eager',
                    fetchpriority: 'high',
                    decoding: 'async',
                    width: 1689,
                    height: 600,
                };
            }
            return {
                loading: 'lazy',
                decoding: 'async',
                width: 1689,
                height: 600,
            };
        },
    },
}
</script>

<style scoped>
.hero-banner-frame {
    aspect-ratio: 1689 / 600;
    width: 100%;
    overflow: hidden;
}

.hero-banner-image {
    display: block;
}

.banner-swiper :deep(.swiper-pagination) {
    bottom: 1rem !important;
}

@media (min-width: 640px) {
    .banner-swiper :deep(.swiper-pagination) {
        bottom: 1.25rem !important;
    }
}
</style>
