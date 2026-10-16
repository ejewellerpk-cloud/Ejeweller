/**
 * Shared checkout address bootstrap — dedupes API calls when shipping + billing mount.
 */

let addressListsPromise = null;
let addressListsFetched = false;
let countryBootstrapPromise = null;
let defaultCountryStatesPromise = null;
let defaultCountryStatesKey = null;
let countriesPromise = null;
let formLookupsPromise = null;
let formLookupsGuest = null;

function hasSetting(store) {
    const setting = store.getters["frontendSetting/lists"];
    return setting && Object.keys(setting).length > 0;
}

export function getDefaultCheckoutCountry(store) {
    const setting = store.getters["frontendSetting/lists"] || {};
    const name = String(setting.site_default_checkout_country || "").trim();
    return name || "Pakistan";
}

export function invalidateAddressListsCache() {
    addressListsPromise = null;
    addressListsFetched = false;
}

export function ensureAddressLists(store) {
    if (!store.getters.authStatus) {
        return Promise.resolve([]);
    }

    if (addressListsFetched) {
        return Promise.resolve(store.getters["frontendAddress/lists"] || []);
    }

    const existing = store.getters["frontendAddress/lists"];
    if (existing && existing.length > 0) {
        addressListsFetched = true;
        return Promise.resolve(existing);
    }

    if (!addressListsPromise) {
        addressListsPromise = store
            .dispatch("frontendAddress/lists", {
                search: {
                    paginate: 0,
                    order_column: "id",
                    order_type: "asc",
                },
            })
            .then((res) => {
                addressListsFetched = true;
                return res.data.data;
            })
            .catch((err) => {
                addressListsPromise = null;
                throw err;
            });
    }

    return addressListsPromise;
}

export function ensureCountryBootstrap(store) {
    if (countryBootstrapPromise) {
        return countryBootstrapPromise;
    }

    countryBootstrapPromise = (async () => {
        const codes = store.getters["frontendCountryCode/lists"];
        const needCodes = !codes || codes.length === 0;
        const needSettings = !hasSetting(store);

        await Promise.all([
            needCodes ? store.dispatch("frontendCountryCode/lists") : Promise.resolve(),
            needSettings ? store.dispatch("frontendSetting/lists") : Promise.resolve(),
        ]);

        const setting = store.getters["frontendSetting/lists"];
        const companyCode = setting?.company_country_code;
        if (companyCode) {
            const show = store.getters["frontendCountryCode/show"];
            if (!show || !show.calling_code) {
                await store.dispatch("frontendCountryCode/show", companyCode);
            }
        }

        return {
            calling_code: store.getters["frontendCountryCode/show"]?.calling_code,
            flag_emoji: store.getters["frontendCountryCode/show"]?.flag_emoji,
            default_country: getDefaultCheckoutCountry(store),
        };
    })().catch((err) => {
        countryBootstrapPromise = null;
        throw err;
    });

    return countryBootstrapPromise;
}

export function ensureDefaultCountryStates(store, countryName) {
    const name = String(countryName || getDefaultCheckoutCountry(store) || "Pakistan").trim() || "Pakistan";

    if (defaultCountryStatesPromise && defaultCountryStatesKey === name) {
        return defaultCountryStatesPromise;
    }

    defaultCountryStatesKey = name;
    defaultCountryStatesPromise = store
        .dispatch("frontendCountryStateCity/statesByCountry", name)
        .catch((err) => {
            defaultCountryStatesPromise = null;
            defaultCountryStatesKey = null;
            throw err;
        });

    return defaultCountryStatesPromise;
}

/** @deprecated Use ensureDefaultCountryStates */
export function ensurePakistanStates(store) {
    return ensureDefaultCountryStates(store, "Pakistan");
}

export function ensureCountries(store) {
    const existing = store.getters["frontendCountryStateCity/countries"];
    if (existing && existing.length > 0) {
        return Promise.resolve(existing);
    }

    if (!countriesPromise) {
        countriesPromise = store
            .dispatch("frontendCountryStateCity/countries")
            .then((res) => res.data.data)
            .catch((err) => {
                countriesPromise = null;
                throw err;
            });
    }

    return countriesPromise;
}

/**
 * Warm country-code + states in background (do not block address picker).
 * Shares one in-flight promise across shipping/billing mounts.
 */
export function warmCheckoutAddressFormLookups(store, { guest = false } = {}) {
    if (formLookupsPromise && formLookupsGuest === guest) {
        return formLookupsPromise;
    }

    formLookupsGuest = guest;
    formLookupsPromise = (async () => {
        const countryMeta = await ensureCountryBootstrap(store);
        const defaultCountry = countryMeta?.default_country || getDefaultCheckoutCountry(store);
        // Always load countries so vue-select can bind the default (guest + add-address modal).
        const [statesRes, countries] = await Promise.all([
            ensureDefaultCountryStates(store, defaultCountry),
            ensureCountries(store),
        ]);
        return [countryMeta, statesRes, countries];
    })().catch((err) => {
        formLookupsPromise = null;
        formLookupsGuest = null;
        throw err;
    });

    return formLookupsPromise;
}

/**
 * Logged-in: only wait for addresses (picker). Guest: full form data.
 * Heavy country-code/states warm in background for logged-in users.
 */
export function bootstrapCheckoutAddress(store, { guest = false } = {}) {
    return (async () => {
        if (!guest) {
            const addresses = await ensureAddressLists(store);
            // Do not await — address cards should paint immediately.
            warmCheckoutAddressFormLookups(store, { guest: false }).catch(() => {});
            return [addresses, null, null, null];
        }

        const [addresses, countryMeta] = await Promise.all([
            ensureAddressLists(store),
            ensureCountryBootstrap(store),
        ]);

        const defaultCountry = countryMeta?.default_country || getDefaultCheckoutCountry(store);

        const [statesRes, countries] = await Promise.all([
            ensureDefaultCountryStates(store, defaultCountry),
            ensureCountries(store),
        ]);

        return [addresses, countryMeta, statesRes, countries];
    })();
}

/** Prefetch checkout lists from cart step so /checkout/checkout hits cache. */
export function prefetchCheckoutLists(store, { status } = {}) {
    const tasks = [
        store.dispatch("frontendSetting/lists"),
        store.dispatch("frontendOrderArea/lists"),
        store.dispatch("frontendPaymentGateway/lists", status != null ? { status } : { status: 5 }),
        store.dispatch("frontendOutlet/lists", status != null ? { status } : { status: 5 }),
    ];

    if (store.getters.authStatus) {
        // Addresses first — most important for next screen.
        tasks.unshift(ensureAddressLists(store));
        // Country codes are heavy; warm after a tick so cart stays snappy.
        tasks.push(
            Promise.resolve().then(() => warmCheckoutAddressFormLookups(store, { guest: false }))
        );
    } else {
        tasks.push(warmCheckoutAddressFormLookups(store, { guest: true }));
    }

    return Promise.allSettled(tasks);
}
