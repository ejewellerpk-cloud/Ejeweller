/**
 * Shared product discount / offer helpers for storefront components.
 */
export function discountPercentage(product) {
    if (!product) {
        return 0;
    }
    if (product.old_price && product.price && product.old_price > product.price) {
        return Math.round(((product.old_price - product.price) / product.old_price) * 100);
    }
    const discount = parseFloat(product.discount);
    if (!isNaN(discount) && discount > 0) {
        return Math.round(discount);
    }
    if (product.discount_percentage) {
        return Math.round(parseFloat(product.discount_percentage));
    }
    if (product.is_offer && product.discounted_price && product.currency_price) {
        const oldVal = parseFloat(String(product.currency_price).replace(/[^0-9.]/g, ''));
        const newVal = parseFloat(String(product.discounted_price).replace(/[^0-9.]/g, ''));
        if (oldVal > newVal && oldVal > 0) {
            return Math.round(((oldVal - newVal) / oldVal) * 100);
        }
    }
    return 0;
}

export function hasActiveDiscount(product) {
    if (!product) {
        return false;
    }
    if (product.is_offer) {
        return discountPercentage(product) > 0;
    }
    const d = parseFloat(product.discount);
    if (!isNaN(d) && d > 0) {
        return true;
    }
    return discountPercentage(product) > 0;
}
