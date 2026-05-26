<template>
    <div class="relative">
        <LoadingComponent v-if="loading.isActive" :props="loading" :is-full-screen="false" skeleton="promotions" :skeleton-count="3" />
        <section v-if="promotions.length > 0" class="mb-10 sm:mb-20">
            <div class="container">
                <Swiper dir="ltr" :speed="1000" class="ad-swiper" :breakpoints="breakpoints">
                    <SwiperSlide v-for="promotion in promotions" class="mobile:!w-52">
                        <router-link :to="{name: 'frontend.promotion.products', params: { slug: promotion.slug }}" class=" w-full">
                            <img class="w-full block rounded-2xl" :src="promotion.cover" alt="promotion">
                        </router-link>
                    </SwiperSlide>
                </Swiper>
            </div>
        </section>
    </div>
</template>

<script>

import statusEnum from "../../../enums/modules/statusEnum";
import {Swiper, SwiperSlide} from 'swiper/vue';
import promotionTypeEnum from "../../../enums/modules/promotionTypeEnum";
import LoadingComponent from "../components/LoadingComponent";

import frontendSectionFetch from '../../../mixins/frontendSectionFetch';

export default {
    name: "PromotionComponent",
    mixins: [frontendSectionFetch],
    components: {
        Swiper,
        SwiperSlide,
        LoadingComponent
    },
    data() {
        return {
            loading: {
                isActive: false,
            },
            breakpoints: {
                0: {slidesPerView: 'auto', spaceBetween: 16},
                640: {slidesPerView: 3, spaceBetween: 24},
            }
        }
    },
    computed: {
        promotions: function () {
            return this.$store.getters["frontendPromotion/lists"];
        },
    },
    methods: {
        fetchData() {
            // Only fetch if empty to utilize cache
            if (this.promotions.length > 0) return;
            
            this.loading.isActive = true;
            this.$store.dispatch("frontendPromotion/lists", {
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