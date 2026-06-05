import axios from "axios";

export const userSession = {
    namespaced: true,
    state: {
        lists: [],
        totalDevices: 0,
    },
    getters: {
        lists: function (state) {
            return state.lists;
        },
        totalDevices: function (state) {
            return state.totalDevices;
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
        reset: function (state) {
            state.lists = [];
            state.totalDevices = 0;
        },
    },
};
