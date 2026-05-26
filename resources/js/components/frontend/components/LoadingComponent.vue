<template>
    <transition name="skeleton-fade" mode="out-in">
        <component
            v-if="props.isActive && skeletonComponent"
            :is="skeletonComponent"
            v-bind="skeletonProps"
            role="status"
            aria-live="polite"
            aria-busy="true"
            :aria-label="ariaLabel"
        />
    </transition>
</template>

<script>
import {
    SKELETON_REGISTRY,
    DEFAULT_INLINE_SKELETON,
    DEFAULT_FULLSCREEN_SKELETON,
} from './skeleton/skeletonRegistry';

export default {
    name: 'LoadingComponent',
    props: {
        props: {
            type: Object,
            required: true,
        },
        isFullScreen: {
            type: Boolean,
            default: true,
        },
        /** Skeleton layout key — see skeletonRegistry.js */
        skeleton: {
            type: String,
            default: '',
        },
        skeletonCount: {
            type: Number,
            default: 8,
        },
        skeletonShowHeader: {
            type: Boolean,
            default: true,
        },
        skeletonColumns: {
            type: String,
            default: 'default',
        },
    },
    computed: {
        resolvedSkeleton() {
            if (this.skeleton) {
                return this.skeleton;
            }
            return this.isFullScreen ? DEFAULT_FULLSCREEN_SKELETON : DEFAULT_INLINE_SKELETON;
        },
        skeletonComponent() {
            return SKELETON_REGISTRY[this.resolvedSkeleton] || SKELETON_REGISTRY.page;
        },
        skeletonProps() {
            const type = this.resolvedSkeleton;
            if (type === 'products' || type === 'product-grid') {
                return {
                    count: this.skeletonCount,
                    showHeader: this.skeletonShowHeader,
                    columns: this.skeletonColumns,
                };
            }
            if (type === 'order-list') {
                return { count: this.skeletonCount };
            }
            if (type === 'categories' || type === 'brands' || type === 'promotions') {
                return { count: this.skeletonCount };
            }
            return {};
        },
        ariaLabel() {
            return this.$t?.('label.loading') || 'Loading';
        },
    },
};
</script>
