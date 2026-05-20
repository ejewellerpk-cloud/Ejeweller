<template>
    <LoadingComponent :props="loading" />
    <section class="mb-10 sm:mb-20">
        <div class="container">
            <Swiper
                v-if="sliders.length > 0"
                dir="ltr"
                :slides-per-view="1"
                :speed="1000"
                :loop="true"
                :navigation="true"
                :pagination="{ clickable: true }"
                :autoplay="{ delay: 2500 }"
                :modules="modules"
                class="banner-swiper"
            >
                <SwiperSlide v-for="slider in sliders" :key="slider.id">
                    <router-link v-if="slider.link" :to="slider.link">
                        <img class="w-full rounded-2xl" :src="slider.image" alt="banner" >
                    </router-link>
                    <div v-else>
                        <img class="w-full rounded-2xl" :src="slider.image" alt="banner" >
                    </div>
                </SwiperSlide>
            </Swiper>
        </div>
    </section>
</template>

<script>
import 'swiper/css';
import {Navigation, Pagination, Autoplay} from 'swiper/modules';
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
            modules: [Navigation, Pagination, Autoplay],
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
        this.loading.isActive = true;
        this.$store.dispatch("frontendSlider/lists", this.sliderProps.search).then((res) => {
            this.loading.isActive = false;
        }).catch((err) => {
            this.loading.isActive = false;
        });
    }
}
</script>

<style scoped>
.banner-swiper :deep(.swiper-pagination) {
    bottom: 20px !important;
}
.banner-swiper :deep(.swiper-pagination-bullet) {
    background: #ff5c00 !important;
    opacity: 0.3;
    width: 12px;
    height: 12px;
    margin: 0 5px !important;
}
.banner-swiper :deep(.swiper-pagination-bullet-active) {
    opacity: 1;
    width: 30px;
    border-radius: 6px;
    background: #ff5c00 !important;
}
.banner-swiper :deep(.swiper-button-next),
.banner-swiper :deep(.swiper-button-prev) {
    color: #ff5c00 !important;
    background: white;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.banner-swiper :deep(.swiper-button-next):after,
.banner-swiper :deep(.swiper-button-prev):after {
    font-size: 18px;
    font-weight: bold;
}
</style>
