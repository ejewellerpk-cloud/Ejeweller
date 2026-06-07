<template>
    <section class="skeleton-section mb-10 sm:mb-12" aria-hidden="true">
        <div class="container">
            <SkeletonBone
                rounded="lg"
                height="0"
                class="w-full aspect-[1689/600] !h-auto !min-h-0 block border border-gray-100"
            />
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
