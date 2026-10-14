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
        if (!codes || codes.length === 0) {
            await store.dispatch("frontendCountryCode/lists");
        }

        let setting = store.getters["frontendSetting/lists"];
        if (!hasSetting(store)) {
            const settingRes = await store.dispatch("frontendSetting/lists");
            setting = settingRes.data.data;
        }

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
        const statesRes = await ensureDefaultCountryStates(store, defaultCountry);
        const countries = guest ? await ensureCountries(store) : null;

        return [addresses, countryMeta, statesRes, countries];
    })();
}
