<template>
    <section class="skeleton-section mb-3 sm:mb-10" aria-hidden="true">
        <div class="container">
            <SkeletonBone width="10rem" height="2rem" rounded="pill" class="mb-6 sm:!w-56 sm:!h-10" />
            <div class="flex gap-4 sm:gap-6 overflow-hidden">
                <div v-for="n in count" :key="n" class="shrink-0 w-[120px] rounded-2xl border border-gray-100 p-3">
                    <SkeletonBone width="3.5rem" height="3.5rem" rounded="lg" class="mx-auto mb-3" />
                    <SkeletonBone width="70%" height="0.875rem" rounded="pill" class="mx-auto" />
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import SkeletonBone from './SkeletonBone.vue';

export default {
    name: 'BrandStripSkeleton',
    components: { SkeletonBone },
    props: {
        count: { type: Number, default: 5 },
    },
};
</script>
