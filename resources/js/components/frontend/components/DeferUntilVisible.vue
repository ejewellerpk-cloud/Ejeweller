<template>
    <div ref="root" class="homepage-deferred-section">
        <slot v-if="visible" />
        <div v-else class="homepage-deferred-placeholder" :style="placeholderStyle" aria-hidden="true"></div>
    </div>
</template>

<script>
export default {
    name: "DeferUntilVisible",
    props: {
        /** px before entering viewport to start mounting */
        rootMargin: {
            type: String,
            default: "200px 0px",
        },
        minHeight: {
            type: String,
            default: "1px",
        },
    },
    data() {
        return {
            visible: typeof IntersectionObserver === "undefined",
            observer: null,
        };
    },
    computed: {
        placeholderStyle() {
            return { minHeight: this.minHeight };
        },
    },
    mounted() {
        if (this.visible) {
            return;
        }
        this.observer = new IntersectionObserver(
            (entries) => {
                if (entries.some((e) => e.isIntersecting)) {
                    this.visible = true;
                    this.teardown();
                }
            },
            { root: null, rootMargin: this.rootMargin, threshold: 0.01 }
        );
        this.observer.observe(this.$refs.root);
    },
    beforeUnmount() {
        this.teardown();
    },
    methods: {
        teardown() {
            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }
        },
    },
};
</script>
