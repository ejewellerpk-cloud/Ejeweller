<template>
    <section class="skeleton-section mb-6 sm:mb-10" aria-hidden="true">
        <div class="container grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
            <div v-for="n in 4" :key="n" class="flex flex-col items-center gap-3 p-4">
                <SkeletonBone width="3rem" height="3rem" rounded="full" />
                <SkeletonBone width="70%" height="0.875rem" rounded="pill" />
                <SkeletonBone width="90%" height="0.625rem" rounded="pill" />
            </div>
        </div>
    </section>
</template>

<script>
import SkeletonBone from './SkeletonBone.vue';

export default {
    name: 'BenefitsStripSkeleton',
    components: { SkeletonBone },
};
</script>
