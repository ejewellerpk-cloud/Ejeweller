import axios from "axios";
import appService from "../../../services/appService";

let pendingLists = null;

export const frontendOrderArea = {
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
            if (Array.isArray(context.state.lists) && context.state.lists.length > 0 && !payload) {
                return Promise.resolve({ data: { data: context.state.lists } });
            }

            if (pendingLists && !payload) {
                return pendingLists;
            }

            pendingLists = new Promise((resolve, reject) => {
                let url = "frontend/order-area";
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url).then((res) => {
                    context.commit("lists", res.data.data);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                }).finally(() => {
                    pendingLists = null;
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
