import { defineAsyncComponent, h } from 'vue';

/** Minimal placeholder while a homepage section chunk downloads */
export const HomeSectionSkeleton = {
    name: 'HomeSectionSkeleton',
    props: {
        variant: { type: String, default: 'block' },
    },
    render() {
        return h('div', {
            class: ['home-skeleton', `home-skeleton--${this.variant}`],
            'aria-hidden': 'true',
        });
    },
};

export function defineHomeChunk(loader, variant = 'block') {
    return defineAsyncComponent({
        loader,
        loadingComponent: HomeSectionSkeleton,
        loadingComponentProps: { variant },
        delay: 0,
        timeout: 30000,
    });
}

export const SliderComponent = defineHomeChunk(
    () => import('./SliderComponent.vue'),
    'hero'
);

export const CategoryComponent = defineHomeChunk(
    () => import('./CategoryComponent.vue'),
    'categories'
);

export const PromotionComponent = defineHomeChunk(
    () => import('./PromotionComponent.vue'),
    'products'
);

export const ProductSectionComponent = defineHomeChunk(
    () => import('./ProductSectionComponent.vue'),
    'products'
);

export const MostPopularComponent = defineHomeChunk(
    () => import('./MostPopularComponent.vue'),
    'products'
);

export const FlashSaleComponent = defineHomeChunk(
    () => import('./FlashSaleComponent.vue'),
    'products'
);

export const ProductBrandComponent = defineHomeChunk(
    () => import('./ProductBrandComponent.vue'),
    'strip'
);

export const OutletComponent = defineHomeChunk(
    () => import('./OutletComponent.vue'),
    'strip'
);

export const BenefitComponent = defineHomeChunk(
    () => import('./BenefitComponent.vue'),
    'benefits'
);

export const HomeReviewsComponent = defineHomeChunk(
    () => import('./HomeReviewsComponent.vue'),
    'reviews'
);

export const HomeWhatsappCtaComponent = defineHomeChunk(
    () => import('./HomeWhatsappCtaComponent.vue'),
    'strip'
);

/** Prefetch next section chunks during idle time */
export function prefetchHomeChunks(paths) {
    if (typeof window === 'undefined') {
        return;
    }
    const run = () => paths.forEach((load) => load().catch(() => {}));
    if (window.requestIdleCallback) {
        window.requestIdleCallback(run, { timeout: 1500 });
    } else {
        setTimeout(run, 0);
    }
}
