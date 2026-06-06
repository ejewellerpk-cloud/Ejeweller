import { observeOnce } from '../utils/lazySectionObserver';

/**
 * Fetch section data when the component enters (or nears) the viewport.
 * Pair with HomeLazySection on the homepage for deferred mount + API calls.
 */
export default {
    mounted() {
        if (typeof this.fetchData !== 'function') {
            return;
        }

        const runFetch = () => this.fetchData();

        if (typeof IntersectionObserver === 'undefined') {
            runFetch();
            return;
        }

        this._sectionFetchCleanup = observeOnce(this.$el, runFetch, {
            rootMargin: '280px 0px',
        });
    },
    beforeUnmount() {
        this._sectionFetchCleanup?.();
    },
};
