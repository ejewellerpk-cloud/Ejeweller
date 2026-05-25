<template>
    <div class="homepage-root relative" :class="homepageThemeClass">
        <SliderComponent />

        <CategoryComponent />

        <PromotionComponent />

        <ProductSectionComponent />

        <MostPopularComponent />

        <FlashSaleComponent />

        <ProductBrandComponent />

        <HomeWhatsappCtaComponent />

        <OutletComponent />

        <HomeReviewsComponent />

        <BenefitComponent />
    </div>
</template>

<script>
import { defineAsyncComponent } from 'vue';
// Lazy load homepage sections to speed up checkout → home navigation
const SliderComponent = defineAsyncComponent(() => import("./SliderComponent.vue"));
const CategoryComponent = defineAsyncComponent(() => import("./CategoryComponent.vue"));

// Lazy load below-the-fold components to reduce initial JS bundle size
const PromotionComponent = defineAsyncComponent(() => import("./PromotionComponent.vue"));
const ProductSectionComponent = defineAsyncComponent(() => import("./ProductSectionComponent.vue"));
const MostPopularComponent = defineAsyncComponent(() => import("./MostPopularComponent.vue"));
const FlashSaleComponent = defineAsyncComponent(() => import("./FlashSaleComponent.vue"));
const ProductBrandComponent = defineAsyncComponent(() => import("./ProductBrandComponent.vue"));
const OutletComponent = defineAsyncComponent(() => import("./OutletComponent.vue"));
const BenefitComponent = defineAsyncComponent(() => import("./BenefitComponent.vue"));
const HomeReviewsComponent = defineAsyncComponent(() => import("./HomeReviewsComponent.vue"));
const HomeWhatsappCtaComponent = defineAsyncComponent(() => import("./HomeWhatsappCtaComponent.vue"));

export default {
    name: "HomeComponent",
    components: {
        SliderComponent,
        CategoryComponent,
        PromotionComponent,
        ProductSectionComponent,
        MostPopularComponent,
        FlashSaleComponent,
        ProductBrandComponent,
        OutletComponent,
        BenefitComponent,
        HomeReviewsComponent,
        HomeWhatsappCtaComponent,
    },
    computed: {
        setting() {
            return this.$store.getters['frontendSetting/lists'];
        },
        homepageThemeClass() {
            const theme = this.setting?.site_homepage_theme || 'default';
            return `homepage-theme--${theme}`;
        },
    },
};
</script>
