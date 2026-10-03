<template>
    <div
        class="header-category-mega fixed left-0 z-50 w-full origin-top scale-y-0 transition-all duration-300"
        style="top: var(--frontend-header-bottom, 4rem);"
        @mouseenter="onMegaEnter"
    >
        <div class="container">
            <div class="header-category-mega__shell w-full rounded-b-2xl shadow-paper bg-white overflow-hidden">
                <div v-if="activeCategory" class="header-category-mega__layout">
                    <!-- Left rail: top-level categories -->
                    <nav class="header-category-mega__rail" aria-label="Categories">
                        <router-link
                            v-for="category in categories"
                            :key="category.id"
                            :to="{ name: 'frontend.product', query: { category: category.slug } }"
                            class="header-category-mega__rail-item"
                            :class="{ 'header-category-mega__rail-item--active': isParentActive(category) }"
                            @mouseenter.prevent="setParentActive(category)"
                        >
                            <span class="header-category-mega__rail-label capitalize">{{ category.name }}</span>
                            <i
                                v-if="category.children?.length"
                                class="lab lab-line-arrow-right header-category-mega__rail-chevron"
                            ></i>
                        </router-link>
                    </nav>

                    <!-- Main columns -->
                    <div class="header-category-mega__main">
                        <div class="header-category-mega__main-inner">
                            <div class="header-category-mega__columns-wrap">
                                <div v-if="panelColumns.length > 0" class="header-category-mega__columns">
                                    <div
                                        v-for="column in panelColumns"
                                        :key="column.id"
                                        class="header-category-mega__col"
                                    >
                                        <router-link
                                            :to="{ name: 'frontend.product', query: { category: column.slug } }"
                                            class="header-category-mega__col-title capitalize"
                                        >
                                            {{ column.name }}
                                        </router-link>

                                        <ul v-if="columnChildren(column).length > 0" class="header-category-mega__col-list">
                                            <li v-for="nested in columnChildren(column)" :key="nested.id">
                                                <router-link
                                                    :to="{ name: 'frontend.product', query: { category: nested.slug } }"
                                                    class="header-category-mega__link capitalize"
                                                >
                                                    {{ nested.name }}
                                                </router-link>
                                            </li>
                                        </ul>
                                        <router-link
                                            v-else
                                            :to="{ name: 'frontend.product', query: { category: column.slug } }"
                                            class="header-category-mega__link header-category-mega__link--shop"
                                        >
                                            {{ $t('label.shop_all') }} {{ column.name }}
                                        </router-link>
                                    </div>
                                </div>

                                <div v-else class="header-category-mega__empty">
                                    <router-link
                                        :to="panelShopLink"
                                        class="header-category-mega__link header-category-mega__link--shop"
                                    >
                                        {{ $t('label.shop_all') }} {{ activeCategory.name }}
                                    </router-link>
                                </div>
                            </div>

                            <!-- Optional featured image when cover exists -->
                            <router-link
                                v-if="hasFeatureImage"
                                :to="panelShopLink"
                                class="header-category-mega__feature group"
                            >
                                <img
                                    class="header-category-mega__feature-img"
                                    loading="lazy"
                                    :src="panelImage"
                                    :alt="activeCategory.name"
                                    @error="onImageError"
                                />
                                <div class="header-category-mega__feature-overlay">
                                    <span class="header-category-mega__feature-name capitalize">
                                        {{ activeCategory.name }}
                                    </span>
                                    <span class="header-category-mega__feature-cta">
                                        {{ $t('label.shop_now') }}
                                        <i class="lab lab-line-arrow-right text-sm"></i>
                                    </span>
                                </div>
                            </router-link>
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
        panelShopLink() {
            const slug = this.activeCategory?.slug;
            return { name: 'frontend.product', query: { category: slug } };
        },
        panelImage() {
            if (this.imageFallback) {
                return '';
            }
            const cover = this.activeCategory?.cover || '';
            if (cover && !cover.includes('default/category')) {
                return cover;
            }
            const thumb = this.activeCategory?.thumb || '';
            if (thumb && !thumb.includes('default/category')) {
                return thumb;
            }
            return '';
        },
        hasFeatureImage() {
            return Boolean(this.panelImage) && !this.imageFallback;
        },
        panelColumns() {
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
        activeCategory() {
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
        setParentActive(category) {
            this.activeParentSlug = category.slug;
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
