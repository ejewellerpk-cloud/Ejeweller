export function getActualSales(product) {
    if (!product) {
        return 0;
    }

    const value = product.actual_sales ?? product.order ?? 0;
    return parseInt(value, 10) || 0;
}

export function getDisplaySoldCount(product, options = {}) {
    const useLocalStorage = options.useLocalStorage !== false;
    const actual = getActualSales(product);
    const randomSaleValue = parseInt(product?.use_random_sale, 10);

    if (!product || randomSaleValue === 10 || randomSaleValue === 0) {
        return actual;
    }

    const startingPoint = randomSaleValue === 5
        ? ((product.id * 53) % 450 + 138)
        : randomSaleValue;

    if (!useLocalStorage) {
        return startingPoint + actual;
    }

    const storageKey = 'sold_count_' + product.id;
    let localCount = localStorage.getItem(storageKey);

    if (!localCount || parseInt(localCount, 10) < startingPoint) {
        localCount = String(startingPoint + actual);
        localStorage.setItem(storageKey, localCount);
    }

    return parseInt(localCount, 10);
}

export function shouldShowSoldCount(product) {
    if (!product) {
        return false;
    }

    const randomSaleValue = parseInt(product.use_random_sale, 10);
    const isRandomSaleOff = randomSaleValue === 10 || randomSaleValue === 0;

    if (isRandomSaleOff && getActualSales(product) === 0) {
        return false;
    }

    return true;
}

export function shouldShowActualSales(product) {
    if (!product) {
        return false;
    }

    const randomSaleValue = parseInt(product.use_random_sale, 10);
    const isRandomSaleOff = randomSaleValue === 10 || randomSaleValue === 0;

    return !isRandomSaleOff && getActualSales(product) > 0;
}
