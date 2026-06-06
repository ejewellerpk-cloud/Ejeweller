/**
 * Fetch section data on mount (replaces IntersectionObserver lazy sections).
 */
export default {
    mounted() {
        if (typeof this.fetchData === 'function') {
            this.fetchData();
        }
    },
};
