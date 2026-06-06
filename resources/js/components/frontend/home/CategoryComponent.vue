<template>
    <LoadingComponent v-if="loading.isActive" :props="loading" :is-full-screen="false" skeleton="categories" :skeleton-count="6" />
    <section v-else-if="categories.length > 0" class="sm:mb-10">
        <div class="container">
            <h2 class="text-2xl sm:text-4xl font-bold -mb-10">{{ $t('label.browse_by_categories')}}</h2>
            <Swiper dir="ltr" v-bind="rowTouch" :speed="rowSpeed" :loop="true" :navigation="true" :modules="modules" class="navigate-swiper homepage-touch-swiper" :breakpoints="breakpoints">
                <SwiperSlide v-for="category in categories" class="mobile:!w-24">
                    <router-link :to="{name: 'frontend.product', query:{ category: category.slug}}"
                                 class="w-full flex flex-col items-center gap-2 sm:gap-3 group">
                        <div class="w-full aspect-square rounded-2xl overflow-hidden bg-[#fafafa] border border-gray-100 transition-all duration-300 group-hover:shadow-[0_4px_15px_rgba(0,0,0,0.05)] group-hover:border-primary/20">
                            <img v-if="category.thumb && !category.thumb.includes('default/category')" class="w-full h-full object-cover block transition-transform duration-500 group-hover:scale-[1.05]" :src="category.thumb" alt="category" loading="lazy"
                                @error="$event.target.src=$store.getters['frontendSetting/lists'].theme_logo; $event.target.classList.remove('object-cover'); $event.target.classList.add('object-contain', 'bg-white', 'p-4')">
                            <div v-else class="w-full h-full flex items-center justify-center bg-gray-50/50">
                                <img :src="$store.getters['frontendSetting/lists'].theme_logo" alt="logo" loading="lazy" class="w-1/2 h-1/2 object-contain opacity-40">
                            </div>
                        </div>
                        <span class="text-xs sm:text-sm md:text-base font-bold capitalize text-center leading-snug group-hover:text-primary transition-colors px-1 whitespace-normal">
                            {{ category.name }}
                        </span>
                    </router-link>
                </SwiperSlide>
            </Swiper>
        </div>
    </section>
</template>

<script>

import {Navigation, Pagination, Autoplay} from 'swiper/modules';
import {Swiper, SwiperSlide} from 'swiper/vue';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import statusEnum from "../../../enums/modules/statusEnum";
import LoadingComponent from "../components/LoadingComponent";
import { homepageRowSwiperProps, HOMEPAGE_ROW_SWIPER_SPEED } from '../../../utils/homepageSwiper';
import frontendSectionFetch from '../../../mixins/frontendSectionFetch';


export default {
    name: "CategoryComponent",
    mixins: [frontendSectionFetch],
    components: {
        Swiper,
        SwiperSlide,
        LoadingComponent
    },
    setup() {
        return {
            modules: [Navigation, Pagination, Autoplay],
            rowTouch: homepageRowSwiperProps,
            rowSpeed: HOMEPAGE_ROW_SWIPER_SPEED,
        }
    },
    data() {
        return {
            loading: {
                isActive: false,
            },
            settings: {
                itemsToShow: 6,
                wrapAround: false,
                snapAlign: "start"
            },
            breakpoints: {
                0: {slidesPerView: 'auto', spaceBetween: 16},
                640: {slidesPerView: 4, spaceBetween: 24},
                768: {slidesPerView: 5, spaceBetween: 24},
                1024: {slidesPerView: 6, spaceBetween: 24}
            },
        }
    },
    computed: {
        categories: function () {
            return this.$store.getters["frontendProductCategory/lists"];
        },
    },
    methods: {
        fetchData() {
            if (this.categories.length > 0) {
                return;
            }

            this.loading.isActive = true;
            this.$store.dispatch("frontendProductCategory/lists", {
                paginate: 0,
                order_column: "id",
                order_type: "asc",
                parent_id: null,
                status: statusEnum.ACTIVE,
            }).then(() => {
                this.loading.isActive = false;
            }).catch(() => {
                this.loading.isActive = false;
            });
        },
    },
}
</script>

