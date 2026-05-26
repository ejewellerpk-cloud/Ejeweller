<template>
    <div class="product-section-slider-container relative skeleton-section" aria-hidden="true">
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6 !pb-10">
            <ProductCardSkeleton v-for="n in count" :key="n" />
        </div>
    </div>
</template>

<script>
import ProductCardSkeleton from './ProductCardSkeleton.vue';

export default {
    name: 'RelatedProductsSliderSkeleton',
    components: { ProductCardSkeleton },
    props: {
        count: { type: Number, default: 4 },
    },
};
</script>
