<template>
    <section class="skeleton-section py-4" aria-hidden="true">
        <div class="container space-y-4">
            <SkeletonBone width="35%" height="1.75rem" rounded="pill" class="mb-2" />
            <div v-for="n in count" :key="n" class="rounded-2xl border border-gray-100 p-4 sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <SkeletonBone width="8rem" height="1rem" rounded="pill" />
                    <SkeletonBone width="5rem" height="1.5rem" rounded="pill" />
                </div>
                <div class="flex gap-4">
                    <SkeletonBone width="4rem" height="4rem" rounded="lg" class="shrink-0" />
                    <div class="flex-1 space-y-2">
                        <SkeletonBone width="75%" height="0.875rem" />
                        <SkeletonBone width="45%" height="0.75rem" rounded="pill" />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import SkeletonBone from './SkeletonBone.vue';

export default {
    name: 'OrderListSkeleton',
    components: { SkeletonBone },
    props: {
        count: { type: Number, default: 4 },
    },
};
</script>
