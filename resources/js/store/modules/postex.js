import axios from "axios";

export const postex = {
    namespaced: true,
    state: {
        lists: [],
    },
    getters: {
        lists: function (state) {
            return state.lists;
        },
    },
    actions: {
        lists: function (context) {
            return new Promise((resolve, reject) => {
                axios
                    .get("admin/setting/postex")
                    .then((res) => {
                        context.commit("lists", res.data.data);
                        resolve(res);
                    })
                    .catch((err) => {
                        reject(err);
                    });
            });
        },
        save: function (context, payload) {
            return new Promise((resolve, reject) => {
                axios
                    .put("admin/setting/postex", payload)
                    .then((res) => {
                        context.commit("lists", res.data.data);
                        resolve(res);
                    })
                    .catch((err) => {
                        reject(err);
                    });
            });
        },
        testConnection: function () {
            return axios.post("admin/setting/postex/test-connection");
        },
        operationalCities: function (context, params = {}) {
            return axios.get("admin/setting/postex/operational-cities", { params });
        },
        merchantAddresses: function (context, params = {}) {
            return axios.get("admin/setting/postex/merchant-addresses", { params });
        },
        orderTypes: function () {
            return axios.get("admin/setting/postex/order-types");
        },
    },
    mutations: {
        lists: function (state, payload) {
            state.lists = payload;
        },
    },
};
