/**
 * Fire callback once when element enters (or nears) the viewport.
 */
export function observeOnce(element, callback, options = {}) {
    if (!element || typeof callback !== 'function') {
        return () => {};
    }

    if (typeof IntersectionObserver === 'undefined') {
        callback();
        return () => {};
    }

    let done = false;
    const observer = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (entry.isIntersecting && !done) {
                done = true;
                observer.disconnect();
                callback();
            }
        }
    }, {
        root: null,
        rootMargin: options.rootMargin ?? '320px 0px',
        threshold: options.threshold ?? 0,
    });

    observer.observe(element);

    return () => observer.disconnect();
}
