<template>
    <section class="skeleton-section py-6" aria-hidden="true">
        <div class="container">
            <div class="flex flex-col lg:flex-row gap-6">
                <div class="hidden lg:block w-64 shrink-0 space-y-2">
                    <SkeletonBone v-for="n in 6" :key="n" width="100%" height="2.75rem" rounded="lg" />
                </div>
                <div class="flex-1 space-y-4">
                    <SkeletonBone width="40%" height="1.75rem" rounded="pill" />
                    <div class="rounded-2xl border border-gray-100 p-6 space-y-4">
                        <SkeletonBone width="100%" height="2.75rem" rounded="lg" />
                        <SkeletonBone width="100%" height="2.75rem" rounded="lg" />
                        <SkeletonBone width="100%" height="2.75rem" rounded="lg" />
                        <SkeletonBone width="8rem" height="2.75rem" rounded="pill" />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import SkeletonBone from './SkeletonBone.vue';

export default {
    name: 'AccountPageSkeleton',
    components: { SkeletonBone },
};
</script>
