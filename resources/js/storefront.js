import StorefrontShell from "./components/StorefrontShell.vue";
import { createShopApp } from "./bootstrap/createShopApp";
import { createStorefrontRouter } from "./router/storefrontRouter";
import { bindAppRouter } from "./router";

const router = createStorefrontRouter();
bindAppRouter(router);

createShopApp(StorefrontShell, router);
