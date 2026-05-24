/**
 * Social proof copy from real API values only.
 * "In X Bastek & Y bought in last 24 hours"
 */
export function socialProofText(inBaskets, boughtLast24) {
    const baskets = parseInt(inBaskets, 10) || 0;
    const bought = parseInt(boughtLast24, 10) || 0;
    const parts = [];
    if (baskets > 0) {
        parts.push(`In ${baskets} Bastek`);
    }
    if (bought > 0) {
        parts.push(`${bought} bought in last 24 hours`);
    }
    if (parts.length === 2) {
        return parts[0] + ' & ' + parts[1];
    }
    return parts.join('');
}

export function shouldShowSocialProof(item) {
    if (!item) {
        return false;
    }
    const baskets = parseInt(item.in_baskets, 10) || 0;
    const bought = parseInt(item.bought_last_24_hours, 10) || 0;
    return baskets > 0 || bought > 0;
}

export function socialProofTextForItem(item) {
    if (!item) {
        return '';
    }
    return socialProofText(item.in_baskets, item.bought_last_24_hours);
}
