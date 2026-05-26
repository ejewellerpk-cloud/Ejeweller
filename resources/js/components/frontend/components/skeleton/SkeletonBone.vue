<template>
    <div
        class="skeleton-bone"
        :class="[roundedClass, className]"
        :style="boneStyle"
        aria-hidden="true"
    />
</template>

<script>
export default {
    name: 'SkeletonBone',
    props: {
        width: { type: String, default: '100%' },
        height: { type: String, default: '1rem' },
        rounded: { type: String, default: 'md' },
        className: { type: String, default: '' },
    },
    computed: {
        roundedClass() {
            const map = {
                none: 'skeleton-bone--sharp',
                sm: 'rounded-sm',
                md: 'rounded-xl',
                lg: 'rounded-2xl',
                full: 'skeleton-bone--circle',
                pill: 'skeleton-bone--pill',
            };
            return map[this.rounded] || map.md;
        },
        boneStyle() {
            return {
                width: this.width,
                height: this.height,
                minHeight: this.height,
            };
        },
    },
};
</script>
