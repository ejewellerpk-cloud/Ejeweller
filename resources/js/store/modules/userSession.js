import axios from "axios";
import appService from "../../services/appService";

export const userSession = {
    namespaced: true,
    state: {
        lists: [],
        totalDevices: 0,
        allLists: [],
        allPagination: {},
        allPage: {},
        allTotalDevices: 0,
    },
    getters: {
        lists: function (state) {
            return state.lists;
        },
        totalDevices: function (state) {
            return state.totalDevices;
        },
        allLists: function (state) {
            return state.allLists;
        },
        allPagination: function (state) {
            return state.allPagination;
        },
        allPage: function (state) {
            return state.allPage;
        },
        allTotalDevices: function (state) {
            return state.allTotalDevices;
        },
    },
    actions: {
        lists: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = payload.mode === "self"
                    ? "auth/sessions"
                    : `admin/${payload.apiPrefix}/sessions/${payload.userId}`;

                axios.get(url).then((res) => {
                    context.commit("lists", res.data.data);
                    context.commit("totalDevices", res.data.total_devices ?? res.data.data.length);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        allLists: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = `admin/${payload.apiPrefix}/sessions`;
                if (payload.search) {
                    url = url + appService.requestHandler(payload.search);
                }

                axios.get(url).then((res) => {
                    context.commit("allLists", res.data.data);
                    context.commit("allPagination", res.data);
                    context.commit("allPage", res.data.meta);
                    context.commit("allTotalDevices", res.data.total_devices ?? res.data.meta?.total ?? res.data.data.length);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        destroy: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = payload.mode === "self"
                    ? `auth/sessions/${payload.tokenId}`
                    : `admin/${payload.apiPrefix}/sessions/${payload.userId}/${payload.tokenId}`;

                axios.delete(url).then((res) => {
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        destroyAll: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = payload.mode === "self"
                    ? (payload.othersOnly ? "auth/sessions/others" : "auth/sessions/all")
                    : `admin/${payload.apiPrefix}/sessions/${payload.userId}`;

                axios.delete(url).then((res) => {
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
        totalDevices: function (state, payload) {
            state.totalDevices = payload;
        },
        allLists: function (state, payload) {
            state.allLists = payload;
        },
        allPagination: function (state, payload) {
            state.allPagination = payload;
        },
        allPage: function (state, payload) {
            if (typeof payload !== "undefined" && payload !== null) {
                state.allPage = {
                    from: payload.from,
                    to: payload.to,
                    total: payload.total,
                };
            }
        },
        allTotalDevices: function (state, payload) {
            state.allTotalDevices = payload;
        },
        reset: function (state) {
            state.lists = [];
            state.totalDevices = 0;
        },
        resetAll: function (state) {
            state.allLists = [];
            state.allPagination = {};
            state.allPage = {};
            state.allTotalDevices = 0;
        },
    },
};
