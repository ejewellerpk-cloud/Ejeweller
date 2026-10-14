<template>
    <div class="homepage-root relative" :class="homepageThemeClass">
        <SliderComponent />
        <CategoryComponent />
        <DeferUntilVisible min-height="120px">
            <PromotionComponent />
        </DeferUntilVisible>
        <DeferUntilVisible min-height="280px">
            <ProductSectionComponent />
        </DeferUntilVisible>
        <DeferUntilVisible min-height="280px">
            <MostPopularComponent />
        </DeferUntilVisible>
        <DeferUntilVisible min-height="200px">
            <FlashSaleComponent />
        </DeferUntilVisible>
        <DeferUntilVisible min-height="160px">
            <ProductBrandComponent />
        </DeferUntilVisible>
        <DeferUntilVisible min-height="160px">
            <OutletComponent />
        </DeferUntilVisible>
        <DeferUntilVisible min-height="200px">
            <HomeReviewsComponent />
        </DeferUntilVisible>
        <DeferUntilVisible min-height="120px">
            <BenefitComponent />
        </DeferUntilVisible>
    </div>
</template>

<script>
import { defineAsyncComponent } from "vue";
import DeferUntilVisible from "../components/DeferUntilVisible.vue";

const SliderComponent = defineAsyncComponent(() => import("./SliderComponent.vue"));
const CategoryComponent = defineAsyncComponent(() => import("./CategoryComponent.vue"));
const PromotionComponent = defineAsyncComponent(() => import("./PromotionComponent.vue"));
const ProductSectionComponent = defineAsyncComponent(() => import("./ProductSectionComponent.vue"));
const MostPopularComponent = defineAsyncComponent(() => import("./MostPopularComponent.vue"));
const FlashSaleComponent = defineAsyncComponent(() => import("./FlashSaleComponent.vue"));
const ProductBrandComponent = defineAsyncComponent(() => import("./ProductBrandComponent.vue"));
const OutletComponent = defineAsyncComponent(() => import("./OutletComponent.vue"));
const BenefitComponent = defineAsyncComponent(() => import("./BenefitComponent.vue"));
const HomeReviewsComponent = defineAsyncComponent(() => import("./HomeReviewsComponent.vue"));

export default {
    name: "HomeComponent",
    components: {
        SliderComponent,
        CategoryComponent,
        DeferUntilVisible,
        PromotionComponent,
        ProductSectionComponent,
        MostPopularComponent,
        FlashSaleComponent,
        ProductBrandComponent,
        OutletComponent,
        BenefitComponent,
        HomeReviewsComponent,
    },
    computed: {
        setting() {
            return this.$store.getters["frontendSetting/lists"];
        },
        homepageThemeClass() {
            const theme = this.setting?.site_homepage_theme || "default";
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
