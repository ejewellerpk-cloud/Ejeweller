/**
 * Intelligence dashboard debug logging.
 * Disable in console: localStorage.setItem('intelligence_debug', '0'); location.reload();
 * Force enable: localStorage.setItem('intelligence_debug', '1'); location.reload();
 */
export function isIntelligenceDebugEnabled() {
    if (typeof window === 'undefined') {
        return import.meta.env.DEV;
    }
    if (window.__INTELLIGENCE_DEBUG__ === false) {
        return false;
    }
    if (window.__INTELLIGENCE_DEBUG__ === true) {
        return true;
    }
    const stored = localStorage.getItem('intelligence_debug');
    if (stored === '0') return false;
    if (stored === '1') return true;
    return true;
}

export function intelLog(label, payload) {
    if (!isIntelligenceDebugEnabled()) return;
    if (payload !== undefined) {
        console.log('[Intelligence]', label, payload);
    } else {
        console.log('[Intelligence]', label);
    }
}

export function intelWarn(label, payload) {
    if (!isIntelligenceDebugEnabled()) return;
    console.warn('[Intelligence]', label, payload ?? '');
}

export function intelError(label, payload) {
    if (!isIntelligenceDebugEnabled()) return;
    console.error('[Intelligence]', label, payload ?? '');
}

export function intelAuthContext() {
    let hasToken = false;
    let language = null;
    try {
        const vuex = JSON.parse(localStorage.getItem('vuex') || '{}');
        hasToken = !!vuex?.auth?.authToken;
        language = vuex?.globalState?.lists?.language_code ?? null;
    } catch (e) {
        intelWarn('authContext parse failed', e.message);
    }
    return {
        hasToken,
        language,
        appUrl: typeof window !== 'undefined' ? window.APP_URL : null,
        apiKeyPresent: !!(typeof window !== 'undefined' && window.APP_KEY) || !!import.meta.env.VITE_API_KEY,
        apiKeyPrefix: (typeof window !== 'undefined' && window.APP_KEY)
            ? String(window.APP_KEY).slice(0, 8) + '…'
            : (import.meta.env.VITE_API_KEY ? String(import.meta.env.VITE_API_KEY).slice(0, 8) + '…' : null),
    };
}

export function intelAxiosMeta(errOrRes) {
    const res = errOrRes?.response ?? errOrRes;
    if (!res) {
        return { message: errOrRes?.message || 'No response' };
    }
    return {
        status: res.status,
        statusText: res.statusText,
        url: res.config?.url,
        method: res.config?.method,
        params: res.config?.params,
        success: res.data?.success,
        message: res.data?.message,
        dataKeys: res.data?.data && typeof res.data.data === 'object'
            ? Object.keys(res.data.data)
            : null,
    };
}
