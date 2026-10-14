import { isAdminPath } from "./themeResolver";

export { isAdminPath };

/**
 * Hard-navigate between storefront and admin bundles.
 * Client-side Vue Router cannot load the other entry's code.
 */
export function navigateToZone(path) {
    const target = path || "/";
    if (typeof window === "undefined") {
        return;
    }
    if (window.location.pathname + window.location.search + window.location.hash === target) {
        return;
    }
    window.location.assign(target);
}

export function adminHref(pathOrUrl = "dashboard") {
    const cleaned = String(pathOrUrl || "dashboard").replace(/^\/+/, "");
    if (cleaned.startsWith("admin/")) {
        return "/" + cleaned;
    }
    return "/admin/" + cleaned;
}

export function isAdminTarget(to) {
    if (!to) {
        return false;
    }
    if (typeof to === "string") {
        return isAdminPath(to) || to === "/exception";
    }
    if (to.name && String(to.name).startsWith("admin.")) {
        return true;
    }
    if (to.name === "route.exception") {
        return true;
    }
    if (to.path) {
        return isAdminPath(to.path);
    }
    return false;
}

export function resolveLoginRedirect(queryRedirect) {
    if (!queryRedirect || typeof queryRedirect !== "string") {
        return null;
    }
    if (!queryRedirect.startsWith("/") || queryRedirect.startsWith("//")) {
        return null;
    }
    return queryRedirect;
}
