<template>
    <div class="homepage-root relative" :class="homepageThemeClass">
        <!-- Instant paint: CSS-only skeletons until staged chunks mount -->
        <div v-if="homeStage < 1" class="home-skeleton home-skeleton--hero" aria-hidden="true"></div>
        <SliderComponent v-if="homeStage >= 1" @ready="onSliderReady" />

        <div v-if="homeStage >= 1 && homeStage < 2" class="home-skeleton home-skeleton--categories" aria-hidden="true"></div>
        <CategoryComponent v-if="homeStage >= 2" @ready="onCategoriesReady" />

        <template v-if="homeStage >= 3">
            <PromotionComponent immediate-fetch />
            <ProductSectionComponent immediate-fetch />
            <MostPopularComponent immediate-fetch />
            <FlashSaleComponent immediate-fetch />
        </template>

        <template v-if="homeStage >= 4">
            <ProductBrandComponent />
            <HomeWhatsappCtaComponent />
            <OutletComponent />
            <HomeReviewsComponent />
            <BenefitComponent />
        </template>
    </div>
</template>

<script>
import {
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
    prefetchHomeChunks,
} from './homeAsyncChunks';

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
    data() {
        return {
            homeStage: 0,
            _stageFallbackTimer: null,
            _bootRaf: null,
        };
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
    mounted() {
        this.startHomePipeline();
    },
    activated() {
        if (this.homeStage < 1) {
            this.startHomePipeline();
        }
    },
    beforeUnmount() {
        this.clearHomeTimers();
    },
    deactivated() {
        this.clearHomeTimers();
    },
    methods: {
        startHomePipeline() {
            this.clearHomeTimers();
            prefetchHomeChunks([
                () => import('./SliderComponent.vue'),
            ]);
            this._bootRaf = requestAnimationFrame(() => {
                this._bootRaf = requestAnimationFrame(() => {
                    if (this.homeStage < 1) {
                        this.homeStage = 1;
                    }
                });
            });
            this._stageFallbackTimer = setTimeout(() => {
                if (this.homeStage < 2) {
                    this.homeStage = 2;
                }
                if (this.homeStage < 3) {
                    this.homeStage = 3;
                }
                if (this.homeStage < 4) {
                    this.homeStage = 4;
                }
            }, 5000);
        },
        clearHomeTimers() {
            if (this._bootRaf) {
                cancelAnimationFrame(this._bootRaf);
                this._bootRaf = null;
            }
            if (this._stageFallbackTimer) {
                clearTimeout(this._stageFallbackTimer);
                this._stageFallbackTimer = null;
            }
        },
        onSliderReady() {
            prefetchHomeChunks([
                () => import('./CategoryComponent.vue'),
            ]);
            if (this.homeStage < 2) {
                this.homeStage = 2;
            }
        },
        onCategoriesReady() {
            prefetchHomeChunks([
                () => import('./PromotionComponent.vue'),
                () => import('./ProductSectionComponent.vue'),
                () => import('./MostPopularComponent.vue'),
                () => import('./FlashSaleComponent.vue'),
            ]);
            if (this.homeStage < 3) {
                this.homeStage = 3;
            }
            this.$nextTick(() => {
                prefetchHomeChunks([
                    () => import('./ProductBrandComponent.vue'),
                    () => import('./HomeWhatsappCtaComponent.vue'),
                    () => import('./OutletComponent.vue'),
                    () => import('./HomeReviewsComponent.vue'),
                    () => import('./BenefitComponent.vue'),
                ]);
                if (this.homeStage < 4) {
                    this.homeStage = 4;
                }
            });
        },
    },
};
</script>

<style>
/* Homepage skeletons (unscoped — shared with async loading placeholders) */
.home-skeleton {
    background: linear-gradient(90deg, #f3f4f6 0%, #e5e7eb 45%, #f3f4f6 90%);
    background-size: 200% 100%;
    animation: home-skeleton-shimmer 1.15s ease-in-out infinite;
    border-radius: 1rem;
}

.home-skeleton--hero {
    width: 100%;
    aspect-ratio: 21 / 9;
    max-height: 420px;
    margin-bottom: 2.5rem;
    border-radius: 0;
}

.home-skeleton--categories {
    width: 100%;
    min-height: 140px;
    margin-bottom: 2.5rem;
}

.home-skeleton--products {
    width: 100%;
    min-height: 280px;
    margin-bottom: 2.5rem;
}

.home-skeleton--strip {
    width: 100%;
    min-height: 72px;
    margin-bottom: 1.5rem;
}

.home-skeleton--reviews {
    width: 100%;
    min-height: 200px;
    margin-bottom: 2.5rem;
}

.home-skeleton--benefits {
    width: 100%;
    min-height: 88px;
    margin-bottom: 1.5rem;
}

@keyframes home-skeleton-shimmer {
    0% {
        background-position: 100% 0;
    }
    100% {
        background-position: -100% 0;
    }
}
</style>

<style scoped>
.homepage-root {
    min-height: 1px;
}
</style>
