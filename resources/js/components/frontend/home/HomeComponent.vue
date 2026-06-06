<template>
    <div class="homepage-root relative" :class="homepageThemeClass">
        <SliderComponent />

        <HomeLazySection :min-height="240" root-margin="480px 0px">
            <CategoryComponent />
        </HomeLazySection>

        <HomeLazySection :min-height="160">
            <PromotionComponent />
        </HomeLazySection>

        <HomeLazySection :min-height="380">
            <ProductSectionComponent />
        </HomeLazySection>

        <HomeLazySection :min-height="380">
            <MostPopularComponent />
        </HomeLazySection>

        <HomeLazySection :min-height="380">
            <FlashSaleComponent />
        </HomeLazySection>

        <HomeLazySection :min-height="180">
            <ProductBrandComponent />
        </HomeLazySection>

        <HomeLazySection :min-height="140">
            <HomeWhatsappCtaComponent />
        </HomeLazySection>

        <HomeLazySection :min-height="280">
            <OutletComponent />
        </HomeLazySection>

        <HomeLazySection :min-height="320">
            <HomeReviewsComponent />
        </HomeLazySection>

        <HomeLazySection :min-height="100">
            <BenefitComponent />
        </HomeLazySection>
    </div>
</template>

<script>
import { defineAsyncComponent } from 'vue';
import SliderComponent from './SliderComponent.vue';
import HomeLazySection from '../components/HomeLazySection.vue';

const CategoryComponent = defineAsyncComponent(() => import('./CategoryComponent.vue'));
const PromotionComponent = defineAsyncComponent(() => import('./PromotionComponent.vue'));
const ProductSectionComponent = defineAsyncComponent(() => import('./ProductSectionComponent.vue'));
const MostPopularComponent = defineAsyncComponent(() => import('./MostPopularComponent.vue'));
const FlashSaleComponent = defineAsyncComponent(() => import('./FlashSaleComponent.vue'));
const ProductBrandComponent = defineAsyncComponent(() => import('./ProductBrandComponent.vue'));
const OutletComponent = defineAsyncComponent(() => import('./OutletComponent.vue'));
const BenefitComponent = defineAsyncComponent(() => import('./BenefitComponent.vue'));
const HomeReviewsComponent = defineAsyncComponent(() => import('./HomeReviewsComponent.vue'));
const HomeWhatsappCtaComponent = defineAsyncComponent(() => import('./HomeWhatsappCtaComponent.vue'));

export default {
    name: 'HomeComponent',
    components: {
        SliderComponent,
        HomeLazySection,
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
