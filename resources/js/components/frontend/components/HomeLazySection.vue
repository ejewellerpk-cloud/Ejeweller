<template>
    <div ref="root" class="home-lazy-section" :style="placeholderStyle">
        <slot v-if="visible" />
    </div>
</template>

<script>
import { observeOnce } from '../../../utils/lazySectionObserver';

export default {
    name: 'HomeLazySection',
    props: {
        minHeight: {
            type: [Number, String],
            default: 1,
        },
        rootMargin: {
            type: String,
            default: '320px 0px',
        },
    },
    data() {
        return {
            visible: false,
        };
    },
    computed: {
        placeholderStyle() {
            if (this.visible) {
                return undefined;
            }

            const height = typeof this.minHeight === 'number'
                ? `${this.minHeight}px`
                : this.minHeight;

            return { minHeight: height };
        },
    },
    mounted() {
        this._stopObserving = observeOnce(
            this.$refs.root,
            () => {
                this.visible = true;
            },
            { rootMargin: this.rootMargin },
        );
    },
    beforeUnmount() {
        this._stopObserving?.();
    },
};
</script>

<style scoped>
.home-lazy-section {
    contain: layout style;
}
</style>
