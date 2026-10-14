import StorefrontShell from "./components/StorefrontShell.vue";
import { createShopApp } from "./bootstrap/createShopApp";
import { createStorefrontRouter } from "./router/storefrontRouter";
import { bindAppRouter } from "./router";
import store from "./store/storefront";

const router = createStorefrontRouter(store);
bindAppRouter(router);

createShopApp(StorefrontShell, router, { store });
