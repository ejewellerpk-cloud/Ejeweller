/**
 * Normalize order address fields for equality checks.
 */
function norm(value) {
    return String(value ?? '')
        .trim()
        .toLowerCase()
        .replace(/\s+/g, ' ')
}

/**
 * Compare shipping vs billing content (ignores address_type / ids).
 */
export function areOrderAddressesEqual(a, b) {
    if (!a || !b) {
        return false
    }

    const keys = [
        'full_name',
        'email',
        'phone',
        'country_code',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
    ]

    return keys.every((key) => norm(a[key]) === norm(b[key]))
}

/**
 * Collapse identical shipping + billing into a single display entry.
 *
 * @param {Array} addresses
 * @param {{ SHIPPING: number, BILLING: number }} addressTypeEnum
 * @returns {{ address: object, kind: 'shipping'|'billing'|'both' }[]}
 */
export function getDisplayOrderAddresses(addresses, addressTypeEnum) {
    const list = Array.isArray(addresses) ? [...addresses] : []
    if (!list.length || !addressTypeEnum) {
        return list.map((address) => ({
            address,
            kind: 'shipping',
        }))
    }

    const shipping = list.find((a) => Number(a.address_type) === Number(addressTypeEnum.SHIPPING))
    const billing = list.find((a) => Number(a.address_type) === Number(addressTypeEnum.BILLING))
    const used = new Set()
    const result = []

    if (shipping && billing && areOrderAddressesEqual(shipping, billing)) {
        result.push({ address: shipping, kind: 'both' })
        used.add(shipping)
        used.add(billing)
    } else {
        if (shipping) {
            result.push({ address: shipping, kind: 'shipping' })
            used.add(shipping)
        }
        if (billing) {
            result.push({ address: billing, kind: 'billing' })
            used.add(billing)
        }
    }

    list.forEach((address) => {
        if (used.has(address)) {
            return
        }
        const kind =
            Number(address.address_type) === Number(addressTypeEnum.BILLING) ? 'billing' : 'shipping'
        result.push({ address, kind })
    })

    return result
}

export function addressKindTitleKey(kind) {
    if (kind === 'both') {
        return 'label.shipping_billing_address'
    }
    if (kind === 'billing') {
        return 'label.billing_address'
    }
    return 'label.shipping_address'
}
