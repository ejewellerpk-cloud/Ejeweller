import axios from "axios";
import appService from "../../../services/appService";
import { trackWishlistToggle } from "../../../services/analyticsEcommerceBridge";

export const frontendWishlist = {
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
            return new Promise((resolve, reject) => {
                let url = "frontend/wishlist";
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url, payload).then((res) => {
                    resolve(res);
                    context.commit("lists", res.data.data);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        toggle: function (context, payload) {
            return new Promise((resolve, reject) => {
                axios.post("frontend/wishlist/toggle", payload).then((res) => {
                    const added = !!payload?.toggle;
                    trackWishlistToggle(
                        {
                            id: payload.product_id,
                            product_id: payload.product_id,
                            sku: payload.sku ?? res.data?.data?.sku,
                        },
                        added
                    );
                    context.dispatch("lists").then().catch();
                    if (!added) {
                        context.dispatch('frontendProduct/wishlistProducts', null, { root: true }).then().catch();
                    }
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        reset: function (context) {
            context.commit('reset');
        }
    },
    mutations: {
        lists: function (state, payload) {
            state.lists = payload;
        },
        reset: function (state) {
            state.lists = [];
        }
    },
};
