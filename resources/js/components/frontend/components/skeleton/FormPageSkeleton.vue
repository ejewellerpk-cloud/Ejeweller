<template>
    <section class="skeleton-section py-8 sm:py-12" aria-hidden="true">
        <div class="container max-w-md mx-auto space-y-5">
            <SkeletonBone width="60%" height="2rem" rounded="pill" class="mx-auto" />
            <SkeletonBone width="80%" height="0.875rem" rounded="pill" class="mx-auto" />
            <div class="rounded-2xl border border-gray-100 p-6 sm:p-8 space-y-4 mt-6">
                <SkeletonBone width="100%" height="2.75rem" rounded="lg" />
                <SkeletonBone width="100%" height="2.75rem" rounded="lg" />
                <SkeletonBone width="100%" height="2.75rem" rounded="lg" />
                <SkeletonBone width="100%" height="3rem" rounded="pill" class="mt-2" />
            </div>
        </div>
    </section>
</template>

<script>
import SkeletonBone from './SkeletonBone.vue';

export default {
    name: 'FormPageSkeleton',
    components: { SkeletonBone },
};
</script>
