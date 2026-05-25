import axios from 'axios';

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
    },
    actions: {
        async fetchSites({ commit }) {
            const res = await axios.get('admin/intelligence/sites');
            if (!res.data?.success) {
                throw new Error(res.data?.message || 'Failed to load analytics sites');
            }
            const sites = res.data.data || [];
            commit('setSites', sites);
            if (sites.length) {
                commit('setActiveSiteId', sites[0].id);
            }
            return sites;
        },
        async fetchOverview({ commit, state }) {
            if (!state.activeSiteId) return;
            commit('setLoading', true);
            try {
                const res = await axios.get('admin/intelligence/overview', {
                    params: { site_id: state.activeSiteId, ...state.filters },
                });
                commit('setOverview', res.data.data);
                commit('setRealtime', res.data.data?.realtime);
            } finally {
                commit('setLoading', false);
            }
        },
        async fetchRealtime({ commit, state }) {
            if (!state.activeSiteId) return;
            const res = await axios.get('admin/intelligence/realtime', {
                params: { site_id: state.activeSiteId },
            });
            commit('setRealtime', res.data.data);
        },
        async fetchFunnel({ commit, state }) {
            if (!state.activeSiteId) return;
            const res = await axios.get('admin/intelligence/funnel', {
                params: { site_id: state.activeSiteId, ...state.filters },
            });
            commit('setFunnel', res.data.data || []);
        },
        async fetchSources({ commit, state }) {
            if (!state.activeSiteId) return;
            const res = await axios.get('admin/intelligence/sources', {
                params: { site_id: state.activeSiteId, ...state.filters },
            });
            commit('setSources', res.data.data || []);
        },
        async fetchProducts({ commit, state }) {
            if (!state.activeSiteId) return;
            const res = await axios.get('admin/intelligence/products', {
                params: { site_id: state.activeSiteId, ...state.filters },
            });
            commit('setProducts', res.data.data || []);
        },
        async refreshAll({ dispatch }) {
            await Promise.all([
                dispatch('fetchOverview'),
                dispatch('fetchFunnel'),
                dispatch('fetchSources'),
                dispatch('fetchProducts'),
            ]);
        },
    },
    mutations: {
        setSites(state, sites) {
            state.sites = sites;
        },
        setActiveSiteId(state, id) {
            state.activeSiteId = id;
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
    },
};
