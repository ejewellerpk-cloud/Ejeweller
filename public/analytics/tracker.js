/**
 * Shopperzz Analytics Tracker v1
 * Async, SPA-friendly, batched ingestion with offline retry.
 */
(function (window, document) {
    'use strict';

    var w = window;
    var cfg = w.__ANALYTICS__ || {};
    var siteKey = cfg.siteKey || cfg.key || '';
    var endpoint = cfg.endpoint || '/api/analytics/v1/collect';
    var userId = cfg.userId || null;
    var batchSize = cfg.batchSize || 20;
    var flushMs = cfg.flushInterval || 3000;
    var debug = !!cfg.debug;

    if (!siteKey) {
        if (debug) console.warn('[analytics] missing siteKey');
        return;
    }

    var lastSiteKey = storageGet('analytics_site_key');
    if (lastSiteKey && lastSiteKey !== siteKey) {
        try {
            storageSet('analytics_offline', '[]');
        } catch (e) {}
        log('site key changed — cleared offline queue');
    }
    storageSet('analytics_site_key', siteKey);

    var queue = [];
    var flushing = false;
    var lastPageView = 0;
    var sessionId = storageGet('analytics_session') || uuid();
    var visitorId = storageGet('analytics_visitor') || uuid();
    storageSet('analytics_session', sessionId);
    storageSet('analytics_visitor', visitorId);

    function uuid() {
        if (w.crypto && w.crypto.randomUUID) return w.crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            var v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    function storageGet(k) {
        try { return w.localStorage.getItem(k); } catch (e) { return null; }
    }

    function storageSet(k, v) {
        try { w.localStorage.setItem(k, v); } catch (e) {}
    }

    function log() {
        if (debug) console.log.apply(console, ['[analytics]'].concat([].slice.call(arguments)));
    }

    function context() {
        var nav = w.navigator || {};
        var scr = w.screen || {};
        var params = new URLSearchParams(w.location.search);
        return {
            page_url: w.location.href,
            page_path: w.location.pathname,
            referrer: document.referrer || null,
            utm_source: params.get('utm_source'),
            utm_medium: params.get('utm_medium'),
            utm_campaign: params.get('utm_campaign'),
            utm_content: params.get('utm_content'),
            utm_term: params.get('utm_term'),
            user_agent: nav.userAgent,
            language: nav.language,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            screen: scr.width + 'x' + scr.height,
            device_type: /Mobi|Android/i.test(nav.userAgent) ? 'mobile' : 'desktop',
            scroll_depth: maxScrollDepth
        };
    }

    var maxScrollDepth = 0;
    function trackScroll() {
        var h = document.documentElement;
        var scrolled = (h.scrollTop + w.innerHeight) / Math.max(h.scrollHeight, 1) * 100;
        maxScrollDepth = Math.min(100, Math.max(maxScrollDepth, Math.round(scrolled)));
    }
    w.addEventListener('scroll', throttle(trackScroll, 500), { passive: true });

    function buildEvent(name, category, props) {
        props = props || {};
        return {
            event_uuid: uuid(),
            event_name: name,
            event_category: category || 'general',
            page_url: w.location.href,
            page_title: document.title,
            occurred_at: new Date().toISOString(),
            properties: props,
            product_id: props.product_id || null,
            product_sku: props.product_sku || null,
            revenue: props.revenue || null,
            currency: props.currency || null,
            order_id: props.order_id || null
        };
    }

    function enqueue(ev) {
        queue.push(ev);
        if (queue.length >= batchSize) flush();
    }

    function payload(events) {
        return JSON.stringify({
            site_key: siteKey,
            session_id: sessionId,
            visitor_id: visitorId,
            user_id: userId,
            events: events,
            context: context()
        });
    }

    function sendBeacon(body) {
        try {
            if (w.navigator && w.navigator.sendBeacon) {
                var blob = new Blob([body], { type: 'application/json' });
                return w.navigator.sendBeacon(endpoint, blob);
            }
        } catch (e) {}
        return false;
    }

    function sendFetch(body) {
        return fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Analytics-Key': siteKey,
                Accept: 'application/json'
            },
            body: body,
            keepalive: true,
            credentials: 'omit'
        }).then(function (r) {
            if (r.ok) return true;
            if (r.status >= 400 && r.status < 500) {
                log('collect rejected', r.status);
                return 'reject';
            }
            log('collect server error', r.status);
            return false;
        }).catch(function () { return false; });
    }

    function persistOffline(body) {
        try {
            var key = 'analytics_offline';
            var arr = JSON.parse(storageGet(key) || '[]');
            arr.push(body);
            if (arr.length > 50) arr = arr.slice(-50);
            storageSet(key, JSON.stringify(arr));
        } catch (e) {}
    }

    function flushOffline() {
        try {
            var key = 'analytics_offline';
            var arr = JSON.parse(storageGet(key) || '[]');
            if (!arr.length) return;
            storageSet(key, '[]');
            var maxReplay = 10;
            arr.slice(0, maxReplay).forEach(function (body) {
                try {
                    var parsed = JSON.parse(body);
                    if (!parsed.events || !parsed.events.length) return;
                    parsed.site_key = siteKey;
                    sendFetch(JSON.stringify(parsed)).then(function (ok) {
                        if (ok === 'reject') return;
                        if (!ok) persistOffline(JSON.stringify(parsed));
                    });
                } catch (e) {
                    log('skip corrupt offline batch');
                }
            });
        } catch (e) {}
    }

    function flush(useBeacon) {
        if (flushing || !queue.length) return;
        flushing = true;
        var batch = queue.splice(0, batchSize);
        var body = payload(batch);
        var done = function () { flushing = false; if (queue.length) flush(); };

        if (useBeacon && sendBeacon(body)) {
            done();
            return;
        }

        sendFetch(body).then(function (ok) {
            if (ok === false) persistOffline(body);
            done();
        });
    }

    function throttle(fn, wait) {
        var t = 0;
        return function () {
            var now = Date.now();
            if (now - t >= wait) { t = now; fn(); }
        };
    }

    function debounce(fn, wait) {
        var timer;
        return function () {
            clearTimeout(timer);
            timer = setTimeout(fn, wait);
        };
    }

    var pageView = debounce(function () {
        var now = Date.now();
        if (now - lastPageView < 800) return;
        lastPageView = now;
        enqueue(buildEvent('page_view', 'page', { path: w.location.pathname }));
    }, 300);

    function track(name, category, props) {
        enqueue(buildEvent(name, category || 'general', props || {}));
    }

    function identify(id) {
        userId = id;
        cfg.userId = id;
    }

    function ecommerce(name, props) {
        track(name, 'ecommerce', props || {});
    }

    w.Analytics = w.Analytics || {
        track: track,
        page: pageView,
        identify: identify,
        ecommerce: ecommerce,
        flush: function () { flush(false); },
        getSessionId: function () { return sessionId; },
        getVisitorId: function () { return visitorId; }
    };

    setInterval(function () { flush(false); }, flushMs);
    w.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') flush(true);
    });
    w.addEventListener('pagehide', function () { flush(true); });

    if (w.history && w.history.pushState) {
        var push = w.history.pushState;
        w.history.pushState = function () {
            push.apply(w.history, arguments);
            pageView();
        };
        var replace = w.history.replaceState;
        w.history.replaceState = function () {
            replace.apply(w.history, arguments);
            pageView();
        };
        w.addEventListener('popstate', pageView);
    }

    pageView();
    flushOffline();

    log('initialized', { sessionId: sessionId, visitorId: visitorId });
})(window, document);
