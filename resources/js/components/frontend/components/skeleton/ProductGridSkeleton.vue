<template>
    <section class="skeleton-section mb-10 sm:mb-20" aria-hidden="true">
        <div class="container">
            <div v-if="showHeader" class="flex items-center justify-between gap-4 mb-5 sm:mb-7">
                <SkeletonBone width="12rem" height="2rem" rounded="pill" class="sm:!w-64 sm:!h-10" />
                <SkeletonBone width="6.5rem" height="2.5rem" rounded="pill" class="hidden sm:block" />
            </div>
            <div :class="gridClass">
                <ProductCardSkeleton v-for="n in count" :key="n" />
            </div>
        </div>
    </section>
</template>

<script>
import SkeletonBone from './SkeletonBone.vue';
import ProductCardSkeleton from './ProductCardSkeleton.vue';

export default {
    name: 'ProductGridSkeleton',
    components: { SkeletonBone, ProductCardSkeleton },
    props: {
        count: { type: Number, default: 8 },
        showHeader: { type: Boolean, default: true },
        columns: { type: String, default: 'default' },
    },
    computed: {
        gridClass() {
            if (this.columns === 'shop') {
                return 'grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6';
            }
            return 'grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-1.5 sm:gap-6';
        },
    },
};
</script>
