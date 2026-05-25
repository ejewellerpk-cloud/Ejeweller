/**
 * Shared product discount / offer helpers (must match AppLibrary::productOfferPrice on backend).
 */

export function parseAmount(value) {
    if (value == null || value === '') {
        return 0;
    }
    if (typeof value === 'number' && !isNaN(value)) {
        return value;
    }
    return parseFloat(String(value).replace(/[^0-9.-]/g, '')) || 0;
}

/**
 * Active offer only when API says is_offer (dates + discount validated server-side).
 */
export function hasActiveDiscount(product) {
    if (!product || !product.is_offer) {
        return false;
    }
    return discountPercentage(product) > 0;
}

export function discountPercentage(product) {
    if (!product || !product.is_offer) {
        return 0;
    }

    const fromField = parseFloat(product.discount);
    if (!isNaN(fromField) && fromField > 0) {
        return Math.round(fromField);
    }

    const fromPercent = parseFloat(product.discount_percentage);
    if (!isNaN(fromPercent) && fromPercent > 0) {
        return Math.round(fromPercent);
    }

    const oldVal = parseAmount(product.old_price);
    const newVal = parseAmount(product.price);
    if (oldVal > newVal && oldVal > 0) {
        return Math.round(((oldVal - newVal) / oldVal) * 100);
    }

    const originalFormatted = parseAmount(product.currency_price);
    const saleFormatted = parseAmount(product.discounted_price);
    if (originalFormatted > saleFormatted && originalFormatted > 0) {
        return Math.round(((originalFormatted - saleFormatted) / originalFormatted) * 100);
    }

    return 0;
}

/** List/card display: sale price + original strikethrough */
export function getListPrices(product) {
    const onSale = hasActiveDiscount(product);
    return {
        onSale,
        salePrice: onSale ? (product.discounted_price || product.currency_price) : product.currency_price,
        originalPrice: product.currency_price,
        percent: discountPercentage(product),
    };
}

/** Detail page display */
export function getDetailPrices(product) {
    const onSale = hasActiveDiscount(product);
    return {
        onSale,
        salePrice: product.currency_price || '',
        originalPrice: product.old_currency_price || product.currency_price || '',
        percent: discountPercentage(product),
    };
}
