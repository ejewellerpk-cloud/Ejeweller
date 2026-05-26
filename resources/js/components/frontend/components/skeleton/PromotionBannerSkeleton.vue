<template>
    <section class="skeleton-section mb-10 sm:mb-20" aria-hidden="true">
        <div class="container flex gap-4 sm:gap-6 overflow-hidden">
            <SkeletonBone
                v-for="n in count"
                :key="n"
                rounded="lg"
                width="13rem"
                height="8rem"
                class="shrink-0 sm:!w-64 sm:!h-36"
            />
        </div>
    </section>
</template>

<script>
import SkeletonBone from './SkeletonBone.vue';

export default {
    name: 'PromotionBannerSkeleton',
    components: { SkeletonBone },
    props: {
        count: { type: Number, default: 3 },
    },
};
</script>
