import axios from "axios";
import appService from "../../../services/appService";

let categoryWiseProductsSeq = 0;

function extractCategoryWiseProductList(payload) {
    if (!payload || typeof payload !== "object") {
        return [];
    }
    const products = payload.products;
    if (Array.isArray(products)) {
        return products;
    }
    if (products && Array.isArray(products.data)) {
        return products.data;
    }
    if (Array.isArray(payload.data)) {
        return payload.data;
    }
    return [];
}

export const frontendProduct = {
    namespaced: true,
    state: {
        show : {},
        showImages: [],
        showReviews: [],
        showVideos: [],
        showSeo: {},
        popularProducts: [],
        popularProductPage: {},
        popularProductPagination: [],
        flashSaleProducts: [],
        flashSaleProductPage: {},
        flashSaleProductPagination: [],
        categoryWiseProducts: [],
        categoryWiseBands: [],
        categoryWiseVariations: [],
        categoryWiseProductPage: {},
        categoryWiseProductPagination: {},
        offerProducts: [],
        offerProductPage: {},
        offerProductPagination: [],
        wishlistProducts: [],
        wishlistProductPage: {},
        wishlistProductPagination: [],
        relatedProducts: [],
    },
    getters: {
        show: function (state) {
            return state.show;
        },
        showImages: function (state) {
            return state.showImages;
        },
        showReviews: function (state) {
            return state.showReviews;
        },
        showVideos: function (state) {
            return state.showVideos;
        },
        showSeo: function (state) {
            return state.showSeo;
        },
        popularProducts: function (state) {
            return state.popularProducts;
        },
        popularProductPage: function (state) {
            return state.popularProductPage;
        },
        popularProductPagination: function (state) {
            return state.popularProductPagination;
        },
        flashSaleProducts: function (state) {
            return state.flashSaleProducts;
        },
        flashSaleProductPage: function (state) {
            return state.flashSaleProductPage;
        },
        flashSaleProductPagination: function (state) {
            return state.flashSaleProductPagination;
        },
        categoryWiseProducts: function (state) {
            return state.categoryWiseProducts;
        },
        categoryWiseBands: function (state) {
            return state.categoryWiseBands;
        },
        categoryWiseVariations: function (state) {
            return state.categoryWiseVariations;
        },
        categoryWiseProductPage: function (state) {
            return state.categoryWiseProductPage;
        },
        categoryWiseProductPagination: function (state) {
            return state.categoryWiseProductPagination;
        },
        offerProducts: function (state) {
            return state.offerProducts;
        },
        offerProductPage: function (state) {
            return state.offerProductPage;
        },
        offerProductPagination: function (state) {
            return state.offerProductPagination;
        },
        wishlistProducts: function (state) {
            return state.wishlistProducts;
        },
        wishlistProductPage: function (state) {
            return state.wishlistProductPage;
        },
        wishlistProductPagination: function (state) {
            return state.wishlistProductPagination;
        },
        relatedProducts: function (state) {
            return state.relatedProducts;
        },
    },
    actions: {
        show: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = `frontend/product/show/${payload.slug}`;
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url).then((res) => {
                    context.commit("show", res.data.data);
                    let images = res.data.data.images || [];
                    if (images.length === 0 && res.data.data.image) {
                        images = [res.data.data.image];
                    }
                    context.commit("showImages", images);
                    context.commit("showReviews", res.data.data.reviews);
                    context.commit("showVideos", res.data.data.videos);
                    context.commit("showSeo", res.data.data.seo);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        showWithTrashed: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = `frontend/product/show-with-trashed/${payload.slug}`;
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url).then((res) => {
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        categoryWiseProducts: function (context, payload) {
            const seq = ++categoryWiseProductsSeq;
            return new Promise((resolve, reject) => {
                const url = `frontend/product/category-wise-products`;
                axios.post(url, payload).then((res) => {
                    if (seq !== categoryWiseProductsSeq) {
                        resolve(res);
                        return;
                    }
                    const commitData = res?.data?.data ?? res?.data ?? {};
                    context.commit("categoryWiseProducts", commitData);
                    context.commit("categoryWiseProductPage", commitData);
                    context.commit("categoryWiseProductPagination", commitData);
                    resolve(res);
                }).catch((err) => {
                    if (seq === categoryWiseProductsSeq) {
                        reject(err);
                    } else {
                        resolve();
                    }
                });
            });
        },
        popularProducts: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = `frontend/product/popular-products`;
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url, payload).then((res) => {
                    context.commit("popularProducts", res.data.data);
                    context.commit("popularProductPage", res.data.meta);
                    context.commit("popularProductPagination", res.data);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        flashSaleProducts: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = `frontend/product/flash-sale-products`;
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url, payload).then((res) => {
                    context.commit("flashSaleProducts", res.data.data);
                    context.commit("flashSaleProductPage", res.data.meta);
                    context.commit("flashSaleProductPagination", res.data);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        offerProducts: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = `frontend/product/offer-products`;
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url, payload).then((res) => {
                    context.commit("offerProducts", res.data.data);
                    context.commit("offerProductPage", res.data.meta);
                    context.commit("offerProductPagination", res.data);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        lists: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = `frontend/product`;
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url).then((res) => {
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        wishlistProducts: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = `frontend/product/wishlist-products`;
                if (payload && typeof payload.ids !== 'undefined') {
                    url = `frontend/product`;
                }
                if (payload) {
                    url = url + appService.requestHandler(payload);
                }
                axios.get(url).then((res) => {
                    context.commit("wishlistProducts", res.data.data);
                    context.commit("wishlistProductPage", res.data.meta);
                    context.commit("wishlistProductPagination", res.data);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
        relatedProducts: function (context, payload) {
            return new Promise((resolve, reject) => {
                let url = `frontend/product/related-products/${payload.slug}`;
                const query = { ...payload };
                delete query.slug;
                url = url + appService.requestHandler(query);
                axios.get(url).then((res) => {
                    context.commit("relatedProducts", res.data.data);
                    resolve(res);
                }).catch((err) => {
                    reject(err);
                });
            });
        },
    },
    mutations: {
        show: function (state, payload) {
            state.show = payload;
        },
        updateSocialProof: function (state, payload) {
            if (state.show && state.show.id === payload.product_id) {
                state.show.in_baskets = parseInt(payload.in_baskets, 10) || 0;
                state.show.bought_last_24_hours = parseInt(payload.bought_last_24_hours, 10) || 0;
            }
        },
        showImages: function (state, payload) {
            state.showImages = payload;
        },
        showReviews: function(state, payload) {
            state.showReviews = payload;
        },
        showVideos:function (state, payload) {
            state.showVideos = payload;
        },
        showSeo:function (state, payload) {
            state.showSeo = payload;
        },
        popularProducts: function (state, payload) {
            state.popularProducts = payload;
        },
        popularProductPage: function (state, payload) {
            if (typeof payload !== "undefined" && payload !== null) {
                state.popularProductPage = {
                    from: payload.from,
                    to: payload.to,
                    total: payload.total,
                };
            }
        },
        popularProductPagination: function (state, payload) {
            state.popularProductPagination = payload;
        },
        flashSaleProducts: function (state, payload) {
            state.flashSaleProducts = payload;
        },
        flashSaleProductPage: function (state, payload) {
            if (typeof payload !== "undefined" && payload !== null) {
                state.flashSaleProductPage = {
                    from: payload.from,
                    to: payload.to,
                    total: payload.total,
                };
            }
        },
        flashSaleProductPagination: function (state, payload) {
            state.flashSaleProductPagination = payload;
        },
        categoryWiseProducts: function (state, payload) {
            const list = extractCategoryWiseProductList(payload);
            const page = Number(payload?.current_page) || 1;

            if (page > 1) {
                state.categoryWiseProducts = [...state.categoryWiseProducts, ...list];
            } else {
                state.categoryWiseProducts = list;
            }

            if (page === 1) {
                state.categoryWiseBands = Array.isArray(payload.brands)
                    ? payload.brands
                    : (payload.brands?.data || state.categoryWiseBands);
                state.categoryWiseVariations = payload.variations || {};
            }
        },
        categoryWiseProductPage: function (state, payload) {
            if (typeof payload !== "undefined" && payload !== null) {
                state.categoryWiseProductPage = {
                    from: payload.from,
                    to: payload.to,
                    total: payload.total,
                };
            }
        },
        categoryWiseProductPagination: function (state, payload) {
            const productData = Array.isArray(payload.products)
                ? payload.products
                : (payload.products?.data ?? payload.products);
            state.categoryWiseProductPagination = {
                data: productData,
                links: {
                    first: payload.first_page_url,
                    last: payload.last_page_url,
                    next: payload.next_page_url,
                    prev: payload.prev_page_url
                },
                meta: {
                    current_page: payload.current_page,
                    from: payload.from,
                    last_page: payload.last_page,
                    links: payload.links,
                    path: payload.path,
                    per_page: payload.per_page,
                    to: payload.to,
                    total: payload.total
                }
            }
        },
        offerProducts: function (state, payload) {
            state.offerProducts = payload;
        },
        offerProductPage: function (state, payload) {
            if (typeof payload !== "undefined" && payload !== null) {
                state.offerProductPage = {
                    from: payload.from,
                    to: payload.to,
                    total: payload.total,
                };
            }
        },
        offerProductPagination: function (state, payload) {
            state.offerProductPagination = payload;
        },
        wishlistProducts: function (state, payload) {
            state.wishlistProducts = payload;
        },
        wishlistProductPage: function (state, payload) {
            if (typeof payload !== "undefined" && payload !== null) {
                state.wishlistProductPage = {
                    from: payload.from,
                    to: payload.to,
                    total: payload.total,
                };
            }
        },
        wishlistProductPagination: function (state, payload) {
            state.wishlistProductPagination = payload;
        },
        relatedProducts: function (state, payload) {
            state.relatedProducts = payload;
        },
    },
};
