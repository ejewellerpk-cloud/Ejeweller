import { createI18n } from "vue-i18n";

// Dynamically import all JSON files in languages folder
const messages = {};
const modules = import.meta.glob('./languages/*.json', { eager: true, as: 'json' });

for (const path in modules) {
    // Extract locale name from filename
    const match = path.match(/\.\/languages\/([a-z0-9-_]+)\.json$/i);
    if (match) {
        const locale = match[1];
        messages[locale] = modules[path];
    }
}

const i18n = createI18n({
    legacy: false,
    locale: "en",
    fallbackLocale: "en",
    messages
});

export default i18n;
