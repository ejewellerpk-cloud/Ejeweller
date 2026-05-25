import axios from 'axios';

/** Coalesce duplicate API bursts (e.g. Vue strict-mode double mount). */
let fetchSitesInflight = null;
let refreshAllInflight = null;
let refreshAllKey = '';

function isHtmlApiBody(data) {
    return typeof data === 'string' && /^\s*<!DOCTYPE/i.test(data);
}

function parseResponse(res, label) {
    const body = res?.data;
    if (isHtmlApiBody(body)) {
        throw new Error(
            'Server returned the storefront page instead of JSON. Deploy routes/api.php (intelligence routes) and run: php artisan route:clear'
        );
    }
    if (!body?.success) {
        const msg = body?.message || body?.error;
        throw new Error(msg || `Failed to load ${label}`);
    }
    return body.data ?? [];
}

function apiErrorMessage(err, fallback) {
    const status = err.response?.status;
    const msg = err.response?.data?.message || err.message || fallback;
    if (status === 400) {
        return `${msg} (Check VITE_API_KEY in .env matches the site license key.)`;
    }
    if (status === 401) {
        return `${msg} (Please log in again.)`;
    }
    if (status === 404) {
        return `${msg} (Deploy latest code — analytics API route missing.)`;
    }
    return status ? `${msg} (HTTP ${status})` : msg;
}

export const intelligence = {
    namespaced: true,
    state: {
        sites: [],
        activeSiteId: null,
        overview: null,
        funnel: [],
        sources: [],
        products: [],
        realtime: null,
        filters: {
            from: '',
            to: '',
        },
        loading: false,
        lastError: null,
    },
    getters: {
        sites: (s) => s.sites,
        activeSiteId: (s) => s.activeSiteId,
        overview: (s) => s.overview,
        funnel: (s) => s.funnel,
        sources: (s) => s.sources,
        products: (s) => s.products,
        realtime: (s) => s.realtime,
        filters: (s) => s.filters,
        loading: (s) => s.loading,
        lastError: (s) => s.lastError,
    },
    actions: {
        async fetchSites({ commit }) {
            if (fetchSitesInflight) {
                return fetchSitesInflight;
            }

            fetchSitesInflight = (async () => {
                let res;
                try {
                    res = await axios.get('admin/intelligence/sites');
                } catch (err) {
                    throw new Error(apiErrorMessage(err, 'Failed to load sites'));
                }
                const sites = parseResponse(res, 'sites');
                const meta = res.data.meta || {};
                commit('setSites', sites);
                commit('setFilters', {
                    from: meta.default_from || '',
                    to: meta.default_to || meta.server_today || '',
                });
                const defaultId = Number(meta.default_site_id || sites[0]?.id || 0);
                if (defaultId) {
                    commit('setActiveSiteId', defaultId);
                }
                return { sites, meta };
            })();

            try {
                return await fetchSitesInflight;
            } finally {
                fetchSitesInflight = null;
            }
        },
        async fetchOverview({ commit, state }) {
            if (!state.activeSiteId) {
                return;
            }
            const params = {
                site_id: state.activeSiteId,
                from: state.filters.from,
                to: state.filters.to,
            };
            commit('setLoading', true);
            commit('setLastError', null);
            try {
                const res = await axios.get('admin/intelligence/overview', { params });
                const data = parseResponse(res, 'overview');
                if (res.data?.warning) {
                    commit('setLastError', res.data.warning);
                }
                commit('setOverview', data);
                if (data?.realtime) {
                    commit('setRealtime', data.realtime);
                }
            } catch (e) {
                commit('setLastError', e.message || 'Overview failed');
                throw e;
            } finally {
                commit('setLoading', false);
            }
        },
        async fetchRealtime({ commit, state }) {
            if (!state.activeSiteId) return;
            const params = { site_id: state.activeSiteId };
            try {
                const res = await axios.get('admin/intelligence/realtime', { params });
                const data = parseResponse(res, 'realtime');
                commit('setRealtime', data);
            } catch (e) {
                commit('setLastError', e.message || 'Realtime failed');
            }
        },
        async fetchFunnel({ commit, state }) {
            if (!state.activeSiteId) return;
            const params = {
                site_id: state.activeSiteId,
                from: state.filters.from,
                to: state.filters.to,
            };
            try {
                const res = await axios.get('admin/intelligence/funnel', { params });
                const data = parseResponse(res, 'funnel') || [];
                commit('setFunnel', data);
            } catch (e) {
                throw e;
            }
        },
        async fetchSources({ commit, state }) {
            if (!state.activeSiteId) return;
            const params = {
                site_id: state.activeSiteId,
                from: state.filters.from,
                to: state.filters.to,
            };
            try {
                const res = await axios.get('admin/intelligence/sources', { params });
                const data = parseResponse(res, 'sources') || [];
                commit('setSources', data);
            } catch (e) {
                throw e;
            }
        },
        async fetchProducts({ commit, state }) {
            if (!state.activeSiteId) return;
            const params = {
                site_id: state.activeSiteId,
                from: state.filters.from,
                to: state.filters.to,
            };
            try {
                const res = await axios.get('admin/intelligence/products', { params });
                const data = parseResponse(res, 'products') || [];
                commit('setProducts', data);
            } catch (e) {
                throw e;
            }
        },
        async refreshAll({ dispatch, commit, state }) {
            const key = `${state.activeSiteId}|${state.filters.from}|${state.filters.to}`;
            if (refreshAllInflight && refreshAllKey === key) {
                return refreshAllInflight;
            }
            refreshAllKey = key;

            refreshAllInflight = (async () => {
                const results = await Promise.allSettled([
                    dispatch('fetchOverview'),
                    dispatch('fetchFunnel'),
                    dispatch('fetchSources'),
                    dispatch('fetchProducts'),
                ]);
                const failures = results.filter((r) => r.status === 'rejected');
                if (failures.length === results.length) {
                    throw failures[0].reason;
                }
                if (failures.length > 0) {
                    const msg = failures[0].reason?.message || 'Some metrics failed to load';
                    commit('setLastError', msg);
                }
            })();

            try {
                return await refreshAllInflight;
            } finally {
                refreshAllInflight = null;
            }
        },
    },
    mutations: {
        setSites(state, sites) {
            state.sites = sites;
        },
        setActiveSiteId(state, id) {
            state.activeSiteId = Number(id) || null;
        },
        setOverview(state, data) {
            state.overview = data;
        },
        setRealtime(state, data) {
            state.realtime = data;
        },
        setFunnel(state, data) {
            state.funnel = data;
        },
        setSources(state, data) {
            state.sources = data;
        },
        setProducts(state, data) {
            state.products = data;
        },
        setFilters(state, filters) {
            state.filters = { ...state.filters, ...filters };
        },
        setLoading(state, v) {
            state.loading = v;
        },
        setLastError(state, msg) {
            state.lastError = msg;
        },
    },
};
