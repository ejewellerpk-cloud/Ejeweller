<template>
    <div class="p-1 sm:p-1.5 bg-white rounded-2xl border border-gray-100 shadow-sm" aria-hidden="true">
        <SkeletonBone rounded="lg" height="0" class="w-full aspect-[4/5] !h-auto !min-h-0 block" />
        <div class="px-2 pt-3 pb-2 space-y-2">
            <SkeletonBone width="55%" height="1.25rem" rounded="pill" />
            <SkeletonBone width="40%" height="0.875rem" rounded="pill" />
            <div class="flex items-center justify-between pt-1">
                <SkeletonBone width="4.5rem" height="1.125rem" rounded="pill" />
                <SkeletonBone width="2.25rem" height="2.25rem" rounded="full" />
            </div>
        </div>
    </div>
</template>

<script>
import SkeletonBone from './SkeletonBone.vue';

export default {
    name: 'ProductCardSkeleton',
    components: { SkeletonBone },
};
</script>
