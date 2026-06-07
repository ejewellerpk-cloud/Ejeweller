const AUTO_RANDOM_SALE = 5;
const LEGACY_AUTO_RANDOM_SALE = 1;
const DISABLED_RANDOM_SALE_VALUES = new Set([0, 10]);

export function getActualSales(product) {
    if (!product) {
        return 0;
    }

    const value = product.actual_sales ?? product.order ?? 0;
    return parseInt(value, 10) || 0;
}

export function isRandomSaleDisabled(product) {
    if (!product) {
        return true;
    }

    const value = parseInt(product.use_random_sale, 10);
    return !Number.isFinite(value) || DISABLED_RANDOM_SALE_VALUES.has(value);
}

export function getRandomStartingPoint(product) {
    if (!product || isRandomSaleDisabled(product)) {
        return 0;
    }

    const randomSaleValue = parseInt(product.use_random_sale, 10);

    if (randomSaleValue === AUTO_RANDOM_SALE || randomSaleValue === LEGACY_AUTO_RANDOM_SALE) {
        return ((product.id * 53) % 450 + 138);
    }

    return randomSaleValue;
}

export function getDisplaySoldCount(product, options = {}) {
    const useLocalStorage = options.useLocalStorage !== false;
    const actual = getActualSales(product);

    if (!product || isRandomSaleDisabled(product)) {
        return actual;
    }

    const startingPoint = getRandomStartingPoint(product);
    const combined = startingPoint + actual;

    if (!useLocalStorage) {
        return combined;
    }

    const storageKey = 'sold_count_' + product.id;
    let localCount = parseInt(localStorage.getItem(storageKey), 10);

    if (!Number.isFinite(localCount) || localCount < combined) {
        localCount = combined;
        localStorage.setItem(storageKey, String(localCount));
    }

    return localCount;
}

export function shouldShowSoldCount(product) {
    if (!product) {
        return false;
    }

    if (isRandomSaleDisabled(product) && getActualSales(product) === 0) {
        return false;
    }

    return true;
}

export function shouldShowActualSales(product) {
    if (!product) {
        return false;
    }

    return !isRandomSaleDisabled(product) && getActualSales(product) > 0;
}
