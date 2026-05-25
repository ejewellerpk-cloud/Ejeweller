/**
 * Product variation tree helpers (Shopify/Etsy-style option availability).
 */

export function normalizeVariationTree(apiData) {
    return Array.isArray(apiData) ? apiData : [];
}

export function findNodeByPath(tree, selectedIds) {
    let nodes = tree;
    let current = null;
    for (const id of selectedIds) {
        if (id == null || id === undefined) {
            break;
        }
        current = nodes.find((n) => Number(n.id) === Number(id));
        if (!current) {
            return null;
        }
        nodes = current.children || [];
    }
    return current;
}

export function findPathToVariationId(tree, targetId, path = []) {
    for (const node of tree) {
        const nextPath = [...path, node.id];
        if (Number(node.id) === Number(targetId)) {
            return nextPath;
        }
        const childPath = findPathToVariationId(node.children || [], targetId, nextPath);
        if (childPath) {
            return childPath;
        }
    }
    return null;
}

export function getOptionsForLevel(tree, selectedIds, level) {
    if (level === 0) {
        return tree;
    }
    const parent = findNodeByPath(tree, selectedIds.slice(0, level));
    return parent ? (parent.children || []) : [];
}

export function hasAvailableLeaf(node) {
    if (!node) {
        return false;
    }
    if (node.sku) {
        return (parseInt(node.stock, 10) || 0) > 0;
    }
    return (node.children || []).some((child) => hasAvailableLeaf(child));
}

export function isOptionAvailable(node, tree, selectedIds, level) {
    if (!node) {
        return false;
    }
    const path = [...selectedIds.slice(0, level), node.id];
    const candidate = findNodeByPath(tree, path);
    return hasAvailableLeaf(candidate);
}

export function getLeafFromPath(tree, selectedIds) {
    const node = findNodeByPath(tree, selectedIds.filter((id) => id != null));
    if (node && node.sku) {
        return node;
    }
    return null;
}

export function getAttributeName(options) {
    return options[0]?.product_attribute_name || '';
}

export function isColorAttribute(name) {
    return /color|colour|رنگ/i.test(String(name || ''));
}

/** First image on this node or any descendant (for color option thumbnails). */
export function getNodePreviewImage(node) {
    if (!node) {
        return null;
    }
    if (node.image) {
        return node.image;
    }
    const queue = [...(node.children || [])];
    while (queue.length) {
        const current = queue.shift();
        if (current?.image) {
            return current.image;
        }
        if (current?.children?.length) {
            queue.push(...current.children);
        }
    }
    return null;
}

export function collectLeafVariations(tree, path = [], out = []) {
    for (const node of tree) {
        const nextPath = [...path, node];
        if (node.sku) {
            out.push({ node, path: nextPath });
        }
        if (node.children?.length) {
            collectLeafVariations(node.children, nextPath, out);
        }
    }
    return out;
}

export function getVariationPriceRange(tree, parseAmount) {
    const leaves = collectLeafVariations(tree);
    const prices = leaves
        .map(({ node }) => parseAmount(node.price))
        .filter((p) => p > 0);
    if (!prices.length) {
        return null;
    }
    return {
        min: Math.min(...prices),
        max: Math.max(...prices),
    };
}

export function buildVisibleLevels(tree, selectedIds) {
    const levels = [];
    let levelIndex = 0;

    while (levelIndex < 12) {
        const options = getOptionsForLevel(tree, selectedIds, levelIndex);
        if (!options.length) {
            break;
        }

        levels.push({
            levelIndex,
            attributeName: getAttributeName(options),
            isColor: isColorAttribute(getAttributeName(options)),
            options: options.map((opt) => ({
                ...opt,
                available: isOptionAvailable(opt, tree, selectedIds, levelIndex),
                selected: Number(selectedIds[levelIndex]) === Number(opt.id),
            })),
        });

        if (selectedIds[levelIndex] == null) {
            break;
        }
        levelIndex++;
    }

    return levels;
}
