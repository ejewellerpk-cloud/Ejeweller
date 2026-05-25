<template>
    <section v-if="page && page.title" class="store-page mb-10 sm:mb-16 lg:mb-20">
        <div class="container">
            <nav class="store-page-breadcrumb mb-4 sm:mb-6" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-2 text-sm text-secondary">
                    <li>
                        <router-link :to="{ name: 'frontend.home' }" class="hover:text-primary transition-colors">
                            {{ $t('label.home') }}
                        </router-link>
                    </li>
                    <li class="text-gray-300" aria-hidden="true">/</li>
                    <li class="font-medium text-heading capitalize" aria-current="page">{{ page.title }}</li>
                </ol>
            </nav>

            <div class="store-page-hero rounded-2xl sm:rounded-3xl bg-gradient-to-br from-secondary via-secondary/95 to-heading/90 text-white px-5 py-8 sm:px-10 sm:py-12 mb-6 sm:mb-10 shadow-lg">
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold capitalize leading-tight mb-2">
                    {{ page.title }}
                </h1>
                <p v-if="pageLead" class="text-sm sm:text-base text-white/80 max-w-2xl">{{ pageLead }}</p>
            </div>

            <div v-if="page.image" class="w-full mb-6 sm:mb-8 rounded-2xl overflow-hidden border border-gray-100 shadow-sm">
                <img :src="page.image" :alt="page.title" class="w-full h-auto object-cover max-h-[420px]" loading="lazy" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10">
                <article class="lg:col-span-8 xl:col-span-9">
                    <div class="store-page-card rounded-2xl border border-gray-100 bg-white shadow-sm p-5 sm:p-8 lg:p-10">
                        <div class="store-page-content ql-editor" v-html="page.description"></div>
                        <TemplateManagerComponent v-if="page.menu_template_id" :menuTemplateId="page.menu_template_id" />
                    </div>
                </article>

                <aside class="lg:col-span-4 xl:col-span-3">
                    <div class="store-page-sidebar rounded-2xl border border-gray-100 bg-white shadow-sm p-5 sm:p-6 lg:sticky lg:top-24">
                        <h3 class="text-lg font-bold text-heading capitalize mb-4">{{ $t('label.contact') }}</h3>
                        <ul class="flex flex-col gap-4">
                            <li v-if="setting.company_address" class="flex gap-3">
                                <span class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                    <i class="lab-fill-location text-primary text-sm"></i>
                                </span>
                                <span class="text-sm text-heading leading-relaxed pt-1">{{ setting.company_address }}</span>
                            </li>
                            <li v-if="setting.company_email" class="flex gap-3">
                                <span class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                    <i class="lab-fill-mail text-primary text-sm"></i>
                                </span>
                                <a :href="'mailto:' + setting.company_email" class="text-sm text-heading hover:text-primary transition-colors pt-1 break-all">
                                    {{ setting.company_email }}
                                </a>
                            </li>
                            <li v-if="companyPhone" class="flex gap-3">
                                <span class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                    <i class="lab-fill-calling text-primary text-sm"></i>
                                </span>
                                <a :href="'tel:' + companyPhoneTel" class="text-sm text-heading hover:text-primary transition-colors pt-1">
                                    {{ companyPhone }}
                                </a>
                            </li>
                        </ul>
                        <router-link
                            :to="{ name: 'frontend.trackOrder' }"
                            class="mt-6 w-full inline-flex items-center justify-center h-11 rounded-full bg-primary text-white text-sm font-bold hover:opacity-90 transition-opacity">
                            {{ $t('label.track_order') }}
                        </router-link>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</template>

<script>
import TemplateManagerComponent from "../components/TemplateManagerComponent.vue";
import 'vue3-quill/lib/vue3-quill.css';

export default {
    name: "PageComponent",
    components: { TemplateManagerComponent },
    computed: {
        page: function () {
            return this.$store.getters['frontendPage/show'];
        },
        setting: function () {
            return this.$store.getters['frontendSetting/lists'];
        },
        companyPhone: function () {
            const code = this.setting.company_calling_code || '';
            const phone = this.setting.company_phone || '';
            const full = `${code}${phone}`.trim();
            return full || '';
        },
        companyPhoneTel: function () {
            return this.companyPhone.replace(/\s+/g, '');
        },
        pageLead: function () {
            if (!this.page || !this.page.description) {
                return '';
            }
            const match = this.page.description.match(/store-page-lead[^>]*>([^<]+)</i);
            return match ? match[1].trim() : '';
        },
    },
    mounted() {
        this.pageSetup();
    },
    methods: {
        pageSetup: function () {
            if (Object.keys(this.$route.params).length > 0 && typeof this.$route.params.slug === 'string') {
                this.$store.dispatch('frontendPage/show', this.$route.params.slug).then().catch();
            }
        },
    },
    watch: {
        $route() {
            this.pageSetup();
        },
    },
};
</script>

<style scoped>
.store-page-content :deep(.store-page-body) {
    color: #334155;
    font-size: 0.9375rem;
    line-height: 1.75;
}

.store-page-content :deep(.store-page-lead) {
    display: none;
}

.store-page-content :deep(.store-page-section) {
    margin-bottom: 2rem;
}

.store-page-content :deep(.store-page-section:last-child) {
    margin-bottom: 0;
}

.store-page-content :deep(.store-page-section h2) {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid rgba(var(--primary-rgb, 255, 138, 0), 0.15);
}

.store-page-content :deep(.store-page-section p) {
    margin-bottom: 0.875rem;
}

.store-page-content :deep(.store-page-section p:last-child) {
    margin-bottom: 0;
}

.store-page-content :deep(.store-page-list) {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.625rem;
}

.store-page-content :deep(.store-page-list li) {
    position: relative;
    padding-left: 1.25rem;
}

.store-page-content :deep(.store-page-list li::before) {
    content: '';
    position: absolute;
    left: 0;
    top: 0.55em;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--primary, #ff8a00);
}

.store-page-content :deep(a) {
    color: var(--primary, #ff8a00);
    text-decoration: underline;
    text-underline-offset: 2px;
}

.store-page-content :deep(a:hover) {
    opacity: 0.85;
}

.store-page-content :deep(strong) {
    color: #0f172a;
    font-weight: 600;
}
</style>
