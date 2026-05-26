<template>
    <div class="flex gap-2.5 overflow-hidden pb-2 skeleton-section" aria-hidden="true">
        <div v-for="n in count" :key="n" class="flex-shrink-0 w-[100px]">
            <SkeletonBone rounded="lg" class="w-[100px] h-[100px] mb-1.5 block" />
            <SkeletonBone width="90%" height="0.6875rem" rounded="pill" class="mb-1" />
            <SkeletonBone width="55%" height="0.6875rem" rounded="pill" />
        </div>
    </div>
</template>

<script>
import SkeletonBone from './SkeletonBone.vue';

export default {
    name: 'RecentlyViewedStripSkeleton',
    components: { SkeletonBone },
    props: {
        count: { type: Number, default: 6 },
    },
};
</script>
