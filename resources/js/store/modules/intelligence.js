import axios from 'axios';
import {
    intelAuthContext,
    intelAxiosMeta,
    intelError,
    intelLog,
    intelWarn,
    isIntelligenceDebugEnabled,
} from './intelligenceDebug';

function isHtmlApiBody(data) {
    return typeof data === 'string' && /^\s*<!DOCTYPE/i.test(data);
}

function parseResponse(res, label) {
    const body = res?.data;
    if (isHtmlApiBody(body)) {
        const hint =
            'Server returned the storefront page instead of JSON. Deploy routes/api.php (intelligence routes) and run: php artisan route:clear';
        intelError(`${label} rejected (HTML body)`, body.slice(0, 200));
        throw new Error(hint);
    }
    intelLog(`${label} response`, {
        success: body?.success,
        data: body?.data,
        meta: body?.meta,
    });
    if (!body?.success) {
        const msg = body?.message || body?.error;
        intelError(`${label} rejected (success=false)`, body);
        throw new Error(msg || `Failed to load ${label}`);
    }
    return body.data ?? [];
}

function apiErrorMessage(err, fallback) {
    intelError('API error', intelAxiosMeta(err));
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
            intelLog('fetchSites → start', intelAuthContext());
            let res;
            try {
                res = await axios.get('admin/intelligence/sites');
                intelLog('fetchSites ← HTTP OK', intelAxiosMeta(res));
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
            intelLog('fetchSites ✓ committed', { sitesCount: sites.length, defaultId, meta });
            return { sites, meta };
        },
        async fetchOverview({ commit, state }) {
            if (!state.activeSiteId) {
                intelWarn('fetchOverview skipped — no activeSiteId', state);
                return;
            }
            const params = {
                site_id: state.activeSiteId,
                from: state.filters.from,
                to: state.filters.to,
            };
            intelLog('fetchOverview →', params);
            commit('setLoading', true);
            commit('setLastError', null);
            try {
                const res = await axios.get('admin/intelligence/overview', { params });
                intelLog('fetchOverview ← HTTP OK', intelAxiosMeta(res));
                const data = parseResponse(res, 'overview');
                commit('setOverview', data);
                if (data?.realtime) {
                    commit('setRealtime', data.realtime);
                }
                intelLog('fetchOverview ✓ KPIs', {
                    visitors: data?.visitors,
                    sessions: data?.sessions,
                    page_views: data?.page_views,
                    realtime: data?.realtime,
                });
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
                intelLog('fetchRealtime ✓', {
                    active_visitors: data?.active_visitors,
                    page_views_today: data?.page_views_today,
                });
            } catch (e) {
                commit('setLastError', e.message || 'Realtime failed');
                intelError('fetchRealtime failed', e.message);
            }
        },
        async fetchFunnel({ commit, state }) {
            if (!state.activeSiteId) return;
            const params = {
                site_id: state.activeSiteId,
                from: state.filters.from,
                to: state.filters.to,
            };
            intelLog('fetchFunnel →', params);
            try {
                const res = await axios.get('admin/intelligence/funnel', { params });
                const data = parseResponse(res, 'funnel') || [];
                commit('setFunnel', data);
                intelLog('fetchFunnel ✓ steps', data.length);
            } catch (e) {
                intelError('fetchFunnel failed', intelAxiosMeta(e));
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
            intelLog('fetchSources →', params);
            try {
                const res = await axios.get('admin/intelligence/sources', { params });
                const data = parseResponse(res, 'sources') || [];
                commit('setSources', data);
                intelLog('fetchSources ✓ rows', data.length);
            } catch (e) {
                intelError('fetchSources failed', intelAxiosMeta(e));
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
            intelLog('fetchProducts →', params);
            try {
                const res = await axios.get('admin/intelligence/products', { params });
                const data = parseResponse(res, 'products') || [];
                commit('setProducts', data);
                intelLog('fetchProducts ✓ rows', data.length);
            } catch (e) {
                intelError('fetchProducts failed', intelAxiosMeta(e));
                throw e;
            }
        },
        async refreshAll({ dispatch, state }) {
            intelLog('refreshAll →', {
                activeSiteId: state.activeSiteId,
                filters: state.filters,
            });
            const results = await Promise.allSettled([
                dispatch('fetchOverview'),
                dispatch('fetchFunnel'),
                dispatch('fetchSources'),
                dispatch('fetchProducts'),
            ]);
            const labels = ['overview', 'funnel', 'sources', 'products'];
            results.forEach((r, i) => {
                if (r.status === 'fulfilled') {
                    intelLog(`refreshAll ✓ ${labels[i]}`);
                } else {
                    intelError(`refreshAll ✗ ${labels[i]}`, r.reason?.message || r.reason);
                }
            });
            const failed = results.find((r) => r.status === 'rejected');
            if (failed) {
                throw failed.reason;
            }
            intelLog('refreshAll complete');
        },
    },
    mutations: {
        setSites(state, sites) {
            state.sites = sites;
            intelLog('mutation setSites', { count: sites?.length, ids: sites?.map((s) => s.id) });
        },
        setActiveSiteId(state, id) {
            state.activeSiteId = Number(id) || null;
            intelLog('mutation setActiveSiteId', state.activeSiteId);
        },
        setOverview(state, data) {
            state.overview = data;
            intelLog('mutation setOverview', data);
        },
        setRealtime(state, data) {
            state.realtime = data;
        },
        setFunnel(state, data) {
            state.funnel = data;
            intelLog('mutation setFunnel', { steps: data?.length });
        },
        setSources(state, data) {
            state.sources = data;
            intelLog('mutation setSources', { rows: data?.length });
        },
        setProducts(state, data) {
            state.products = data;
            intelLog('mutation setProducts', { rows: data?.length });
        },
        setFilters(state, filters) {
            state.filters = { ...state.filters, ...filters };
            intelLog('mutation setFilters', state.filters);
        },
        setLoading(state, v) {
            state.loading = v;
        },
        setLastError(state, msg) {
            state.lastError = msg;
            if (msg) intelWarn('mutation setLastError', msg);
        },
    },
};

if (typeof window !== 'undefined' && isIntelligenceDebugEnabled()) {
    intelLog('Debug ON — disable: localStorage.setItem("intelligence_debug","0"); location.reload()');
}
