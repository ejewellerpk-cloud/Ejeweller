import axios from 'axios';

function parseResponse(res, label) {
    if (!res?.data?.success) {
        throw new Error(res?.data?.message || `Failed to load ${label}`);
    }
    return res.data.data;
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
            const res = await axios.get('admin/intelligence/sites');
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
        },
        async fetchOverview({ commit, state }) {
            if (!state.activeSiteId) return;
            commit('setLoading', true);
            commit('setLastError', null);
            try {
                const res = await axios.get('admin/intelligence/overview', {
                    params: {
                        site_id: state.activeSiteId,
                        from: state.filters.from,
                        to: state.filters.to,
                    },
                });
                const data = parseResponse(res, 'overview');
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
            try {
                const res = await axios.get('admin/intelligence/realtime', {
                    params: { site_id: state.activeSiteId },
                });
                commit('setRealtime', parseResponse(res, 'realtime'));
            } catch (e) {
                commit('setLastError', e.message || 'Realtime failed');
            }
        },
        async fetchFunnel({ commit, state }) {
            if (!state.activeSiteId) return;
            const res = await axios.get('admin/intelligence/funnel', {
                params: {
                    site_id: state.activeSiteId,
                    from: state.filters.from,
                    to: state.filters.to,
                },
            });
            commit('setFunnel', parseResponse(res, 'funnel') || []);
        },
        async fetchSources({ commit, state }) {
            if (!state.activeSiteId) return;
            const res = await axios.get('admin/intelligence/sources', {
                params: {
                    site_id: state.activeSiteId,
                    from: state.filters.from,
                    to: state.filters.to,
                },
            });
            commit('setSources', parseResponse(res, 'sources') || []);
        },
        async fetchProducts({ commit, state }) {
            if (!state.activeSiteId) return;
            const res = await axios.get('admin/intelligence/products', {
                params: {
                    site_id: state.activeSiteId,
                    from: state.filters.from,
                    to: state.filters.to,
                },
            });
            commit('setProducts', parseResponse(res, 'products') || []);
        },
        async refreshAll({ dispatch }) {
            const results = await Promise.allSettled([
                dispatch('fetchOverview'),
                dispatch('fetchFunnel'),
                dispatch('fetchSources'),
                dispatch('fetchProducts'),
            ]);
            const failed = results.find((r) => r.status === 'rejected');
            if (failed) {
                throw failed.reason;
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
