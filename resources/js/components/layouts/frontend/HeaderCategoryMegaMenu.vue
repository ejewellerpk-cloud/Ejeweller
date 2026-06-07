<template>
    <div
        class="header-category-mega fixed left-0 z-50 w-full origin-top scale-y-0 transition-all duration-300"
        style="top: var(--frontend-header-bottom, 4rem);"
        @mouseenter="onMegaEnter"
    >
        <div class="container">
            <div class="header-category-mega__shell w-full rounded-b-2xl shadow-paper bg-white overflow-hidden">
                <!-- Tier 1: top-level categories -->
                <nav class="header-category-mega__tabs flex items-center justify-center flex-wrap gap-x-1 gap-y-0 px-4 border-b border-slate-100">
                    <router-link
                        v-for="category in categories"
                        :key="category.id"
                        :to="{ name: 'frontend.product', query: { category: category.slug } }"
                        class="header-category-mega__tab capitalize text-sm font-semibold tracking-wide px-4 py-3.5 transition-all duration-300 relative"
                        :class="{ 'header-category-mega__tab--active': isParentActive(category) }"
                        @mouseenter.prevent="setParentActive(category)"
                    >
                        {{ category.name }}
                    </router-link>
                </nav>

                <div v-if="activeCategory" class="header-category-mega__body">
                    <!-- Tier 2: subcategories -->
                    <nav
                        v-if="activeChildren.length > 0"
                        class="header-category-mega__subs flex items-center justify-center flex-wrap gap-2 px-6 py-3 bg-primary-slate/40 border-b border-slate-100"
                    >
                        <button
                            v-for="child in activeChildren"
                            :key="child.id"
                            type="button"
                            class="header-category-mega__sub-pill capitalize text-xs sm:text-sm font-medium px-4 py-1.5 rounded-full border transition-all duration-300"
                            :class="{ 'header-category-mega__sub-pill--active': isSubActive(child) }"
                            @mouseenter="setSubActive(child)"
                        >
                            {{ child.name }}
                        </button>
                    </nav>

                    <!-- Tier 3: hero + nested subcategories -->
                    <div class="header-category-mega__panel">
                        <div class="max-w-6xl mx-auto px-6 py-7 lg:py-8">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-start">
                                <!-- Featured image -->
                                <div class="lg:col-span-4 xl:col-span-4">
                                    <router-link
                                        :to="panelShopLink"
                                        class="header-category-mega__hero group block relative overflow-hidden rounded-2xl aspect-[4/5] bg-slate-100 shadow-sm"
                                    >
                                        <img
                                            class="w-full h-full object-cover object-center transition-transform duration-500 group-hover:scale-105"
                                            loading="lazy"
                                            :src="panelImage"
                                            :alt="panelCategory.name"
                                            @error="onImageError"
                                        />
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                                        <div class="absolute inset-x-0 bottom-0 p-5 sm:p-6 text-white">
                                            <p class="text-[11px] uppercase tracking-[0.2em] font-semibold opacity-80 mb-1">
                                                {{ activeCategory.name }}
                                            </p>
                                            <h3 class="text-xl sm:text-2xl font-bold capitalize leading-tight mb-3">
                                                {{ panelCategory.name }}
                                            </h3>
                                            <span class="inline-flex items-center gap-2 text-sm font-semibold bg-white/15 backdrop-blur-sm px-4 py-2 rounded-full group-hover:bg-primary group-hover:text-white transition-all duration-300">
                                                {{ $t('label.shop_now') }}
                                                <i class="lab lab-line-arrow-right text-base"></i>
                                            </span>
                                        </div>
                                    </router-link>
                                </div>

                                <!-- Subcategory columns -->
                                <div class="lg:col-span-8 xl:col-span-8">
                                    <div v-if="panelColumns.length > 0" class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-7">
                                        <div
                                            v-for="column in panelColumns"
                                            :key="column.id"
                                            class="min-w-0"
                                        >
                                            <router-link
                                                :to="{ name: 'frontend.product', query: { category: column.slug } }"
                                                class="inline-flex items-center gap-1.5 text-sm font-bold capitalize text-heading border-b border-slate-200 pb-2 mb-3 hover:text-primary transition-colors duration-300"
                                            >
                                                {{ column.name }}
                                                <i class="lab lab-line-arrow-right text-xs opacity-60"></i>
                                            </router-link>

                                            <ul v-if="columnChildren(column).length > 0" class="space-y-1.5">
                                                <li v-for="nested in columnChildren(column)" :key="nested.id">
                                                    <router-link
                                                        :to="{ name: 'frontend.product', query: { category: nested.slug } }"
                                                        class="text-sm capitalize text-slate-600 hover:text-primary hover:translate-x-0.5 inline-block transition-all duration-200"
                                                    >
                                                        {{ nested.name }}
                                                    </router-link>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div v-else class="flex flex-col items-center justify-center text-center min-h-[220px] lg:min-h-[320px] px-4">
                                        <p class="text-slate-500 text-sm mb-4 max-w-sm">
                                            {{ $t('label.explore_collection') }}
                                        </p>
                                        <router-link
                                            :to="panelShopLink"
                                            class="inline-flex items-center gap-2 text-sm font-semibold text-primary bg-primary/10 px-5 py-2.5 rounded-full hover:bg-primary hover:text-white transition-all duration-300"
                                        >
                                            {{ $t('label.shop_all') }} {{ panelCategory.name }}
                                            <i class="lab lab-line-arrow-right"></i>
                                        </router-link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'HeaderCategoryMegaMenu',
    props: {
        categories: {
            type: Array,
            required: true,
        },
    },
    data() {
        return {
            activeParentSlug: null,
            activeSubSlug: null,
            imageFallback: false,
        };
    },
    computed: {
        activeCategory() {
            if (!this.activeParentSlug) {
                return this.categories[0] || null;
            }
            return this.categories.find((c) => c.slug === this.activeParentSlug) || this.categories[0] || null;
        },
        activeChildren() {
            return this.activeCategory?.children || [];
        },
        activeSubcategory() {
            if (!this.activeChildren.length) {
                return null;
            }
            if (this.activeSubSlug) {
                return this.activeChildren.find((c) => c.slug === this.activeSubSlug) || this.activeChildren[0];
            }
            return this.activeChildren[0];
        },
        panelCategory() {
            return this.activeSubcategory || this.activeCategory;
        },
        panelShopLink() {
            const slug = this.panelCategory?.slug;
            return { name: 'frontend.product', query: { category: slug } };
        },
        panelImage() {
            if (this.imageFallback) {
                return this.$store.getters['frontendSetting/lists']?.theme_logo || '';
            }
            const cover = this.panelCategory?.cover || '';
            if (cover && !cover.includes('default/category')) {
                return cover;
            }
            const thumb = this.panelCategory?.thumb || '';
            if (thumb && !thumb.includes('default/category')) {
                return thumb;
            }
            return cover || thumb;
        },
        panelColumns() {
            const nested = this.panelCategory?.children || [];
            if (nested.length > 0) {
                return nested;
            }
            if (this.activeSubcategory) {
                return [];
            }
            return this.activeChildren;
        },
    },
    watch: {
        categories: {
            immediate: true,
            handler(list) {
                if (list?.length && !this.activeParentSlug) {
                    this.setParentActive(list[0]);
                }
            },
        },
        panelCategory() {
            this.imageFallback = false;
        },
    },
    methods: {
        onMegaEnter() {
            if (!this.activeParentSlug && this.categories.length) {
                this.setParentActive(this.categories[0]);
            }
        },
        isParentActive(category) {
            const current = this.activeParentSlug || this.categories[0]?.slug;
            return current === category.slug;
        },
        isSubActive(child) {
            const current = this.activeSubSlug || this.activeChildren[0]?.slug;
            return current === child.slug;
        },
        setParentActive(category) {
            this.activeParentSlug = category.slug;
            const firstChild = category.children?.[0];
            this.activeSubSlug = firstChild?.slug || null;
            this.imageFallback = false;
        },
        setSubActive(child) {
            this.activeSubSlug = child.slug;
            this.imageFallback = false;
        },
        columnChildren(column) {
            return column?.children || [];
        },
        onImageError() {
            this.imageFallback = true;
        },
    },
};
</script>
