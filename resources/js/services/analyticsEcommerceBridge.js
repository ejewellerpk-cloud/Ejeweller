/**
 * Bridges Shopperzz storefront actions to window.Analytics ecommerce events.
 */
export function trackProductViewed(product) {
    if (!window.Analytics || !product) return;
    window.Analytics.ecommerce('product_viewed', {
        product_id: product.id,
        product_sku: product.sku,
        name: product.name,
    });
}

export function trackAddToCart(product, quantity = 1) {
    if (!window.Analytics || !product) return;
    window.Analytics.ecommerce('add_to_cart', {
        product_id: product.id,
        product_sku: product.sku,
        quantity,
    });
}

export function trackCheckoutStarted(cartTotal, currency) {
    if (!window.Analytics) return;
    window.Analytics.ecommerce('checkout_started', { revenue: cartTotal, currency });
}

export function trackOrderPlaced(order) {
    if (!window.Analytics || !order) return;
    window.Analytics.ecommerce('order_placed', {
        order_id: order.id,
        revenue: order.total,
        currency: order.currency_code,
    });
}

export function identifyAnalyticsUser(userId) {
    if (window.Analytics && userId) {
        window.Analytics.identify(userId);
    }
}
