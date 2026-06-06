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
import SliderComponent from './SliderComponent.vue';
import CategoryComponent from './CategoryComponent.vue';
import PromotionComponent from './PromotionComponent.vue';
import ProductSectionComponent from './ProductSectionComponent.vue';
import MostPopularComponent from './MostPopularComponent.vue';
import FlashSaleComponent from './FlashSaleComponent.vue';
import ProductBrandComponent from './ProductBrandComponent.vue';
import OutletComponent from './OutletComponent.vue';
import BenefitComponent from './BenefitComponent.vue';
import HomeReviewsComponent from './HomeReviewsComponent.vue';
import HomeWhatsappCtaComponent from './HomeWhatsappCtaComponent.vue';

export default {
    name: 'HomeComponent',
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

<style scoped>
.homepage-root {
    min-height: 1px;
}
</style>
