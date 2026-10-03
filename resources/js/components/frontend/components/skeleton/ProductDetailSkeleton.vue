<template>
    <section class="skeleton-section mb-12" aria-hidden="true">
        <div class="container">
            <SkeletonBone width="40%" height="0.875rem" rounded="pill" class="mb-6" />
            <div class="grid grid-cols-12 gap-6">
                <div class="col-span-12 sm:col-span-6 lg:col-span-5">
                    <SkeletonBone rounded="lg" height="0" class="w-full aspect-square !h-auto !min-h-0" />
                    <div class="flex gap-2 mt-4">
                        <SkeletonBone v-for="n in 4" :key="n" width="4rem" height="4rem" rounded="md" class="shrink-0" />
                    </div>
                </div>
                <div class="col-span-12 sm:col-span-6 lg:col-span-7 space-y-4 pt-2">
                    <SkeletonBone width="75%" height="1.75rem" rounded="pill" />
                    <SkeletonBone width="35%" height="1.5rem" rounded="pill" />
                    <SkeletonBone width="100%" height="0.875rem" />
                    <SkeletonBone width="92%" height="0.875rem" />
                    <SkeletonBone width="60%" height="0.875rem" />
                    <div class="flex gap-3 pt-4">
                        <SkeletonBone width="8rem" height="3rem" rounded="pill" />
                        <SkeletonBone width="8rem" height="3rem" rounded="pill" />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import SkeletonBone from './SkeletonBone.vue';

export default {
    name: 'ProductDetailSkeleton',
    components: { SkeletonBone },
};
</script>
