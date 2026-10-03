<template>
    <section class="skeleton-section mb-10 sm:mb-14" aria-hidden="true">
        <div class="container">
            <SkeletonBone width="40%" height="0.875rem" rounded="pill" class="mb-5" />
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10">
                <div class="lg:col-span-7">
                    <div class="flex flex-col lg:flex-row gap-3">
                        <div class="hidden lg:flex flex-col gap-2 w-[72px] shrink-0">
                            <SkeletonBone v-for="n in 4" :key="'t' + n" width="4.5rem" height="4.5rem" rounded="md" class="shrink-0" />
                        </div>
                        <SkeletonBone rounded="lg" height="0" class="w-full aspect-square !h-auto !min-h-0 flex-1" />
                    </div>
                    <div class="flex lg:hidden gap-2 mt-3">
                        <SkeletonBone v-for="n in 4" :key="'m' + n" width="4rem" height="4rem" rounded="md" class="shrink-0" />
                    </div>
                </div>
                <div class="lg:col-span-5 space-y-4 pt-1">
                    <SkeletonBone width="90%" height="1.75rem" rounded="pill" />
                    <SkeletonBone width="40%" height="0.875rem" rounded="pill" />
                    <SkeletonBone width="45%" height="1.75rem" rounded="pill" />
                    <SkeletonBone width="70%" height="0.75rem" />
                    <SkeletonBone width="100%" height="0.875rem" />
                    <SkeletonBone width="85%" height="0.875rem" />
                    <div class="flex gap-3 pt-3">
                        <SkeletonBone width="100%" height="3rem" rounded="lg" />
                        <SkeletonBone width="7rem" height="3rem" rounded="lg" class="shrink-0" />
                    </div>
                </div>
            </div>
            <div class="mt-10 border-b border-slate-100 pb-3 flex gap-4">
                <SkeletonBone width="5rem" height="1rem" />
                <SkeletonBone width="5rem" height="1rem" />
                <SkeletonBone width="7rem" height="1rem" />
            </div>
            <div class="mt-5 space-y-2 max-w-3xl">
                <SkeletonBone width="100%" height="0.875rem" />
                <SkeletonBone width="95%" height="0.875rem" />
                <SkeletonBone width="80%" height="0.875rem" />
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
