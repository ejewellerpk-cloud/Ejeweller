<template>
    <section class="skeleton-section mb-6" aria-hidden="true">
        <div class="container">
            <SkeletonBone rounded="lg" height="4.5rem" class="w-full sm:!h-20" />
        </div>
    </section>
</template>

<script>
import SkeletonBone from './SkeletonBone.vue';

export default {
    name: 'StripSkeleton',
    components: { SkeletonBone },
};
</script>
