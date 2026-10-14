/**
 * Legacy entry kept for Vite HMR / older references.
 * Production Blade loads storefront.js or admin.js by path.
 */
import { isAdminPath } from "./services/appZone";

if (isAdminPath(window.location.pathname)) {
    import("./admin.js");
} else {
    import("./storefront.js");
}
