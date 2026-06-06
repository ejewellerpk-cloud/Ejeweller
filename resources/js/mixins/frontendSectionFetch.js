/**
 * Defer below-fold homepage section API calls until after first paint.
 */
export default {
    mounted() {
        if (typeof this.fetchData !== 'function') {
            return;
        }

        const runFetch = () => this.fetchData();

        if (typeof requestIdleCallback === 'function') {
            requestIdleCallback(runFetch, { timeout: 1200 });
            return;
        }

        setTimeout(runFetch, 100);
    },
};
