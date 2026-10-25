import { createApp } from "vue";
import axios from "axios";
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";
import VueSimpleAlert from "vue3-simple-alert";
import VueNextSelect from "vue-next-select";
import "vue-next-select/dist/index.css";
import { createHead } from "@vueuse/head";
import "sweetalert2/dist/sweetalert2.min.css";

import i18n, { loadLocaleMessages } from "../i18n";
import ENV from "../config/env";
import appService from "../services/appService";
import alertService from "../services/alertService";

import "../../../public/themes/default/fonts/urbanist/urbanist.css";
import "../../../public/themes/default/fonts/iconly/iconly.css";
import "../../../public/themes/default/fonts/public/public.css";
// Eager — cart/nav use fa-solid; deferred load left blank icons
import "../../../public/themes/default/fonts/fontawesome/fontawesome.css";

const toastOptions = {
    timeout: 2000,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: false,
    hideProgressBar: false,
    closeButton: "button",
    icon: true,
    rtl: false,
};

function configureAxios(router, store) {
    const API_URL = ENV.API_URL;
    const API_KEY = ENV.API_KEY;

    axios.defaults.baseURL = API_URL + "/api";

    // Avoid JSON.parse(localStorage) on every request — admin pages fire many APIs per navigation.
    let cachedVuexRaw = null;
    let cachedVuex = null;

    axios.interceptors.request.use(
        (config) => {
            config.headers["x-api-key"] = API_KEY;
            const raw = localStorage.getItem("vuex");
            if (raw) {
                if (raw !== cachedVuexRaw) {
                    cachedVuexRaw = raw;
                    try {
                        cachedVuex = JSON.parse(raw);
                    } catch (e) {
                        cachedVuex = null;
                    }
                }
                const vuex = cachedVuex;
                if (vuex?.auth) {
                    const token = vuex.auth.authToken;
                    config.headers["Authorization"] = token ? `Bearer ${token}` : "";
                }
                if (vuex?.globalState?.lists?.language_code) {
                    config.headers["x-localization"] = vuex.globalState.lists.language_code;
                }
            } else {
                cachedVuexRaw = null;
                cachedVuex = null;
            }
            return config;
        },
        (error) => Promise.reject(error)
    );

    axios.interceptors.response.use(
        (response) => response,
        (error) => {
            if (error.response && error.response.status === 401) {
                store.dispatch("loginDataReset");
                if (router) {
                    const loginPath = "/login";
                    if (window.location.pathname.startsWith("/admin")) {
                        window.location.assign(
                            loginPath + "?redirect=" + encodeURIComponent(window.location.pathname + window.location.search)
                        );
                    } else {
                        router.push({ name: "auth.login" });
                    }
                }
            } else if (error.response && error.response.status >= 500) {
                alertService.error(appService.apiErrorMessage(error));
            }

            error.apiMessage = appService.apiErrorMessage(error);

            return Promise.reject(error);
        }
    );
}

/**
 * @param {import('vue').Component} RootComponent
 * @param {import('vue-router').Router} router
 * @param {{ store: import('vuex').Store, registerPlugins?: (app: import('vue').App) => void | Promise<void> }} options
 */
export async function createShopApp(RootComponent, router, options = {}) {
    const store = options.store;
    if (!store) {
        throw new Error("createShopApp requires options.store");
    }

    configureAxios(router, store);

    if (Array.isArray(window.__HOME_HERO_SLIDERS__) && window.__HOME_HERO_SLIDERS__.length > 0) {
        store.commit("frontendSlider/lists", window.__HOME_HERO_SLIDERS__);
    }

    const app = createApp(RootComponent);
    const head = createHead();

    app.component("vue-select", VueNextSelect);
    app.use(router);
    app.use(store);
    app.use(VueSimpleAlert);
    app.use(Toast, toastOptions);
    app.use(i18n);
    app.use(head);

    if (typeof options.registerPlugins === "function") {
        await options.registerPlugins(app);
    }

    await router.isReady();

    const persistedLocale = store.getters["globalState/lists"]?.language_code;
    if (persistedLocale && persistedLocale !== "en") {
        try {
            await loadLocaleMessages(persistedLocale);
            i18n.global.locale.value = persistedLocale;
        } catch (e) {
            // Fall back to English already loaded
        }
    }

    // Mount first while #app is still display:none (boot LCP hero stays visible),
    // then swap in one turn — avoids empty flash and double-hero CLS.
    app.mount("#app");
    document.documentElement.classList.add("app-ready");

    if (store.getters.authStatus && store.getters.authToken) {
        store.dispatch("profile").catch(() => {
            // Keep persisted session; broken profile fetch should not block boot.
        });
    }

    return app;
}
