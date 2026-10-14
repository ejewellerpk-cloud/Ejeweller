import AdminShell from "./components/AdminShell.vue";
import { createShopApp } from "./bootstrap/createShopApp";
import { createAdminRouter } from "./router/adminRouter";
import { bindAppRouter } from "./router";
import appService from "./services/appService";
import store from "./store";

const router = createAdminRouter();
bindAppRouter(router);

createShopApp(AdminShell, router, {
    registerPlugins: async (app) => {
        const { default: VueApexCharts } = await import("vue3-apexcharts");
        app.use(VueApexCharts);
    },
}).then(() => {
    if (store.getters.authPermission?.length) {
        appService.recursiveRouter(router.options.routes, store.getters.authPermission);
    }
});
