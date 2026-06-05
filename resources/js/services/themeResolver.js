export function isAdminPath(path) {
    const normalized = path || (typeof window !== 'undefined' ? window.location.pathname : '');
    return normalized.startsWith('/admin') || normalized === '/exception';
}

export function resolveThemeFromRoute(route) {
    if (!route?.matched?.length) {
        return isAdminPath(route?.path) ? 'backend' : 'frontend';
    }

    if (route.meta?.isFrontend === true) {
        return 'frontend';
    }

    if (route.name === 'route.exception' || isAdminPath(route.path)) {
        return 'backend';
    }

    return 'frontend';
}
