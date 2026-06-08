import axios from "axios";
import appService from "../../services/appService";

export const media = {
    namespaced: true,
    state: {
        lists: [],
        folders: [],
        currentFolder: 'all',
        page: {},
        pagination: [],
    },
    getters: {
        lists: function (state) {
            return state.lists;
        },
        folders: function (state) {
            return state.folders;
        },
        currentFolder: function (state) {
            return state.currentFolder;
        },
        pagination: function (state) {
            return state.pagination;
        },
        page: function (state) {
            return state.page;
        },
    },
    actions: {
        lists: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = "admin/media-library";
                if (payload) {
                    const params = new URLSearchParams();
                    Object.entries(payload).forEach(([key, value]) => {
                        if (value !== '' && value !== null && typeof value !== 'undefined') {
                            params.set(key, value);
                        }
                    });
                    const query = params.toString();
                    if (query) {
                        url = `${url}?${query}`;
                    }
                }
                axios.get(url).then((res) => {
                    context.commit("lists", res.data.items);
                    context.commit("folders", res.data.folders || []);
                    context.commit("currentFolder", res.data.currentFolder || 'all');
                    context.commit("page", res.data.pagination);
                    context.commit("pagination", res.data);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        save: function (context, payload) {
            return new Promise((resolve, reject) => {
                const formData = payload?.formData ?? payload;
                const config = payload?.config ?? {};

                axios.post("admin/media-library", formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    },
                    onUploadProgress: config.onUploadProgress,
                }).then((res) => {
                    context.dispatch("lists", { page: 1 }).then().catch();
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        uploadFile: function (context, payload) {
            return new Promise((resolve, reject) => {
                axios.post("admin/media-library", payload.formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    },
                    onUploadProgress: payload.onUploadProgress,
                }).then((res) => {
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        importFromUrl: function (context, payload) {
            return new Promise((resolve, reject) => {
                axios.post("admin/media-library/from-url", {
                    url: payload.url,
                    folder: payload.folder || 'uploads',
                }).then((res) => {
                    context.dispatch("lists", { page: 1 }).then().catch();
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        destroy: function (context, payload) {
            return new Promise((resolve, reject) => {
                axios.delete(`admin/media-library/${payload.id}`).then((res) => {
                    context.dispatch("lists", payload.search).then().catch();
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        bulkDestroy: function (context, payload) {
            return new Promise((resolve, reject) => {
                axios.post('admin/media-library/bulk-delete', { ids: payload.ids }).then((res) => {
                    context.dispatch("lists", payload.search).then().catch();
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
    },
    mutations: {
        lists: function (state, payload) {
            state.lists = payload;
        },
        folders: function (state, payload) {
            state.folders = payload;
        },
        currentFolder: function (state, payload) {
            state.currentFolder = payload;
        },
        pagination: function (state, payload) {
            state.pagination = payload;
        },
        page: function (state, payload) {
            if (typeof payload !== "undefined" && payload !== null) {
                state.page = {
                    from: (payload.page - 1) * payload.limit + 1,
                    to: Math.min(payload.page * payload.limit, payload.total),
                    total: payload.total,
                    totalPages: payload.totalPages,
                    currentPage: payload.page
                };
            }
        },
    },
};
