/**
 * Build Shopify/Etsy-style option groups and leaf variants from the API variation tree.
 */

function normalizeNode(node) {
    return {
        id: node.id,
        product_attribute_id: node.product_attribute_id,
        product_attribute_option_id: node.product_attribute_option_id,
        product_attribute_name: node.product_attribute_name || '',
        product_attribute_option_name: node.product_attribute_option_name || '',
        price: node.price,
        old_price: node.old_price,
        currency_price: node.currency_price,
        old_currency_price: node.old_currency_price,
        discount_percentage: node.discount_percentage ?? 0,
        sku: node.sku,
        stock: Number(node.stock ?? 0),
        image: node.image,
        maximum_purchase_quantity: node.maximum_purchase_quantity,
        children: Array.isArray(node.children) ? node.children : [],
    };
}

export function buildOptionGroups(tree) {
    const roots = (tree || []).map(normalizeNode);
    if (!roots.length) {
        return { groups: [], leaves: [] };
    }

    const groups = [];
    let levelNodes = roots;

    while (levelNodes.length) {
        const attributeId = levelNodes[0].product_attribute_id;
        const attributeName = levelNodes[0].product_attribute_name;
        const optionsMap = new Map();

        levelNodes.forEach((node) => {
            const key = node.product_attribute_option_id;
            if (!optionsMap.has(key)) {
                optionsMap.set(key, {
                    optionId: node.product_attribute_option_id,
                    optionName: node.product_attribute_option_name,
                    sampleNodeId: node.id,
                });
            }
        });

        groups.push({
            attributeId,
            name: attributeName,
            options: Array.from(optionsMap.values()),
        });

        const nextLevel = [];
        levelNodes.forEach((node) => {
            node.children.forEach((child) => nextLevel.push(normalizeNode(child)));
        });
        levelNodes = nextLevel;
    }

    return { groups, leaves: flattenLeaves(roots) };
}

export function flattenLeaves(nodes, path = []) {
    const leaves = [];
    (nodes || []).forEach((raw) => {
        const node = normalizeNode(raw);
        const step = {
            attributeId: node.product_attribute_id,
            optionId: node.product_attribute_option_id,
            optionName: node.product_attribute_option_name,
            attributeName: node.product_attribute_name,
        };
        const nextPath = [...path, step];

        if (node.children.length) {
            leaves.push(...flattenLeaves(node.children, nextPath));
        } else if (node.sku) {
            leaves.push({ variant: node, path: nextPath });
        }
    });
    return leaves;
}

export function selectionKey(selected) {
    return Object.keys(selected)
        .sort()
        .map((k) => `${k}:${selected[k]}`)
        .join('|');
}

export function findMatchingLeaf(leaves, selected, groupCount) {
    const keys = Object.keys(selected);
    if (keys.length < groupCount) {
        return null;
    }
    return (
        leaves.find((leaf) =>
            leaf.path.every(
                (step) => String(selected[step.attributeId]) === String(step.optionId)
            )
        ) || null
    );
}

export function isOptionAvailable(leaves, selected, attributeId, optionId, groupIndex, groups) {
    const trial = { ...selected };
    trial[attributeId] = optionId;
    groups.slice(groupIndex + 1).forEach((g) => {
        delete trial[g.attributeId];
    });

    const entries = Object.entries(trial).filter(([, v]) => v != null && v !== '');
    if (!entries.length) {
        return true;
    }

    return leaves.some((leaf) => {
        const matchesPartial = entries.every(([attrId, optId]) => {
            const step = leaf.path.find((s) => String(s.attributeId) === String(attrId));
            return step && String(step.optionId) === String(optId);
        });
        return matchesPartial && leaf.variant.sku && leaf.variant.stock > 0;
    });
}

export function formatSelectionLabel(selected, groups) {
    return groups
        .map((g) => {
            const optId = selected[g.attributeId];
            const opt = g.options.find((o) => String(o.optionId) === String(optId));
            return opt ? opt.optionName : null;
        })
        .filter(Boolean)
        .join(' / ');
}

export function autoSelectSingleOptions(groups) {
    const selected = {};
    groups.forEach((g) => {
        if (g.options.length === 1) {
            selected[g.attributeId] = g.options[0].optionId;
        }
    });
    return selected;
}

export function minPriceFromLeaves(leaves) {
    const prices = leaves
        .filter((l) => l.variant.sku && l.variant.stock > 0)
        .map((l) => parseFloat(String(l.variant.price).replace(/,/g, '')) || 0)
        .filter((p) => p > 0);
    return prices.length ? Math.min(...prices) : null;
}
