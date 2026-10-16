/**
 * Shared checkout address bootstrap — dedupes API calls when shipping + billing mount.
 */

let addressListsPromise = null;
let countryBootstrapPromise = null;
let defaultCountryStatesPromise = null;
let defaultCountryStatesKey = null;
let countriesPromise = null;

function hasSetting(store) {
    const setting = store.getters["frontendSetting/lists"];
    return setting && Object.keys(setting).length > 0;
}

export function getDefaultCheckoutCountry(store) {
    const setting = store.getters["frontendSetting/lists"] || {};
    const name = String(setting.site_default_checkout_country || "").trim();
    return name || "Pakistan";
}

export function ensureAddressLists(store) {
    if (!store.getters.authStatus) {
        return Promise.resolve([]);
    }

    const existing = store.getters["frontendAddress/lists"];
    if (existing && existing.length > 0) {
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
            .then((res) => res.data.data)
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

export function bootstrapCheckoutAddress(store, { guest = false } = {}) {
    return (async () => {
        const [addresses, countryMeta] = await Promise.all([
            ensureAddressLists(store),
            ensureCountryBootstrap(store),
        ]);

        const defaultCountry = countryMeta?.default_country || getDefaultCheckoutCountry(store);

        // States + guest countries are independent — fetch in parallel
        const [statesRes, countries] = await Promise.all([
            ensureDefaultCountryStates(store, defaultCountry),
            guest ? ensureCountries(store) : Promise.resolve(null),
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
        ensureCountryBootstrap(store),
    ];

    if (store.getters.authStatus) {
        tasks.push(ensureAddressLists(store));
    }

    return Promise.allSettled(tasks);
}
