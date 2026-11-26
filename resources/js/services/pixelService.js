import axios from 'axios';

function makeEventId(eventName, seed = '') {
    const base = String(seed || `${eventName}_${Date.now()}_${Math.floor(Math.random() * 1e6)}`);
    return String(base).replace(/\s+/g, '_').slice(0, 100);
}

function readAdvancedMatching() {
    const user = window.__PIXEL_USER__ || {};
    const em = (user.email || '').trim().toLowerCase();
    const ph = String(user.phone || '').replace(/\D+/g, '');
    const fn = (user.first_name || '').trim().toLowerCase();
    const ln = (user.last_name || '').trim().toLowerCase();
    const match = {};
    if (em) match.em = em;
    if (ph) match.ph = ph;
    if (fn) match.fn = fn;
    if (ln) match.ln = ln;
    return match;
}

export const pixelService = {
    init: function () {
        if (typeof window.fbq === 'function') {
            return;
        }

        const pixelId = window.FACEBOOK_PIXEL_ID || import.meta.env.VITE_FACEBOOK_PIXEL_ID;
        if (!pixelId) {
            return;
        }

        !function (f, b, e, v, n, t, s) {
            if (f.fbq) return; n = f.fbq = function () {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0';
            n.queue = []; t = b.createElement(e); t.async = !0;
            t.src = v; s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');

        const advanced = readAdvancedMatching();
        if (Object.keys(advanced).length) {
            window.fbq('init', pixelId, advanced);
        } else {
            window.fbq('init', pixelId);
        }
        window.fbq('track', 'PageView', {}, { eventID: makeEventId('PageView', 'pv_' + Date.now()) });
    },

    setUserData: function (userData = {}) {
        window.__PIXEL_USER__ = { ...(window.__PIXEL_USER__ || {}), ...userData };
    },

    track: function (eventName, properties = {}, options = {}) {
        this.init();

        const eventId = options.eventID || options.event_id || makeEventId(
            eventName,
            properties.transaction_id || properties.order_id || ''
        );

        if (typeof window.fbq === 'function') {
            window.fbq('track', eventName, properties, { eventID: eventId });
        }

        this.sendCapiEvent(eventName, properties, eventId);
        return eventId;
    },

    sendCapiEvent: function (eventName, customData, eventId) {
        const userData = { ...(window.__PIXEL_USER__ || {}) };
        axios.post('frontend/capi/event', {
            event_name: eventName,
            custom_data: customData,
            event_id: eventId || makeEventId(eventName),
            user_data: userData,
            event_source_url: window.location.href,
        }).catch((err) => console.error('CAPI Event Error:', err));
    },

    trackPageView: function () {
        this.track('PageView');
    },

    trackViewContent: function (product) {
        if (!product) return;
        this.track('ViewContent', {
            content_name: product.name,
            content_category: product.category_slug || 'Products',
            content_ids: [product.id],
            content_type: 'product',
            value: parseFloat(product.price) || 0,
            currency: window.FACEBOOK_PIXEL_CURRENCY || 'PKR'
        }, { eventID: makeEventId('ViewContent', 'vc_' + product.id) });
    },

    trackAddToCart: function (product, quantity = 1) {
        if (!product) return;
        const id = product.product_id || product.id;
        this.track('AddToCart', {
            content_name: product.name,
            content_ids: [id],
            content_type: 'product',
            value: (parseFloat(product.price) || 0) * quantity,
            currency: window.FACEBOOK_PIXEL_CURRENCY || 'PKR'
        }, { eventID: makeEventId('AddToCart', 'atc_' + id + '_' + Date.now()) });
    },

    trackInitiateCheckout: function (cartItems, totalValue) {
        if (!cartItems || cartItems.length === 0) return;
        const ids = cartItems.map((item) => item.product_id || item.id);
        this.track('InitiateCheckout', {
            content_ids: ids,
            content_type: 'product',
            value: parseFloat(totalValue) || 0,
            currency: window.FACEBOOK_PIXEL_CURRENCY || 'PKR',
            num_items: cartItems.length
        }, { eventID: makeEventId('InitiateCheckout', 'ic_' + Date.now()) });
    },

    trackPurchase: function (order) {
        if (!order) return;
        const ids = order.order_items ? order.order_items.map((item) => item.product_id) : [];
        const serial = order.order_serial_no || order.id_serial || order.id;
        this.track('Purchase', {
            content_ids: ids,
            content_type: 'product',
            value: parseFloat(order.total) || 0,
            currency: window.FACEBOOK_PIXEL_CURRENCY || 'PKR',
            transaction_id: serial
        }, { eventID: 'purchase_' + serial });
    }
};
