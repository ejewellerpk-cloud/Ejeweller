import axios from "axios";
import appService from "../../../services/appService";

export const frontendSetting = {
    namespaced: true,
    state: {
        lists: [],
    },
    getters: {
        lists: function (state) {
            return state.lists;
        }
    },
    actions: {
        lists: function (context, payload) {
            // Shell + navbar both request settings — reuse in-memory result for same tick/session
            if (
                !payload &&
                context.state.lists &&
                typeof context.state.lists === "object" &&
                Object.keys(context.state.lists).length > 0
            ) {
                return Promise.resolve({ data: { data: context.state.lists } });
            }

            return new Promise((resolve, reject) => {
                let url = "frontend/setting";
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url).then((res) => {
                    context.commit("lists", res.data.data);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        }
    },
    mutations: {
        lists: function (state, payload) {
            state.lists = payload;
        }
    },
};
