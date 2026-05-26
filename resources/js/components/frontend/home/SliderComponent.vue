<template>
    <LoadingComponent v-if="loading.isActive" :props="loading" :is-full-screen="false" skeleton="hero" />
    <section v-else-if="sliders.length > 0" class="mb-10 sm:mb-20 w-full overflow-hidden">
        <Swiper
            dir="ltr"
            :slides-per-view="1"
            :speed="1000"
            :loop="true"
            effect="fade"
            :fadeEffect="{ crossFade: true }"
            :navigation="true"
            :pagination="{ clickable: true }"
            :autoplay="{ delay: 4000, disableOnInteraction: false }"
            :modules="modules"
            class="banner-swiper group"
        >
            <SwiperSlide v-for="slider in sliders" :key="slider.id" class="relative">
                <router-link v-if="slider.link" :to="slider.link" class="block w-full h-full">
                    <img class="w-full h-auto block" :src="slider.image" alt="banner" >
                    <!-- Subtle Dark Gradient Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent pointer-events-none"></div>
                </router-link>
                <div v-else class="w-full h-full relative">
                    <img class="w-full h-auto block" :src="slider.image" alt="banner" >
                    <!-- Subtle Dark Gradient Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent pointer-events-none"></div>
                </div>
            </SwiperSlide>
        </Swiper>
    </section>
</template>

<script>
import 'swiper/css';
import 'swiper/css/effect-fade';
import {Navigation, Pagination, Autoplay, EffectFade} from 'swiper/modules';
import {Swiper, SwiperSlide} from 'swiper/vue';
import statusEnum from "../../../enums/modules/statusEnum";
import LoadingComponent from "../components/LoadingComponent";

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
        }
    },
    mounted() {
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
}
</script>

<style scoped>
.banner-swiper :deep(.swiper-pagination) {
    bottom: 25px !important;
}
.banner-swiper :deep(.swiper-pagination-bullet) {
    background: #ffffff !important;
    opacity: 0.5;
    width: 30px;
    height: 4px;
    border-radius: 2px;
    margin: 0 4px !important;
    transition: all 0.3s ease;
}
.banner-swiper :deep(.swiper-pagination-bullet-active) {
    opacity: 1;
    width: 50px;
    background: #ffffff !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.banner-swiper :deep(.swiper-button-next),
.banner-swiper :deep(.swiper-button-prev) {
    color: white !important;
    width: 50px;
    height: 50px;
    opacity: 0;
    transition: opacity 0.3s ease, transform 0.3s ease;
    text-shadow: 0 2px 10px rgba(0,0,0,0.3);
}
.banner-swiper:hover :deep(.swiper-button-next),
.banner-swiper:hover :deep(.swiper-button-prev) {
    opacity: 1;
}
.banner-swiper :deep(.swiper-button-next):hover,
.banner-swiper :deep(.swiper-button-prev):hover {
    transform: scale(1.1);
}
.banner-swiper :deep(.swiper-button-next):after,
.banner-swiper :deep(.swiper-button-prev):after {
    font-size: 24px;
    font-weight: 300;
}
</style>
