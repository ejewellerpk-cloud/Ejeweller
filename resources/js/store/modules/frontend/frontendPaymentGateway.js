import axios from "axios";
import appService from "../../../services/appService";

let pendingLists = null;
let pendingKey = null;

export const frontendPaymentGateway = {
    namespaced: true,
    state: {
        lists: []
    },
    getters: {
        lists: function (state) {
            return state.lists;
        }
    },
    actions: {
        lists: function (context, payload) {
            const useVuex = !payload || typeof payload.vuex === "undefined" || payload.vuex === true;
            const cacheKey = payload ? appService.requestHandler(payload) : "";

            if (
                useVuex &&
                Array.isArray(context.state.lists) &&
                context.state.lists.length > 0
            ) {
                return Promise.resolve({ data: { data: context.state.lists } });
            }

            if (pendingLists && pendingKey === cacheKey) {
                return pendingLists;
            }

            pendingKey = cacheKey;
            pendingLists = new Promise((resolve, reject) => {
                let url = "frontend/payment-gateway";
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url).then((res) => {
                    if (useVuex) {
                        context.commit("lists", res.data.data);
                    }
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                }).finally(() => {
                    pendingLists = null;
                    pendingKey = null;
                });
            });

            return pendingLists;
        }
    },
    mutations: {
        lists: function (state, payload) {
            state.lists = payload;
        }
    },
};
