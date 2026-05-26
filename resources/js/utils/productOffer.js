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

/**
 * Numeric unit prices for cart/checkout (offer already applied to `price`).
 * `old_price` is the pre-offer base (variation selling price or product base).
 */
export function buildCartLinePricing(source) {
    if (!source) {
        return { price: 0, old_price: 0, offer_percent: 0 };
    }

    const price = parseAmount(source.price);
    let oldPrice = parseAmount(source.old_price);
    if (!oldPrice && source.old_currency_price) {
        oldPrice = parseAmount(source.old_currency_price);
    }

    const onSale = oldPrice > price && price > 0;
    if (!onSale) {
        oldPrice = price;
    }

    return {
        price,
        old_price: oldPrice,
        offer_percent: onSale ? discountPercentage(source) : 0,
    };
}

/** Merge cart item fields with normalized variation/product pricing. */
export function withCartLinePricing(itemFields, pricingSource) {
    const { price, old_price, offer_percent } = buildCartLinePricing(pricingSource);
    const quantity = parseInt(itemFields.quantity, 10) || 1;

    return {
        ...itemFields,
        price,
        old_price,
        offer_percent,
        discount: 0,
        total_price: price * quantity,
    };
}
