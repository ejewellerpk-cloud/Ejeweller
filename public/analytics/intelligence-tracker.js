/**
 * Shopperzz Intelligence Tracker — heatmaps + session replay (lightweight)
 * Requires window.__ANALYTICS__ and tracker.js (Analytics session/visitor IDs)
 */
(function (w, document) {
    'use strict';

    var cfg = w.__ANALYTICS__ || {};
    if (!cfg.siteKey) return;

    var endpoint = (cfg.endpoint || '/api/analytics/v1/collect').replace(/\/collect\/?$/, '/collect/behavior');
    var queue = [];
    var flushMs = cfg.behaviorFlushMs || 5000;
    var maxBatch = cfg.behaviorBatchSize || 40;
    var rageClicks = {};
    var lastClick = { t: 0, x: 0, y: 0, n: 0 };
    var started = false;

    function sessionId() {
        return w.Analytics && w.Analytics.getSessionId ? w.Analytics.getSessionId() : null;
    }

    function visitorId() {
        return w.Analytics && w.Analytics.getVisitorId ? w.Analytics.getVisitorId() : null;
    }

    function deviceType() {
        return /Mobi|Android/i.test(navigator.userAgent) ? 'mobile' : 'desktop';
    }

    function pagePath() {
        return w.location.pathname + w.location.search;
    }

    function pct(client, size) {
        if (!size) return 0;
        return Math.round((client / size) * 1000) / 10;
    }

    function isMasked(el) {
        if (!el || !el.closest) return false;
        return !!el.closest('[data-analytics-mask],input[type=password],input[name*=card],input[autocomplete*=cc]');
    }

    function enqueue(type, data) {
        queue.push({
            type: type,
            page_path: pagePath(),
            viewport_w: w.innerWidth,
            viewport_h: w.innerHeight,
            device_type: deviceType(),
            occurred_at: new Date().toISOString(),
            data: data || {},
        });
        if (queue.length >= maxBatch) flush();
    }

    function buildPayload(batch) {
        return JSON.stringify({
            site_key: cfg.siteKey,
            session_id: sessionId(),
            visitor_id: visitorId(),
            events: batch,
        });
    }

    function sendPayload(body) {
        if (navigator.sendBeacon) {
            try {
                var blob = new Blob([body], { type: 'application/json' });
                if (navigator.sendBeacon(endpoint, blob)) {
                    return Promise.resolve();
                }
            } catch (e) {}
        }

        return fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Analytics-Key': cfg.siteKey,
                Accept: 'application/json',
            },
            body: body,
            credentials: 'omit',
            keepalive: true,
        }).catch(function () {});
    }

    function flush() {
        if (!queue.length || !sessionId()) return;
        var batch = queue.splice(0, maxBatch);
        sendPayload(buildPayload(batch));
    }

    function bindListeners() {
        if (started) return;
        started = true;

        document.addEventListener('click', function (e) {
            if (isMasked(e.target)) return;
            var now = Date.now();
            var x = pct(e.clientX, w.innerWidth);
            var y = pct(e.clientY, document.documentElement.scrollHeight || w.innerHeight);
            if (now - lastClick.t < 800 && Math.abs(e.clientX - lastClick.x) < 30 && Math.abs(e.clientY - lastClick.y) < 30) {
                lastClick.n++;
                if (lastClick.n >= 3) {
                    enqueue('rage_click', { x_pct: x, y_pct: y });
                    lastClick.n = 0;
                    return;
                }
            } else {
                lastClick = { t: now, x: e.clientX, y: e.clientY, n: 1 };
            }
            var tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
            var dead = ['a', 'button', 'input', 'select', 'textarea'].indexOf(tag) === -1 && !e.target.closest('a,button,[role=button]');
            enqueue(dead ? 'dead_click' : 'click', { x_pct: x, y_pct: y, tag: tag });
        }, true);

        var scrollTimer;
        w.addEventListener('scroll', function () {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(function () {
                var h = document.documentElement;
                var depth = Math.round(((h.scrollTop + w.innerHeight) / Math.max(h.scrollHeight, 1)) * 100);
                enqueue('scroll_depth', { depth: depth });
            }, 400);
        }, { passive: true });

        var replayBuffer = [];
        function recordReplay(type, payload) {
            replayBuffer.push({ type: 'replay_' + type, t: Date.now(), data: payload });
            if (replayBuffer.length >= 25) {
                replayBuffer.forEach(function (ev) {
                    enqueue(ev.type, ev.data);
                });
                replayBuffer = [];
            }
        }

        document.addEventListener('mousemove', function (e) {
            if (Math.random() > 0.92) {
                recordReplay('move', { x_pct: pct(e.clientX, w.innerWidth), y_pct: pct(e.clientY, w.innerHeight) });
            }
        }, { passive: true });

        w.addEventListener('resize', function () {
            recordReplay('resize', { w: w.innerWidth, h: w.innerHeight });
        });

        var pushState = w.history.pushState;
        if (pushState) {
            w.history.pushState = function () {
                pushState.apply(w.history, arguments);
                recordReplay('route', { path: pagePath() });
            };
        }

        setInterval(flush, flushMs);
        w.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') flush();
        });
        w.addEventListener('pagehide', function () {
            flush();
        });
    }

    function waitForSession() {
        if (sessionId()) {
            bindListeners();
            return;
        }

        var tries = 0;
        var timer = setInterval(function () {
            tries++;
            if (sessionId()) {
                clearInterval(timer);
                bindListeners();
            } else if (tries >= 40) {
                clearInterval(timer);
            }
        }, 250);
    }

    waitForSession();

    w.AnalyticsIntelligence = { flush: flush, track: enqueue };
})(window, document);
