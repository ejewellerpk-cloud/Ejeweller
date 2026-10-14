/**
 * Bound router singleton so legacy `import router from ".../router"` keeps working
 * after the storefront/admin entry split. Entries must call bindAppRouter() before mount.
 */
let boundRouter = null;

export function bindAppRouter(router) {
    boundRouter = router;
}

function getBoundRouter() {
    if (!boundRouter) {
        throw new Error("App router has not been bound yet. Call bindAppRouter() from the entry file.");
    }
    return boundRouter;
}

const routerFacade = new Proxy(
    {},
    {
        get(_target, prop) {
            const router = getBoundRouter();
            const value = router[prop];
            return typeof value === "function" ? value.bind(router) : value;
        },
        set(_target, prop, value) {
            const router = getBoundRouter();
            router[prop] = value;
            return true;
        },
    }
);

export default routerFacade;
