<template>
    <section class="skeleton-section sm:mb-10" aria-hidden="true">
        <div class="container">
            <SkeletonBone width="14rem" height="2rem" rounded="pill" class="mb-6 sm:mb-8 sm:!w-72 sm:!h-10" />
            <div class="flex gap-4 sm:gap-6 overflow-hidden">
                <div v-for="n in count" :key="n" class="shrink-0 w-20 sm:w-24 flex flex-col items-center gap-2 sm:gap-3">
                    <SkeletonBone rounded="lg" width="100%" height="0" class="w-full aspect-square !h-auto !min-h-0" />
                    <SkeletonBone width="80%" height="0.75rem" rounded="pill" />
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import SkeletonBone from './SkeletonBone.vue';

export default {
    name: 'CategoryCarouselSkeleton',
    components: { SkeletonBone },
    props: {
        count: { type: Number, default: 6 },
    },
};
</script>
