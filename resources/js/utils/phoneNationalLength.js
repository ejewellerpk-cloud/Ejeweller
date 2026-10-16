/**
 * Expected national mobile number digit lengths (without country calling code).
 * Keys: ISO2 (PK) and/or calling code (+92).
 */
const BY_ISO2 = {
    PK: 11, // Pakistan local: 03XXXXXXXXX (keep leading 0)
    IN: 10,
    BD: 10,
    US: 10,
    CA: 10,
    GB: 10,
    AE: 9,
    SA: 9,
    QA: 8,
    KW: 8,
    BH: 8,
    OM: 8,
    TR: 10,
    MY: 9,
    SG: 8,
    ID: 10,
    PH: 10,
    NG: 10,
    EG: 10,
    ZA: 9,
    AU: 9,
    NZ: 8,
    DE: 11,
    FR: 9,
    IT: 10,
    ES: 9,
    NL: 9,
    BE: 9,
    SE: 9,
    NO: 8,
    DK: 8,
    FI: 9,
    IE: 9,
    CH: 9,
    AT: 10,
    PT: 9,
    PL: 9,
    BR: 11,
    MX: 10,
    AR: 10,
    CN: 11,
    JP: 10,
    KR: 10,
    HK: 8,
    LK: 9,
    NP: 10,
    AF: 9,
    IR: 10,
    IQ: 10,
};

const BY_CALLING_CODE = {
    '+92': 11,
    '+91': 10,
    '+880': 10,
    '+1': 10,
    '+44': 10,
    '+971': 9,
    '+966': 9,
    '+974': 8,
    '+965': 8,
    '+973': 8,
    '+968': 8,
    '+90': 10,
    '+60': 9,
    '+65': 8,
    '+62': 10,
    '+63': 10,
    '+234': 10,
    '+20': 10,
    '+27': 9,
    '+61': 9,
    '+64': 8,
    '+49': 11,
    '+33': 9,
    '+39': 10,
    '+34': 9,
    '+31': 9,
    '+32': 9,
    '+46': 9,
    '+47': 8,
    '+45': 8,
    '+358': 9,
    '+353': 9,
    '+41': 9,
    '+43': 10,
    '+351': 9,
    '+48': 9,
    '+55': 11,
    '+52': 10,
    '+54': 10,
    '+86': 11,
    '+81': 10,
    '+82': 10,
    '+852': 8,
    '+94': 9,
    '+977': 10,
    '+93': 9,
    '+98': 10,
    '+964': 10,
};

/** ITU-T E.164 national number upper bound when country is unknown. */
export const DEFAULT_PHONE_DIGIT_LIMIT = 15;

export function normalizeCallingCode(code) {
    if (!code) {
        return '';
    }
    const digits = String(code).replace(/[^\d+]/g, '');
    if (!digits) {
        return '';
    }
    return digits.startsWith('+') ? digits : `+${digits}`;
}

export function getPhoneDigitLimit({ callingCode = '', iso2 = '' } = {}) {
    const iso = String(iso2 || '').trim().toUpperCase();
    if (iso && BY_ISO2[iso]) {
        return BY_ISO2[iso];
    }

    const dial = normalizeCallingCode(callingCode);
    if (dial && BY_CALLING_CODE[dial]) {
        return BY_CALLING_CODE[dial];
    }

    return DEFAULT_PHONE_DIGIT_LIMIT;
}

function isPakistan({ callingCode = '', iso2 = '' } = {}) {
    const iso = String(iso2 || '').trim().toUpperCase();
    if (iso === 'PK') {
        return true;
    }
    return normalizeCallingCode(callingCode) === '+92';
}

/**
 * Pakistan local mobiles are stored/shown as 03XXXXXXXXX (11 digits, keep leading 0).
 * Users may type 03…, 3…, 92…, or 0092… — normalize to 03… without dropping the 0.
 */
function normalizePakistanNationalDigits(digits) {
    let next = String(digits || '');

    // International trunk: 0092XXXXXXXXXX
    if (next.startsWith('0092')) {
        next = next.slice(4);
    }

    // Country code without +: 92XXXXXXXXXX
    if (next.startsWith('92') && next.length >= 12) {
        next = next.slice(2);
    } else if (next.startsWith('92') && next.length > 2) {
        // While typing 92… strip country code once more digits follow
        next = next.slice(2);
    }

    // Ensure local trunk 0 for mobile numbers (3XXXXXXXXX → 03XXXXXXXXX)
    if (next.startsWith('3') && !next.startsWith('03')) {
        next = '0' + next;
    }

    // Collapse accidental 00… but keep a single leading 0
    while (next.startsWith('00')) {
        next = next.slice(1);
    }

    return next;
}

/**
 * Keep digits only, strip common trunk/country prefixes for the selected country,
 * then truncate to the national digit limit.
 *
 * @param {string} value
 * @param {number|object} limitOrOptions limit number, or { limit, callingCode, iso2 }
 * @param {object} [maybeOptions]
 */
export function sanitizePhoneDigits(value, limitOrOptions = DEFAULT_PHONE_DIGIT_LIMIT, maybeOptions = {}) {
    let limit = DEFAULT_PHONE_DIGIT_LIMIT;
    let callingCode = '';
    let iso2 = '';

    if (limitOrOptions && typeof limitOrOptions === 'object') {
        limit = limitOrOptions.limit ?? getPhoneDigitLimit(limitOrOptions);
        callingCode = limitOrOptions.callingCode || '';
        iso2 = limitOrOptions.iso2 || '';
    } else {
        limit = limitOrOptions;
        callingCode = maybeOptions.callingCode || '';
        iso2 = maybeOptions.iso2 || '';
    }

    let digits = String(value || '').replace(/\D/g, '');

    if (isPakistan({ callingCode, iso2 })) {
        digits = normalizePakistanNationalDigits(digits);
        limit = BY_ISO2.PK;
    }

    const max = Number(limit) > 0 ? Number(limit) : DEFAULT_PHONE_DIGIT_LIMIT;
    return digits.slice(0, max);
}
