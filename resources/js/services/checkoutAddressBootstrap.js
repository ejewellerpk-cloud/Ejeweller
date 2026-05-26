/**
 * Shared checkout address bootstrap — dedupes API calls when shipping + billing mount.
 */

let addressListsPromise = null;
let countryBootstrapPromise = null;
let pakistanStatesPromise = null;
let countriesPromise = null;

function hasSetting(store) {
    const setting = store.getters["frontendSetting/lists"];
    return setting && Object.keys(setting).length > 0;
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
        };
    })().catch((err) => {
        countryBootstrapPromise = null;
        throw err;
    });

    return countryBootstrapPromise;
}

export function ensurePakistanStates(store) {
    if (!pakistanStatesPromise) {
        pakistanStatesPromise = store
            .dispatch("frontendCountryStateCity/statesByCountry", "Pakistan")
            .catch((err) => {
                pakistanStatesPromise = null;
                throw err;
            });
    }
    return pakistanStatesPromise;
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
    const tasks = [
        ensureAddressLists(store),
        ensureCountryBootstrap(store),
        ensurePakistanStates(store),
    ];
    if (guest) {
        tasks.push(ensureCountries(store));
    }
    return Promise.all(tasks);
}
