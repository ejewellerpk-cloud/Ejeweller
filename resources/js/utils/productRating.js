/**
 * Real product ratings from API (rating_star = sum of stars, rating_star_count = review count).
 */

export function hasProductRating(product) {
    const count = parseInt(product?.rating_star_count, 10) || 0;
    return count > 0;
}

export function getProductAverageRating(product) {
    if (product?.rating_star_average != null && product.rating_star_average !== '') {
        const fromApi = parseFloat(product.rating_star_average);
        if (Number.isFinite(fromApi)) {
            return Math.min(5, Math.max(0, Math.round(fromApi * 10) / 10));
        }
    }

    const count = parseInt(product?.rating_star_count, 10) || 0;
    if (count <= 0) {
        return null;
    }

    const sum = parseFloat(product?.rating_star) || 0;
    const average = sum / count;

    return Math.min(5, Math.max(0, Math.round(average * 10) / 10));
}

export function getStarFillCount(product) {
    const average = getProductAverageRating(product);
    if (average === null) {
        return 0;
    }

    return Math.min(5, Math.max(0, Math.round(average)));
}

export function formatProductRating(product) {
    const average = getProductAverageRating(product);
    return average === null ? null : average.toFixed(1);
}
