<template>
    <div class="min-h-[60vh] skeleton-section" aria-hidden="true">
        <HeroSliderSkeleton />
        <CategoryCarouselSkeleton />
        <ProductGridSkeleton :count="4" />
    </div>
</template>

<script>
import HeroSliderSkeleton from './HeroSliderSkeleton.vue';
import CategoryCarouselSkeleton from './CategoryCarouselSkeleton.vue';
import ProductGridSkeleton from './ProductGridSkeleton.vue';

export default {
    name: 'AppShellSkeleton',
    components: {
        HeroSliderSkeleton,
        CategoryCarouselSkeleton,
        ProductGridSkeleton,
    },
};
</script>
