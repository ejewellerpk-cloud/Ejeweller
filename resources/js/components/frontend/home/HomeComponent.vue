<template>
    <div class="homepage-root relative" :class="homepageThemeClass">
        <!-- Eager hero — LCP must not wait on async chunk -->
        <SliderComponent />
        <!-- Categories sit under hero — mount immediately for Shopify-like first paint -->
        <CategoryComponent />
        <DeferUntilVisible class="homepage-deferred-section" min-height="120px" root-margin="320px 0px">
            <PromotionComponent />
        </DeferUntilVisible>
        <DeferUntilVisible class="homepage-deferred-section" min-height="280px" root-margin="280px 0px">
            <ProductSectionComponent />
        </DeferUntilVisible>
        <DeferUntilVisible class="homepage-deferred-section" min-height="280px" root-margin="240px 0px">
            <MostPopularComponent />
        </DeferUntilVisible>
        <DeferUntilVisible class="homepage-deferred-section" min-height="200px" root-margin="200px 0px">
            <FlashSaleComponent />
        </DeferUntilVisible>
        <DeferUntilVisible class="homepage-deferred-section" min-height="160px" root-margin="160px 0px">
            <ProductBrandComponent />
        </DeferUntilVisible>
        <DeferUntilVisible class="homepage-deferred-section" min-height="160px" root-margin="120px 0px">
            <OutletComponent />
        </DeferUntilVisible>
        <DeferUntilVisible class="homepage-deferred-section" min-height="200px" root-margin="120px 0px">
            <HomeReviewsComponent />
        </DeferUntilVisible>
        <DeferUntilVisible class="homepage-deferred-section" min-height="120px" root-margin="80px 0px">
            <BenefitComponent />
        </DeferUntilVisible>
    </div>
</template>

<script>
import { defineAsyncComponent } from "vue";
import SliderComponent from "./SliderComponent.vue";
import CategoryComponent from "./CategoryComponent.vue";
import DeferUntilVisible from "../components/DeferUntilVisible.vue";
import statusEnum from "../../../enums/modules/statusEnum";

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
    created() {
        this.prefetchCriticalHomeData();
        this.warmBelowFoldChunks();
    },
    methods: {
        /** Fire critical APIs in parallel before deferred sections mount. */
        prefetchCriticalHomeData() {
            const store = this.$store;
            if (!store.getters["frontendProductCategory/lists"]?.length) {
                store.dispatch("frontendProductCategory/lists", {
                    paginate: 0,
                    order_column: "id",
                    order_type: "asc",
                    parent_id: null,
                    status: statusEnum.ACTIVE,
                }).catch(() => {});
            }
            if (!store.getters["frontendProductSection/lists"]?.length) {
                store.dispatch("frontendProductSection/lists").catch(() => {});
            }
            if (!store.getters["frontendPromotion/lists"]?.length) {
                store.dispatch("frontendPromotion/lists", {
                    paginate: 0,
                    order_column: "id",
                    order_type: "asc",
                    status: statusEnum.ACTIVE,
                }).catch(() => {});
            }
        },
        warmBelowFoldChunks() {
            const warm = () => {
                import("./PromotionComponent.vue");
                import("./ProductSectionComponent.vue");
                import("./MostPopularComponent.vue");
            };
            if (typeof requestIdleCallback === "function") {
                requestIdleCallback(warm, { timeout: 1200 });
            } else {
                setTimeout(warm, 250);
            }
        },
    },
};
</script>

<style scoped>
.homepage-root {
    min-height: 1px;
}
</style>
