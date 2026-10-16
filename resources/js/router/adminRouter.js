import { createRouter, createWebHistory } from "vue-router";
import ExceptionComponent from "../components/exception/ExceptionComponent.vue";
import appService from "../services/appService";
import store from "../store";
import { navigateToZone } from "../services/appZone";

import administratorRoutes from "./modules/administratorRoutes";
import couponRoutes from "./modules/couponRoutes";
import creditBalanceReportRoutes from "./modules/creditBalanceReportRoutes";
import customerRoutes from "./modules/customerRoutes";
import damageRoutes from "./modules/damageRoutes";
import employeeRoutes from "./modules/employeeRoutes";
import onlineOrderRoutes from "./modules/onlineOrderRoutes";
import postexShipmentRoutes from "./modules/postexShipmentRoutes";
import mediaRoutes from "./modules/mediaRoutes";
import posOrderRoutes from "./modules/posOrderRoutes";
import posRoutes from "./modules/posRoutes";
import whatsappOrderRoutes from "./modules/whatsappOrderRoutes";
import whatsappOrdersRoutes from "./modules/whatsappOrdersRoutes";
import ProductSectionRoutes from "./modules/ProductSectionRoutes";
import productsReportRoutes from "./modules/productsReportRoutes";
import productsRoutes from "./modules/productsRoutes";
import productCategoryRoutes from "./modules/productCategoryRoutes";
import productBrandRoutes from "./modules/productBrandRoutes";
import productAttributeRoutes from "./modules/productAttributeRoutes";
import unitRoutes from "./modules/unitRoutes";
import taxRoutes from "./modules/taxRoutes";
import profileRoutes from "./modules/profileRoutes";
import PromotionRoutes from "./modules/PromotionRoutes";
import purchaseRoutes from "./modules/purchaseRoutes";
import pushNotificationRoutes from "./modules/pushNotificationRoutes";
import returnAndRefundRoutes from "./modules/returnAndRefundRoutes";
import returnOrderRoutes from "./modules/returnOrderRoutes";
import reviewRoutes from "./modules/reviewRoutes";
import salesReportRoutes from "./modules/salesReportRoutes";
import settingRoutes from "./modules/settingRoutes";
import stockRoutes from "./modules/stockRoutes";
import subscriberRoutes from "./modules/subscriberRoutes";
import transactionRoutes from "./modules/transactionRoutes";

const DashboardComponent = () => import("../components/admin/dashboard/DashboardComponent");

const baseRoutes = [
    {
        path: "/exception",
        name: "route.exception",
        component: ExceptionComponent,
        meta: {
            hideFooter: true,
        },
    },
    {
        path: "/admin/dashboard",
        component: DashboardComponent,
        name: "admin.dashboard",
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: "dashboard",
            breadcrumb: "dashboard",
        },
    },
    {
        path: "/admin",
        redirect: { name: "admin.dashboard" },
    },
];

const routes = baseRoutes.concat(
    settingRoutes,
    profileRoutes,
    productsRoutes,
    productCategoryRoutes,
    productBrandRoutes,
    productAttributeRoutes,
    unitRoutes,
    taxRoutes,
    administratorRoutes,
    customerRoutes,
    employeeRoutes,
    transactionRoutes,
    salesReportRoutes,
    creditBalanceReportRoutes,
    pushNotificationRoutes,
    couponRoutes,
    PromotionRoutes,
    ProductSectionRoutes,
    purchaseRoutes,
    stockRoutes,
    returnOrderRoutes,
    damageRoutes,
    onlineOrderRoutes,
    postexShipmentRoutes,
    productsReportRoutes,
    posOrderRoutes,
    posRoutes,
    whatsappOrderRoutes,
    whatsappOrdersRoutes,
    returnAndRefundRoutes,
    subscriberRoutes,
    reviewRoutes,
    mediaRoutes,
    {
        path: "/:pathMatch(.*)*",
        name: "admin.notFound",
        redirect: { name: "admin.dashboard" },
    }
);

export function createAdminRouter() {
    const permission = store.getters.authPermission;
    appService.recursiveRouter(routes, permission);

    const router = createRouter({
        linkActiveClass: "active",
        history: createWebHistory(),
        routes,
        scrollBehavior() {
            return { left: 0, top: 0 };
        },
    });

    router.beforeEach((to, from, next) => {
        const isStorefrontPath =
            to.meta?.isFrontend === true ||
            (to.path && !to.path.startsWith("/admin") && to.path !== "/exception");

        if (isStorefrontPath && to.name !== "route.exception") {
            navigateToZone(to.fullPath);
            return;
        }

        if (to.meta.auth === true) {
            if (!store.getters.authStatus) {
                navigateToZone("/login?redirect=" + encodeURIComponent(to.fullPath));
                return;
            }
            if (to.meta.isFrontend === false && to.meta.access === false) {
                next({ name: "route.exception" });
                return;
            }
        }

        next();
    });

    router.afterEach(() => {
        const mainEl = document.querySelector(".db-main");
        if (mainEl) {
            mainEl.scrollTop = 0;
        }
        window.scrollTo(0, 0);
    });

    return router;
}
