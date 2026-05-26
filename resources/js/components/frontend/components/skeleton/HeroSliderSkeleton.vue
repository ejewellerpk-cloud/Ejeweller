<template>
    <section class="skeleton-section mb-10 sm:mb-20 w-full" aria-hidden="true">
        <SkeletonBone rounded="none" height="0" class="w-full aspect-[21/9] max-h-[420px] !h-auto !min-h-0 block" />
        <div class="container flex justify-center gap-2 mt-4">
            <SkeletonBone v-for="n in 4" :key="n" width="0.5rem" height="0.5rem" rounded="full" />
        </div>
    </section>
</template>

<script>
import SkeletonBone from './SkeletonBone.vue';

export default {
    name: 'HeroSliderSkeleton',
    components: { SkeletonBone },
};
</script>
