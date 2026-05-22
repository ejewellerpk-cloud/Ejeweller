export const pixelService = {
    init: function() {
        // If fbq is already defined globally, do nothing
        if (typeof window.fbq === 'function') {
            return;
        }

        // Check if there is a global pixel id injected from backend or env config
        const pixelId = window.FACEBOOK_PIXEL_ID || import.meta.env.VITE_FACEBOOK_PIXEL_ID;
        if (!pixelId) {
            return;
        }

        // Standard Facebook Pixel injection snippet
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');

        window.fbq('init', pixelId);
        window.fbq('track', 'PageView');
        console.log(`[Pixel] Facebook Pixel (${pixelId}) initialized successfully.`);
    },

    track: function(eventName, properties = {}) {
        this.init(); // Auto-init if not already done
        
        // Dispatch to Facebook Pixel (Browser)
        if (typeof window.fbq === 'function') {
            window.fbq('track', eventName, properties);
        }

        // Dispatch to Server for Facebook CAPI
        this.sendCapiEvent(eventName, properties);
    },

    sendCapiEvent: function(eventName, customData) {
        // Send event to backend API for Server-Side tracking
        fetch('/api/frontend/capi/event', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                event_name: eventName,
                custom_data: customData,
                event_id: eventName.toLowerCase() + '_' + Date.now() + '_' + Math.floor(Math.random() * 1000)
            })
        }).catch(err => console.error('CAPI Event Error:', err));
    },

    trackPageView: function() {
        this.track('PageView');
    },

    trackViewContent: function(product) {
        if (!product) return;
        this.track('ViewContent', {
            content_name: product.name,
            content_category: product.category_slug || 'Products',
            content_ids: [product.id],
            content_type: 'product',
            value: parseFloat(product.price) || 0,
            currency: window.FACEBOOK_PIXEL_CURRENCY || 'PKR'
        });
    },

    trackAddToCart: function(product, quantity = 1) {
        if (!product) return;
        this.track('AddToCart', {
            content_name: product.name,
            content_ids: [product.product_id || product.id],
            content_type: 'product',
            value: (parseFloat(product.price) || 0) * quantity,
            currency: window.FACEBOOK_PIXEL_CURRENCY || 'PKR'
        });
    },

    trackInitiateCheckout: function(cartItems, totalValue) {
        if (!cartItems || cartItems.length === 0) return;
        const ids = cartItems.map(item => item.product_id || item.id);
        this.track('InitiateCheckout', {
            content_ids: ids,
            content_type: 'product',
            value: parseFloat(totalValue) || 0,
            currency: window.FACEBOOK_PIXEL_CURRENCY || 'PKR',
            num_items: cartItems.length
        });
    },

    trackPurchase: function(order) {
        if (!order) return;
        const ids = order.order_items ? order.order_items.map(item => item.product_id) : [];
        this.track('Purchase', {
            content_ids: ids,
            content_type: 'product',
            value: parseFloat(order.total) || 0,
            currency: window.FACEBOOK_PIXEL_CURRENCY || 'PKR',
            transaction_id: order.order_serial_no || order.id_serial || order.id
        });
    }
};
