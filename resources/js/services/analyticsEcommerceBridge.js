/**
 * Bridges Shopperzz storefront actions to window.Analytics ecommerce events.
 * Event names must match App\Analytics\Enums\AnalyticsEcommerceEvent.
 */

function ecommerce(name, props = {}) {
    if (!window.Analytics) return;
    const payload = { ...props };
    if (payload.product_id == null && payload.id != null) {
        payload.product_id = payload.id;
    }
    if (payload.product_sku == null && payload.sku != null) {
        payload.product_sku = payload.sku;
    }
    window.Analytics.ecommerce(name, payload);
}

export function trackProductViewed(product) {
    if (!product) return;
    ecommerce('product_viewed', {
        product_id: product.id,
        product_sku: product.sku,
        name: product.name,
    });
}

export function trackCategoryViewed(category = {}) {
    ecommerce('category_viewed', {
        category_id: category.id ?? null,
        category_slug: category.slug ?? null,
        name: category.name ?? null,
    });
}

export function trackSearchPerformed(query, extra = {}) {
    const q = typeof query === 'string' ? query.trim() : '';
    if (!q) return;
    ecommerce('search_performed', { query: q, ...extra });
}

export function trackAddToCart(product, quantity = 1) {
    if (!product) return;
    ecommerce('add_to_cart', {
        product_id: product.id ?? product.product_id,
        product_sku: product.sku,
        quantity,
    });
}

export function trackRemoveFromCart(product, quantity = 1) {
    if (!product) return;
    ecommerce('remove_from_cart', {
        product_id: product.id ?? product.product_id,
        product_sku: product.sku,
        quantity,
    });
}

export function trackWishlistToggle(product, added) {
    if (!product) return;
    ecommerce(added ? 'add_to_wishlist' : 'remove_wishlist', {
        product_id: product.id ?? product.product_id,
        product_sku: product.sku,
    });
}

export function trackCheckoutStarted(cartTotal, currency) {
    ecommerce('checkout_started', { revenue: cartTotal, currency });
}

export function trackCheckoutAbandoned(cartTotal, currency) {
    ecommerce('checkout_abandoned', { revenue: cartTotal, currency });
}

export function trackPaymentAttempted({ orderId, method, total, currency } = {}) {
    ecommerce('payment_attempted', {
        order_id: orderId ?? null,
        payment_method: method ?? null,
        revenue: total ?? null,
        currency: currency ?? null,
    });
}

export function trackCodSelected(total, currency) {
    ecommerce('cod_selected', { revenue: total, currency });
}

export function trackOrderPlaced(order) {
    if (!order) return;
    ecommerce('order_placed', {
        order_id: order.id,
        revenue: order.total,
        currency: order.currency_code ?? order.currency ?? null,
    });
}

export function trackOrderConfirmed(order) {
    if (!order) return;
    ecommerce('order_confirmed', {
        order_id: order.id,
        revenue: order.total,
        currency: order.currency_code ?? order.currency ?? null,
    });
}

/** Avoid duplicate order events (COD + success page + payment return). */
export function trackOrderCompletedOnce(order) {
    if (!order?.id) return;
    try {
        const key = `analytics_order_done_${order.id}`;
        if (sessionStorage.getItem(key)) return;
        sessionStorage.setItem(key, '1');
    } catch (e) {
        /* ignore */
    }
    trackOrderPlaced(order);
    trackOrderConfirmed(order);
}

export function identifyAnalyticsUser(userId) {
    if (window.Analytics && userId) {
        window.Analytics.identify(userId);
    }
}
