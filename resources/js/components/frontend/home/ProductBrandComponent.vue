<template>
    <div class="relative">
        <LoadingComponent v-if="loading.isActive" :props="loading" :is-full-screen="false" skeleton="brands" :skeleton-count="5" />
        <section class="mb-3 sm:mb-10" v-if="brands.length > 0">
            <div class="container">
                <h2 class="capitalize text-2xl sm:text-4xl font-bold -mb-10">
                    {{ $t('label.popular_brands') }}
                </h2>
                <Swiper dir="ltr" v-bind="carouselTouch" :speed="1000" :loop="true" :navigation="true" :modules="modules" class="navigate-swiper" :breakpoints="breakpoints">
                    <SwiperSlide v-for="brand in brands" class="mobile:!w-[120px]">
                        <router-link :to="{name: 'frontend.product', query:{ brand: brand.slug }}" class="w-full rounded-2xl shadow-xs group border border-gray-100">
                            <figure class="w-full h-[120px] flex items-center justify-center">
                                <img v-if="brand.cover && !brand.cover.includes('default/brand')" :src="brand.cover" alt="brand" class="w-14" loading="lazy"
                                    @error="$event.target.src=$store.getters['frontendSetting/lists'].theme_logo; $event.target.classList.remove('w-14'); $event.target.classList.add('w-10', 'opacity-40')">
                                <img v-else :src="$store.getters['frontendSetting/lists'].theme_logo" alt="logo" loading="lazy" class="w-10 object-contain opacity-40">
                            </figure>
                            <span class="text-sm sm:text-lg font-medium capitalize text-center pb-3 block group-hover:text-primary">
                                    {{ brand.name }}
                                </span>
                        </router-link>
                    </SwiperSlide>
                </Swiper>
            </div>
        </section>
    </div>
</template>

<script>
import statusEnum from "../../../enums/modules/statusEnum";
import LoadingComponent from "../components/LoadingComponent";
import {Swiper, SwiperSlide} from "swiper/vue";
import {Autoplay, Navigation, Pagination} from "swiper/modules";
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import { homepageCarouselTouchProps } from '../../../utils/continuousSwiper';

import frontendSectionFetch from '../../../mixins/frontendSectionFetch';

export default {
    name: "ProductBrandComponent",
    mixins: [frontendSectionFetch],
    components: {
        Swiper, SwiperSlide,
        LoadingComponent
    },
    data() {
        return {
            loading: {
                isActive: false,
            },
            breakpoints: {
                0: {slidesPerView: 'auto', spaceBetween: 16},
                640: {slidesPerView: 4, spaceBetween: 24},
                768: {slidesPerView: 5, spaceBetween: 24},
                1024: {slidesPerView: 6, spaceBetween: 24}
            },
        }
    },
    setup() {
        return {
            modules: [Navigation, Pagination, Autoplay],
            carouselTouch: homepageCarouselTouchProps,
        }
    },
    computed: {
        brands: function () {
            return this.$store.getters["frontendProductBrand/lists"];
        },
    },
    methods: {
        fetchData() {
            if (this.brands.length > 0) return;

            this.loading.isActive = true;
            this.$store.dispatch("frontendProductBrand/lists", {
                paginate: 0,
                order_column: "id",
                order_type: "asc",
                status: statusEnum.ACTIVE,
            }).then(res => {
                this.loading.isActive = false;
            }).catch((err) => {
                this.loading.isActive = false;
            });
        }
    }
}
</script>

