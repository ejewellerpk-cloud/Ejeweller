/** Coalesce concurrent identical async work into one shared Promise. */
const inflight = new Map();

export function coalesceInflight(key, factory) {
    if (inflight.has(key)) {
        return inflight.get(key);
    }

    const promise = Promise.resolve()
        .then(factory)
        .finally(() => {
            inflight.delete(key);
        });

    inflight.set(key, promise);
    return promise;
}

export function inflateRequestKey(name, payload) {
    try {
        return `${name}:${JSON.stringify(payload ?? null)}`;
    } catch (e) {
        return `${name}:fallback`;
    }
}
