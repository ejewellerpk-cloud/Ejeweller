/** Common email domains for guest signup / checkout autocomplete. */
export const EMAIL_DOMAIN_SUGGESTIONS = [
    'gmail.com',
    'yahoo.com',
    'hotmail.com',
    'outlook.com',
    'icloud.com',
    'live.com',
    'msn.com',
    'aol.com',
    'proton.me',
    'protonmail.com',
    'mail.com',
    'ymail.com',
    'googlemail.com',
    'zoho.com',
    'gmx.com',
];

/**
 * Build email completion options from the current input value.
 * @param {string} value
 * @param {number} [limit=8]
 * @returns {string[]}
 */
export function getEmailDomainSuggestions(value, limit = 8) {
    const raw = String(value || '').trim();
    if (!raw || /\s/.test(raw)) {
        return [];
    }

    const atIndex = raw.indexOf('@');
    if (atIndex === -1) {
        const local = raw;
        if (!local) {
            return [];
        }
        return EMAIL_DOMAIN_SUGGESTIONS.slice(0, limit).map((domain) => `${local}@${domain}`);
    }

    const local = raw.slice(0, atIndex);
    const domainPart = raw.slice(atIndex + 1).toLowerCase();
    if (!local) {
        return [];
    }

    const matches = EMAIL_DOMAIN_SUGGESTIONS.filter((domain) => domain.startsWith(domainPart));
    return matches.slice(0, limit).map((domain) => `${local}@${domain}`);
}
