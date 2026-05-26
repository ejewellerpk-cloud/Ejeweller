<template>
    <section class="skeleton-section py-6" aria-hidden="true">
        <div class="container grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-7 space-y-4">
                <SkeletonBone width="45%" height="1.5rem" rounded="pill" />
                <div class="rounded-2xl border border-gray-100 p-4 space-y-4">
                    <div v-for="n in 3" :key="n" class="flex gap-4 items-center">
                        <SkeletonBone width="4.5rem" height="4.5rem" rounded="lg" class="shrink-0" />
                        <div class="flex-1 space-y-2">
                            <SkeletonBone width="70%" height="0.875rem" />
                            <SkeletonBone width="40%" height="0.75rem" rounded="pill" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-5">
                <div class="rounded-2xl border border-gray-100 p-5 space-y-3">
                    <SkeletonBone width="50%" height="1.25rem" rounded="pill" />
                    <SkeletonBone width="100%" height="0.875rem" />
                    <SkeletonBone width="100%" height="0.875rem" />
                    <SkeletonBone width="100%" height="3rem" rounded="pill" class="mt-4" />
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import SkeletonBone from './SkeletonBone.vue';

export default {
    name: 'CheckoutPageSkeleton',
    components: { SkeletonBone },
};
</script>
