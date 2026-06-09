<template>
    <div class="product-section-slider-container relative" aria-hidden="true">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-5">
            <ProductCardSkeleton v-for="n in count" :key="n" />
        </div>
    </div>
</template>

<script>
import ProductCardSkeleton from "../components/skeleton/ProductCardSkeleton.vue";

export default {
    name: "RelatedProductsSliderSkeleton",
    components: { ProductCardSkeleton },
    props: {
        count: { type: Number, default: 4 },
    },
};
</script>
