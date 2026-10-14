import { createI18n } from "vue-i18n";
import en from "./languages/en.json";

const localeLoaders = import.meta.glob(["./languages/*.json", "!./languages/en.json"]);

const i18n = createI18n({
    legacy: false,
    locale: "en",
    fallbackLocale: "en",
    messages: { en },
});

const loadedLocales = new Set(["en"]);

/**
 * Load a locale JSON on demand (keeps initial storefront JS small).
 * @param {string} locale
 * @returns {Promise<string>}
 */
export async function loadLocaleMessages(locale) {
    const code = (locale || "en").toLowerCase();
    if (loadedLocales.has(code)) {
        return code;
    }

    const path = `./languages/${code}.json`;
    const loader = localeLoaders[path];
    if (!loader) {
        return "en";
    }

    const mod = await loader();
    const messages = mod.default || mod;
    i18n.global.setLocaleMessage(code, messages);
    loadedLocales.add(code);
    return code;
}

export default i18n;
