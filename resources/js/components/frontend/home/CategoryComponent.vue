<template>
    <LoadingComponent :props="loading" :is-full-screen="false" />
    <section v-if="categories.length > 0" class="sm:mb-10">
        <div class="container">
            <h2 class="text-2xl sm:text-4xl font-bold -mb-10">{{ $t('label.browse_by_categories')}}</h2>
            <Swiper dir="ltr" :speed="1000" :loop="true" :navigation="true" :modules="modules" class="navigate-swiper" :breakpoints="breakpoints">
                <SwiperSlide v-for="category in categories" class="mobile:!w-24">
                    <router-link :to="{name: 'frontend.product', query:{ category: category.slug}}"
                                 class="w-full rounded-2xl shadow-xs group">
                        <img v-if="category.thumb && !category.thumb.includes('default/category')" class="w-full aspect-square object-cover block rounded-tl-2xl rounded-tr-2xl" :src="category.thumb" alt="category" loading="lazy"
                            @error="$event.target.src=$store.getters['frontendSetting/lists'].theme_logo; $event.target.classList.remove('object-cover'); $event.target.classList.add('object-contain', 'bg-white', 'p-4')">
                        <div v-else class="w-full aspect-square flex items-center justify-center bg-gray-50/50 rounded-tl-2xl rounded-tr-2xl">
                            <img :src="$store.getters['frontendSetting/lists'].theme_logo" alt="logo" loading="lazy" class="w-1/2 h-1/2 object-contain opacity-40">
                        </div>
                        <span class="text-sm sm:text-xl font-medium capitalize text-center py-2 px-3 overflow-hidden whitespace-nowrap text-ellipsis block rounded-bl-2xl rounded-br-2xl group-hover:text-primary">
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


export default {
    name: "CategoryComponent",
    components: {
        Swiper,
        SwiperSlide,
        LoadingComponent
    },
    setup() {
        return {
            modules: [Navigation, Pagination, Autoplay],
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
    mounted() {
        this.loading.isActive = true;
        this.$store.dispatch("frontendProductCategory/lists", {
            paginate: 0,
            order_column: "id",
            order_type: "asc",
            parent_id: null,
            status: statusEnum.ACTIVE,
        }).then(res => {
            this.loading.isActive = false;
        }).catch((err) => {
            this.loading.isActive = false;
        });
    },
}
</script>

