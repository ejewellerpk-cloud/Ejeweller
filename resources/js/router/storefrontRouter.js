import { createRouter, createWebHistory } from "vue-router";
import NotFoundComponent from "../components/exception/NotFoundComponent.vue";
import authRoutes from "./modules/authRoutes";
import frontendRoutes from "./modules/frontendRoutes";
import { navigateToZone, isAdminPath } from "../services/appZone";
import { pixelService } from "../services/pixelService";

const routes = [
    ...frontendRoutes,
    ...authRoutes,
    {
        path: "/:pathMatch(.*)*",
        name: "route.notFound",
        component: NotFoundComponent,
        meta: {
            isFrontend: true,
            hideFooter: true,
        },
    },
];

export function createStorefrontRouter(store) {
    const router = createRouter({
        linkActiveClass: "active",
        history: createWebHistory(),
        routes,
        scrollBehavior() {
            return { left: 0, top: 0 };
        },
    });

    router.beforeEach((to, from, next) => {
        if (isAdminPath(to.path) || to.name === "route.exception") {
            navigateToZone(to.fullPath);
            return;
        }

        if (to.meta.auth === true) {
            if (!store.getters.authStatus) {
                next({ name: "auth.login" });
                return;
            }
        } else if (
            (to.name === "auth.login" ||
                to.name === "auth.signup" ||
                to.name === "auth.forgotPassword") &&
            store.getters.authStatus
        ) {
            next({ name: "frontend.home" });
            return;
        }

        next();
    });

    router.afterEach(() => {
        window.scrollTo(0, 0);
        const user = store.getters.authInfo;
        if (user?.email || user?.phone) {
            const parts = String(user.name || '').trim().split(/\s+/);
            pixelService.setUserData({
                email: user.email,
                phone: user.phone,
                first_name: parts[0] || '',
                last_name: parts.slice(1).join(' ') || '',
                user_id: user.id,
            });
        }
        pixelService.trackPageView();
    });

    return router;
}
