<template>
    <div class="relative">
        <LoadingComponent v-if="loading.isActive" :props="loading" :is-full-screen="false" skeleton="products" :skeleton-count="4" />

        <div class="p-0 m-0" v-if="productSections.length > 0 && promotions.length > 0"
            v-for="(productSection, key) in productSections">
            <section class="mb-10 sm:mb-20" v-if="productSection.products.length > 0">
                <div class="container">
                    <div class="flex items-center justify-between gap-4 mb-5 sm:mb-7">
                        <h2 class="text-2xl sm:text-4xl font-bold capitalize">
                            {{ productSection.name }}
                        </h2>
                        <router-link
                            :to="{ name: 'frontend.productSection.products', params: { slug: productSection.slug } }"
                            class="py-2 px-4 text-sm sm:py-3 sm:px-6 rounded-3xl capitalize sm:text-base font-semibold whitespace-nowrap bg-primary-slate text-primary transition-all duration-300 hover:bg-primary hover:text-white">
                            {{ $t('label.show_more') }}
                        </router-link>
                    </div>
                    
                    <div class="product-section-slider-container relative">
                        <Swiper
                            v-bind="productRowTouch"
                            :dir="'ltr'"
                            :slides-per-view="2"
                            :space-between="6"
                            :speed="rowSpeed"
                            :navigation="true"
                            :modules="modules"
                            :breakpoints="{
                                '640': { slidesPerView: 2, spaceBetween: 20 },
                                '768': { slidesPerView: 3, spaceBetween: 24 },
                                '1024': { slidesPerView: 4, spaceBetween: 24 }
                            }"
                            class="product-section-swiper homepage-touch-swiper !pb-10"
                        >
                            <SwiperSlide v-for="product in productSection.products" :key="product.id">
                                <ProductListComponent :products="[product]" />
                            </SwiperSlide>
                        </Swiper>
                    </div>
                </div>
            </section>

            <div v-for="(promotion, promotionKey) in promotions" class="p-0 m-0">
                <section v-if="key === promotionKey" class="mb-10 sm:mb-20">
                    <div class="container">
                        <router-link :to="{ name: 'frontend.promotion.products', params: { slug: promotion.slug } }">
                            <img v-if="promotion.preview && !promotion.preview.includes('default/promotion')" class="w-full rounded-3xl" :src="promotion.preview" alt="promotion" loading="lazy"
                                @error="$event.target.src=$store.getters['frontendSetting/lists'].theme_logo; $event.target.classList.remove('w-full', 'rounded-3xl'); $event.target.classList.add('w-1/2', 'mx-auto', 'object-contain', 'opacity-40')">
                            <div v-else class="w-full rounded-3xl flex items-center justify-center bg-gray-50/50 py-10">
                                <img :src="$store.getters['frontendSetting/lists'].theme_logo" alt="logo" loading="lazy" class="w-1/4 object-contain opacity-40">
                            </div>
                        </router-link>
                    </div>
                </section>
            </div>
        </div>

        <div class="p-0 m-0" v-else-if="productSections.length > 0" v-for="productSection in productSections">
            <section class="mb-10 sm:mb-20" v-if="productSection.products.length > 0">
                <div class="container">
                    <div class="flex items-center justify-between gap-4 mb-5 sm:mb-7">
                        <h2 class="text-2xl sm:text-4xl font-bold capitalize">
                            {{ productSection.name }}
                        </h2>
                        <router-link
                            :to="{ name: 'frontend.productSection.products', params: { slug: productSection.slug } }"
                            class="py-2 px-4 text-sm sm:py-3 sm:px-6 rounded-3xl capitalize sm:text-base font-semibold whitespace-nowrap bg-primary-slate text-primary transition-all duration-300 hover:bg-primary hover:text-white">
                            {{ $t('label.show_more') }}
                        </router-link>
                    </div>
                    
                    <div class="product-section-slider-container relative">
                        <Swiper
                            v-bind="productRowTouch"
                            :dir="'ltr'"
                            :slides-per-view="2"
                            :space-between="6"
                            :speed="rowSpeed"
                            :navigation="true"
                            :modules="modules"
                            :breakpoints="{
                                '640': { slidesPerView: 2, spaceBetween: 20 },
                                '768': { slidesPerView: 3, spaceBetween: 24 },
                                '1024': { slidesPerView: 4, spaceBetween: 24 }
                            }"
                            class="product-section-swiper homepage-touch-swiper !pb-10"
                        >
                            <SwiperSlide v-for="product in productSection.products" :key="product.id">
                                <ProductListComponent :products="[product]" />
                            </SwiperSlide>
                        </Swiper>
                    </div>
                </div>
            </section>
        </div>

        <div class="p-0 m-0" v-else-if="promotions.length > 0">
            <section v-for="promotion in promotions" class="mb-10 sm:mb-20">
                <div class="container">
                    <router-link :to="{ name: 'frontend.promotion.products', params: { slug: promotion.slug } }">
                        <img v-if="promotion.preview && !promotion.preview.includes('default/promotion')" class="w-full rounded-3xl" :src="promotion.preview" alt="promotion" loading="lazy"
                            @error="$event.target.src=$store.getters['frontendSetting/lists'].theme_logo; $event.target.classList.remove('w-full', 'rounded-3xl'); $event.target.classList.add('w-1/2', 'mx-auto', 'object-contain', 'opacity-40')">
                        <div v-else class="w-full rounded-3xl flex items-center justify-center bg-gray-50/50 py-10">
                            <img :src="$store.getters['frontendSetting/lists'].theme_logo" alt="logo" loading="lazy" class="w-1/4 object-contain opacity-40">
                        </div>
                    </router-link>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
import 'swiper/css';
import 'swiper/css/navigation';
import { Navigation } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';
import LoadingComponent from "../components/LoadingComponent.vue";
import promotionTypeEnum from "../../../enums/modules/promotionTypeEnum";
import statusEnum from "../../../enums/modules/statusEnum";
import ProductListComponent from "../components/ProductListComponent.vue";
import { homepageProductRowSwiperProps, HOMEPAGE_ROW_SWIPER_SPEED } from '../../../utils/homepageSwiper';

import frontendSectionFetch from '../../../mixins/frontendSectionFetch';

export default {
    name: "ProductSectionComponent",
    mixins: [frontendSectionFetch],
    components: {
        ProductListComponent,
        LoadingComponent,
        Swiper,
        SwiperSlide
    },
    setup() {
        return {
            modules: [Navigation],
            productRowTouch: homepageProductRowSwiperProps,
            rowSpeed: HOMEPAGE_ROW_SWIPER_SPEED,
        };
    },
    data() {
        return {
            loading: {
                isActive: false,
            },
            promotions: []
        }
    },
    computed: {
        productSections: function () {
            return this.$store.getters["frontendProductSection/lists"];
        },
    },
    methods: {
        fetchData() {
            if (this.productSections.length > 0 && this.promotions.length > 0) return;

            this.loading.isActive = true;
            this.$store.dispatch("frontendProductSection/lists").then(res => {
                this.loading.isActive = false;
            }).catch((err) => {
                this.loading.isActive = false;
            });

            this.$store.dispatch("frontendPromotion/lists", {
                paginate: 0,
                order_column: "id",
                order_type: "asc",
                type: promotionTypeEnum.BIG,
                status: statusEnum.ACTIVE,
                vuex: false
            }).then(res => {
                this.promotions = res.data.data;
            }).catch((err) => { });
        }
    }
}
</script>

<style scoped>
.product-section-swiper :deep(.swiper-button-next),
.product-section-swiper :deep(.swiper-button-prev) {
    color: #ff5c00 !important;
    background: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    top: 40% !important;
}

.product-section-swiper :deep(.swiper-button-next):after,
.product-section-swiper :deep(.swiper-button-prev):after {
    font-size: 18px;
    font-weight: bold;
}

.product-section-swiper :deep(.swiper-button-disabled) {
    opacity: 0;
    cursor: auto;
    pointer-events: none;
}

.product-section-swiper :deep(.swiper-slide) {
    z-index: 0 !important;
    height: auto;
    overflow: hidden;
}
</style>