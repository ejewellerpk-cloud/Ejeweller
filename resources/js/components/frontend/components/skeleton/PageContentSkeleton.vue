<template>
    <section class="skeleton-section py-8" aria-hidden="true">
        <div class="container max-w-3xl mx-auto space-y-4">
            <SkeletonBone width="55%" height="2rem" rounded="pill" />
            <SkeletonBone width="100%" height="0.875rem" />
            <SkeletonBone width="96%" height="0.875rem" />
            <SkeletonBone width="88%" height="0.875rem" />
            <SkeletonBone width="70%" height="0.875rem" />
        </div>
    </section>
</template>

<script>
import SkeletonBone from './SkeletonBone.vue';

export default {
    name: 'PageContentSkeleton',
    components: { SkeletonBone },
};
</script>
