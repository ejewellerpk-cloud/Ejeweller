import axios from 'axios'
import appService from "../../services/appService";


export const whatsappOrder = {
    namespaced: true,
    state: {
        lists: [],
        page: {},
        pagination: [],
        show: {},
        orderProducts: {},
        orderUser: {},
        orderAddress: {},
        temp: {
            temp_id: null,
            isEditing: false,
        },
    },
    getters: {
        lists: function (state) {
            return state.lists;
        },
        pagination: function (state) {
            return state.pagination
        },
        page: function(state) {
            return state.page;
        },
        show: function (state) {
            return state.show;
        },
        orderProducts: function (state) {
            return state.orderProducts;
        },
        orderUser: function (state) {
            return state.orderUser;
        },
        orderAddress: function (state) {
            return state.orderAddress;
        },
        temp: function (state) {
            return state.temp;
        }
    },
    actions: {
        lists: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = 'admin/whatsapp-order';
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url).then((res) => {
                    if(typeof payload.vuex === "undefined" || payload.vuex === true) {
                        context.commit('lists', res.data.data);
                        context.commit('page', res.data.meta);
                        context.commit('pagination', res.data);
                    }
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        save: function (context, payload) {
            return new Promise((resolve, reject) => {
                axios.post("admin/whatsapp-order", payload).then((res) => {
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        show: function (context, payload) {
            return new Promise((resolve, reject) => {
                axios.get(`admin/whatsapp-order/show/${payload}`).then((res) => {
                    context.commit('show', res.data.data);
                    context.commit("orderProducts", res.data.data.order_products);
                    context.commit("orderUser", res.data.data.user);
                    context.commit("orderAddress", res.data.data.order_address);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        destroy: function (context, payload) {
            return new Promise((resolve, reject) => {
                axios.delete(`admin/whatsapp-order/${payload.id}`).then((res) => {
                    context.dispatch('lists', payload.search).then().catch();
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        changeStatus: function (context, payload) {
            return new Promise((resolve, reject) => {
                axios.post(`admin/whatsapp-order/change-status/${payload.id}`, payload).then((res) => {
                    context.commit('show', res.data.data);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        changePaymentStatus: function (context, payload) {
            return new Promise((resolve, reject) => {
                axios.post(`admin/whatsapp-order/change-payment-status/${payload.id}`, payload).then((res) => {
                    context.commit('show', res.data.data);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        reset: function (context) {
            context.commit('reset');
        },
        export: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = 'admin/whatsapp-order/export';
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url, {responseType: 'blob'}).then((res) => {
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        saveCustomer: function (context, payload) {
            return new Promise((resolve, reject) => {
                axios.post("/admin/whatsapp-order/customer", payload.form)
                    .then((res) => {
                        resolve(res);
                    })
                    .catch((err) => {
                        reject(err);
                    });
            });
        },
    },
    mutations: {
        lists: function (state, payload) {
            state.lists = payload
        },
        pagination: function (state, payload) {
            state.pagination = payload;
        },
        page: function (state, payload) {
            if(typeof payload !== "undefined" && payload !== null) {
                state.page = {
                    from: payload.from,
                    to: payload.to,
                    total: payload.total
                }
            }
        },
        show: function (state, payload) {
            state.show = payload;
        },
        orderProducts: function (state, payload) {
            state.orderProducts = payload;
        },
        orderUser: function (state, payload) {
            state.orderUser = payload;
        },
        orderAddress: function (state, payload) {
            state.orderAddress = payload;
        },
        reset: function(state) {
            state.temp.temp_id = null;
            state.temp.isEditing = false;
        }
    },
}
