<template>
    <SliderComponent />

    <CategoryComponent />

    <PromotionComponent />

    <ProductSectionComponent />

    <MostPopularComponent />

    <FlashSaleComponent />

    <ProductBrandComponent />

    <OutletComponent />

    <BenefitComponent />
</template>

<script>
import { defineAsyncComponent } from 'vue';
import SliderComponent from "./SliderComponent.vue";
import CategoryComponent from "./CategoryComponent.vue";

// Lazy load below-the-fold components to reduce initial JS bundle size
const PromotionComponent = defineAsyncComponent(() => import("./PromotionComponent.vue"));
const ProductSectionComponent = defineAsyncComponent(() => import("./ProductSectionComponent.vue"));
const MostPopularComponent = defineAsyncComponent(() => import("./MostPopularComponent.vue"));
const FlashSaleComponent = defineAsyncComponent(() => import("./FlashSaleComponent.vue"));
const ProductBrandComponent = defineAsyncComponent(() => import("./ProductBrandComponent.vue"));
const OutletComponent = defineAsyncComponent(() => import("./OutletComponent.vue"));
const BenefitComponent = defineAsyncComponent(() => import("./BenefitComponent.vue"));

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
        BenefitComponent
    }
};
</script>
