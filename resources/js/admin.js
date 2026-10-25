import AdminShell from "./components/AdminShell.vue";
import { createShopApp } from "./bootstrap/createShopApp";
import { createAdminRouter } from "./router/adminRouter";
import { bindAppRouter } from "./router";
import appService from "./services/appService";
import store from "./store";

const router = createAdminRouter();
bindAppRouter(router);

createShopApp(AdminShell, router, {
    store,
    registerPlugins: (app) => {
        // Do not await — ~1MB charts must not block AdminShell / sidebar mount.
        // Dashboard apexchart nodes are gated on API `options`, so registration usually wins the race.
        import("vue3-apexcharts").then(({ default: VueApexCharts }) => {
            app.use(VueApexCharts);
        });
    },
}).then(() => {
    if (store.getters.authPermission?.length) {
        appService.recursiveRouter(router.options.routes, store.getters.authPermission);
    }
});
