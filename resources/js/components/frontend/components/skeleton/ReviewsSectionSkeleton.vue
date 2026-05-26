<template>
    <section class="skeleton-section mb-10 sm:mb-20" aria-hidden="true">
        <div class="container">
            <SkeletonBone width="11rem" height="2rem" rounded="pill" class="mb-6 sm:!w-48 sm:!h-10" />
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <div v-for="n in 3" :key="n" class="rounded-2xl border border-gray-100 p-5 space-y-3">
                    <div class="flex gap-1">
                        <SkeletonBone v-for="s in 5" :key="s" width="1rem" height="1rem" rounded="sm" />
                    </div>
                    <SkeletonBone width="100%" height="0.875rem" />
                    <SkeletonBone width="85%" height="0.875rem" />
                    <SkeletonBone width="40%" height="0.75rem" rounded="pill" />
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import SkeletonBone from './SkeletonBone.vue';

export default {
    name: 'ReviewsSectionSkeleton',
    components: { SkeletonBone },
};
</script>
