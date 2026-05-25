<template>
    <LoadingComponent :props="loading" :is-full-screen="false" />
    <section class="mb-6 sm:mb-10">
        <div class="container mt-4 sm:mt-6">
            <!-- Category Breadcrumb -->
            <CategoryBreadcrumbComponent
                v-if="typeof $route.query.category !== 'undefined' && $route.query.category !== ''"
                :categories="ancestorsAndSelfCategories" class="mb-4" />

            <!-- New Search Bar Layout -->
            <div class="w-full mb-4 relative flex items-center gap-2 sm:gap-3 z-40">
                <button @click="$router.go(-1)" type="button" class="w-10 h-10 flex-shrink-0 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors">
                    <i class="fa-solid fa-chevron-left text-xl text-gray-700"></i>
                </button>
                <div class="relative w-full rounded-full bg-white border-[1.5px] border-gray-900 focus-within:border-black transition-all duration-300 flex items-center pr-1.5 h-[46px] sm:h-12">
                    <input 
                        type="text" 
                        v-model="searchName" 
                        @input="handleSearchInput"
                        @keyup.enter="executeSearch"
                        @focus="showSuggestions = true"
                        @blur="hideSuggestions"
                        placeholder="Search our premium collection..." 
                        class="w-full pl-5 pr-3 py-2 bg-transparent outline-none text-heading font-medium text-sm sm:text-base placeholder:text-gray-500 rounded-full h-full"
                    />
                    <button 
                        v-if="searchName" 
                        @mousedown.prevent="clearNewSearch" 
                        type="button" 
                        class="text-gray-400 hover:text-gray-600 transition-colors px-2"
                    >
                        <i class="fa-solid fa-circle-xmark text-lg"></i>
                    </button>
                    <button 
                        @mousedown.prevent="executeSearch" 
                        type="button" 
                        class="w-[36px] h-[36px] sm:w-[40px] sm:h-[40px] flex-shrink-0 bg-black text-white rounded-full flex items-center justify-center hover:bg-gray-800 transition-colors ml-1"
                    >
                        <i class="fa-solid fa-magnifying-glass text-sm sm:text-base"></i>
                    </button>

                    <!-- Suggestions Dropdown -->
                    <div v-if="showSuggestions && searchSuggestions.length > 0" class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden">
                        <ul class="max-h-60 overflow-y-auto">
                            <li v-for="(suggestion, index) in searchSuggestions" :key="index"
                                @mousedown.prevent="selectSuggestion(suggestion.name)"
                                class="px-5 py-3 hover:bg-gray-50 cursor-pointer flex items-center gap-3 border-b border-gray-50 last:border-0 transition-colors">
                                <i class="fa-solid fa-magnifying-glass text-gray-400 text-sm"></i>
                                <span class="font-medium text-gray-700">{{ suggestion.name }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Shop filters (menus teleported to body so overflow cannot clip them) -->
            <div class="relative mb-4 z-40">
                <div class="flex flex-wrap items-center gap-2 pb-1">
                    <button v-if="hasActiveFilters" type="button" @click="resetFilters"
                        class="shrink-0 inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-primary bg-primary/5 text-primary font-medium text-sm hover:bg-primary/10 transition-all active:scale-95">
                        <i class="fa-solid fa-xmark text-[11px]"></i>
                        <span>{{ $t('button.clear') || 'Clear' }}</span>
                        </button>

                    <button type="button" @click.stop="toggleDropdown('sort', $event)"
                        :class="filters.sortBy ? 'border-primary text-primary' : 'border-gray-200 text-gray-700'"
                        class="shrink-0 inline-flex items-center gap-2 px-4 py-1.5 rounded-full border bg-gray-50 font-medium text-sm hover:border-gray-300 transition-all active:scale-95">
                            <i class="fa-solid fa-arrow-down-wide-short text-[11px]"></i>
                        <span>{{ sortLabel }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="{ 'rotate-180': activeDropdown === 'sort' }"></i>
                        </button>
                        
                    <button type="button" @click.stop="toggleDropdown('price', $event)"
                        :class="filters.priceActive ? 'border-primary text-primary' : 'border-gray-200 text-gray-700'"
                        class="shrink-0 inline-flex items-center gap-2 px-4 py-1.5 rounded-full border bg-gray-50 font-medium text-sm hover:border-gray-300 transition-all active:scale-95">
                        <i class="fa-solid fa-tags text-[11px]"></i>
                        <span>{{ priceLabel }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="{ 'rotate-180': activeDropdown === 'price' }"></i>
                    </button>

                    <button v-if="categoryWiseBands.length > 0" type="button" @click.stop="toggleDropdown('brand', $event)"
                        :class="filters.brandIds.length ? 'border-primary text-primary' : 'border-gray-200 text-gray-700'"
                        class="shrink-0 inline-flex items-center gap-2 px-4 py-1.5 rounded-full border bg-gray-50 font-medium text-sm hover:border-gray-300 transition-all active:scale-95">
                        <i class="fa-solid fa-building text-[11px]"></i>
                        <span>{{ brandLabel }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="{ 'rotate-180': activeDropdown === 'brand' }"></i>
                        </button>
                        
                    <button v-for="(options, attrKey) in categoryWiseVariations" :key="attrKey" type="button"
                        @click.stop="toggleDropdown('var_' + attrKey, $event)"
                        :class="variationCount(attrKey) ? 'border-primary text-primary' : 'border-gray-200 text-gray-700'"
                        class="shrink-0 inline-flex items-center gap-2 px-4 py-1.5 rounded-full border bg-gray-50 font-medium text-sm hover:border-gray-300 transition-all active:scale-95">
                        <span>{{ underscoreToSpace(attrKey) }}{{ variationCount(attrKey) ? ` (${variationCount(attrKey)})` : '' }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] transition-transform" :class="{ 'rotate-180': activeDropdown === 'var_' + attrKey }"></i>
                    </button>
                        </div>
                    </div>

            <Teleport to="body">
                <div v-if="activeDropdown" class="fixed inset-0 z-[9998] bg-transparent" @click="closeDropdown"></div>

                <div v-if="activeDropdown === 'sort' && dropdownPos" data-shop-filter-menu class="fixed z-[9999] bg-white border border-gray-100 rounded-2xl shadow-lg p-2"
                    :style="dropdownPos" @click.stop>
                    <button v-for="opt in sortOptions" :key="opt.value" type="button"
                        @click="selectSort(opt.value)"
                        class="w-full text-left px-3 py-2.5 rounded-xl text-sm font-medium transition-colors"
                        :class="filters.sortBy === opt.value ? 'bg-primary/10 text-primary' : 'text-gray-700 hover:bg-gray-50'">
                        {{ opt.label }}
                        </button>
                </div>

                <div v-if="activeDropdown === 'price' && dropdownPos" data-shop-filter-menu class="fixed z-[9999] bg-white border border-gray-100 rounded-2xl shadow-lg p-4"
                    :style="dropdownPos" @click.stop>
                    <div class="flex items-center gap-2 mb-3">
                        <input type="number" min="0" :max="maxRange" v-model.number="priceDraft[0]"
                            @keypress="onlyNumber($event)"
                            class="w-1/2 text-center py-1.5 px-2 rounded-lg border border-gray-200 text-sm outline-none focus:border-primary">
                        <span class="text-gray-400 text-sm">–</span>
                        <input type="number" min="0" :max="maxRange" v-model.number="priceDraft[1]"
                            @keypress="onlyNumber($event)"
                            class="w-1/2 text-center py-1.5 px-2 rounded-lg border border-gray-200 text-sm outline-none focus:border-primary">
                    </div>
                    <VueSimpleRangeSlider class="p-1 w-full mb-3" :min="0" :max="maxRange || 1"
                        v-model="priceDraft" active-bar-color="#FD8B0E" bar-color="#D9DBE9" />
                    <button type="button" @click="applyPrice"
                        class="w-full py-2 rounded-xl bg-primary text-white text-sm font-semibold">
                        {{ $t('button.apply') }}
                        </button>
                </div>

                <div v-if="activeDropdown === 'brand' && dropdownPos" data-shop-filter-menu
                    class="fixed z-[9999] max-h-[280px] overflow-y-auto bg-white border border-gray-100 rounded-2xl shadow-lg p-3"
                    :style="dropdownPos" @click.stop>
                    <label v-for="band in categoryWiseBands" :key="band.id"
                        class="flex items-center gap-3 py-2 cursor-pointer">
                        <input type="checkbox" class="cs-custom-checkbox" :value="band.id"
                            v-model="filters.brandIds" @change="loadProducts(1)">
                        <span class="text-sm font-medium capitalize">{{ band.name }}</span>
                                </label>
                            </div>

                <div v-for="(options, attrKey) in categoryWiseVariations" :key="'menu-' + attrKey">
                    <div v-if="activeDropdown === 'var_' + attrKey && dropdownPos" data-shop-filter-menu
                        class="fixed z-[9999] max-h-[280px] overflow-y-auto bg-white border border-gray-100 rounded-2xl shadow-lg p-3"
                        :style="dropdownPos" @click.stop>
                        <label v-for="opt in options" :key="opt.product_attribute_option_id"
                            class="flex items-center gap-3 py-2 cursor-pointer">
                            <input type="checkbox" class="cs-custom-checkbox"
                                :checked="isVariationChecked(opt.product_attribute_id, opt.product_attribute_option_id)"
                                @change="toggleVariation(opt.product_attribute_id, opt.product_attribute_option_id, $event.target.checked)">
                            <span class="text-sm font-medium capitalize">{{ opt.attribute_option_name }}</span>
                        </label>
                    </div>
                </div>
            </Teleport>

            <!-- Product Grid Section (Full-Width) -->
            <div class="w-full mt-2">
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-12 componentLoading">
                    <LoadingContentComponent :props="loadingContent" />
                    <ProductListComponent v-if="categoryWiseProducts.length > 0" :products="categoryWiseProducts" />
                </div>
                <p v-if="!loading.isActive && !loadingContent.isActive && categoryWiseProducts.length === 0"
                    class="text-center text-gray-500 py-12 text-sm sm:text-base">
                    {{ $t('message.no_products_found') }}
                </p>

                <!-- Infinite Scroll Trigger & Loading -->
                <div ref="infiniteScrollTrigger" class="w-full h-10 flex items-center justify-center my-4">
                    <div v-if="isLoadingMore" class="flex items-center gap-2 text-primary font-medium">
                        <i class="fa-solid fa-spinner fa-spin animate-spin"></i>
                        <span>{{ $t('label.loading') }}...</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>


<script>

import alertService from "../../../services/alertService";
import targetService from "../../../services/targetService";
import ProductListComponent from "../components/ProductListComponent";
import StatusEnum from "../../../enums/modules/statusEnum";
import appService from "../../../services/appService";
import VueSimpleRangeSlider from "vue-simple-range-slider";
import "vue-simple-range-slider/css";
import LoadingComponent from "../components/LoadingComponent";
import PaginationComponent from "../components/PaginationComponent";
import LoadingContentComponent from "../components/LoadingContentComponent.vue";
import CategoryBreadcrumbComponent from "../components/CategoryBreadcrumbComponent.vue";

export default {
    name: "ProductComponent",
    components: {
        CategoryBreadcrumbComponent,
        LoadingContentComponent,
        LoadingComponent,
        ProductListComponent,
        VueSimpleRangeSlider
    },
    data() {
        return {
            loading: { isActive: false },
            loadingContent: { isActive: false },
            isLoadingMore: false,
            observer: null,
            activeDropdown: null,
            dropdownPos: null,
            searchTimeout: null,
            showSuggestions: false,
            searchSuggestions: [],
            searchName: '',
            categorySlug: null,
            pendingBrandSlug: null,
            maxRange: 1,
            priceDraft: [0, 1],
            loadToken: 0,
            filters: {
                sortBy: '',
                priceActive: false,
                minPrice: 0,
                maxPrice: 0,
                brandIds: [],
                variations: [],
            },
            sortOptions: [],
        };
    },
    computed: {
        pagination() {
            return this.$store.getters['frontendProduct/categoryWiseProductPagination'];
        },
        ancestorsAndSelfCategories() {
            return this.$store.getters['frontendProductCategory/ancestorsAndSelf'];
        },
        categoryWiseProducts() {
            return this.$store.getters['frontendProduct/categoryWiseProducts'];
        },
        categoryWiseBands() {
            return this.$store.getters['frontendProduct/categoryWiseBands'];
        },
        categoryWiseVariations() {
            return this.$store.getters['frontendProduct/categoryWiseVariations'];
        },
        hasActiveFilters() {
            return !!this.filters.sortBy
                || this.filters.priceActive
                || this.filters.brandIds.length > 0
                || this.filters.variations.length > 0;
        },
        sortLabel() {
            const hit = this.sortOptions.find((o) => o.value === this.filters.sortBy);
            return hit ? hit.label : (this.$t('label.sort_by') || 'Sort By');
        },
        priceLabel() {
            if (!this.filters.priceActive) {
                return this.$t('label.price') || 'Price';
            }
            return `${this.filters.minPrice} – ${this.filters.maxPrice}`;
        },
        brandLabel() {
            const base = this.$t('label.brand') || 'Brand';
            return this.filters.brandIds.length ? `${base} (${this.filters.brandIds.length})` : base;
        },
    },
    created() {
        this.sortOptions = [
            { value: 'newest', label: this.$t('label.newest') || 'Newest' },
            { value: 'price_low_to_high', label: this.$t('label.price_low_to_high') || 'Price: Low to High' },
            { value: 'price_high_to_low', label: this.$t('label.price_high_to_low') || 'Price: High to Low' },
            { value: 'top_rated', label: this.$t('label.top_rated') || 'Top Rated' },
        ];
    },
    mounted() {
        this.initFromRoute();
        this.loadProducts(1);
        this.$nextTick(() => this.setupInfiniteScroll());
        this._onFilterScroll = (e) => {
            if (!this.activeDropdown) {
                return;
            }
            if (e?.target?.closest?.('[data-shop-filter-menu]')) {
                return;
            }
            this.closeDropdown();
        };
        window.addEventListener('scroll', this._onFilterScroll, true);
        window.addEventListener('resize', this._onFilterScroll);
    },
    activated() {
        this.initFromRoute();
        this.loadProducts(1);
    },
    beforeUnmount() {
        if (this.observer) {
            this.observer.disconnect();
        }
        window.removeEventListener('scroll', this._onFilterScroll, true);
        window.removeEventListener('resize', this._onFilterScroll);
    },
    methods: {
        initFromRoute() {
            const q = this.$route.query;
            this.categorySlug = q.category || null;
            this.searchName = q.name || '';
            this.pendingBrandSlug = q.brand || null;

            if (this.categorySlug) {
                this.$store.dispatch('frontendProductCategory/ancestorsAndSelf', this.categorySlug).catch(() => {});
            }
        },
        parseMaxPrice(value) {
            const num = parseFloat(String(value ?? 0).replace(/,/g, ''));
            return Number.isFinite(num) && num > 0 ? Math.ceil(num) : 1;
        },
        buildPayload(page = 1) {
            const payload = {
                page,
                per_page: 30,
                status: StatusEnum.ACTIVE,
            };

            if (this.categorySlug) {
                payload.category = this.categorySlug;
            }
            if (this.searchName && this.searchName.trim()) {
                payload.name = this.searchName.trim();
            }
            if (this.filters.sortBy) {
                payload.sort_by = this.filters.sortBy;
            }
            if (this.filters.brandIds.length) {
                payload.brand = this.filters.brandIds.map((id) => Number(id));
            } else if (this.pendingBrandSlug) {
                payload.brand = [this.pendingBrandSlug];
            }
            if (this.filters.variations.length) {
                payload.variation = this.filters.variations.map((v) => ({
                    attribute: Number(v.attribute),
                    option: Number(v.option),
                }));
            }
            if (this.filters.priceActive) {
                payload.min_price = Number(this.filters.minPrice);
                payload.max_price = Number(this.filters.maxPrice);
            }

            return payload;
        },
        applyResponseMeta(data) {
            const max = this.parseMaxPrice(data?.max_price);
            this.maxRange = max;
            if (!this.filters.priceActive) {
                this.priceDraft = [0, max];
            }
            this.applyBrandFromQuery();
        },
        applyBrandFromQuery() {
            if (!this.pendingBrandSlug || !this.categoryWiseBands.length) {
                return;
            }
            const brand = this.categoryWiseBands.find(
                (b) => b.slug === this.pendingBrandSlug || String(b.id) === String(this.pendingBrandSlug)
            );
            if (brand) {
                this.filters.brandIds = [Number(brand.id)];
                this.pendingBrandSlug = null;
                this.loadProducts(1);
            }
        },
        defaultFilters() {
            return {
                sortBy: '',
                priceActive: false,
                minPrice: 0,
                maxPrice: this.maxRange || 1,
                brandIds: [],
                variations: [],
            };
        },
        loadProducts(page = 1) {
            const token = ++this.loadToken;
            const payload = this.buildPayload(page);

            if (page === 1) {
                this.loading.isActive = true;
                this.loadingContent.isActive = true;
                this.isLoadingMore = false;
            } else {
                this.isLoadingMore = true;
            }

            this.closeDropdown();

            return this.$store.dispatch('frontendProduct/categoryWiseProducts', payload)
                .then((res) => {
                    if (token !== this.loadToken) {
                        return res;
                    }
                    const data = res?.data?.data ?? res?.data ?? {};
                    if (page === 1) {
                        this.applyResponseMeta(data);
                        this.$nextTick(() => this.setupInfiniteScroll());
                    }
                    return res;
                })
                .catch((err) => {
                    if (token !== this.loadToken) {
                        return;
                    }
                    const message = err?.response?.data?.message || err?.message;
                    if (message) {
                        alertService.error(message);
                    }
                })
                .finally(() => {
                    if (token !== this.loadToken) {
                        return;
                    }
                    if (page === 1) {
                        this.loading.isActive = false;
                        this.loadingContent.isActive = false;
                    } else {
                        this.isLoadingMore = false;
                    }
                });
        },
        closeDropdown() {
            this.activeDropdown = null;
            this.dropdownPos = null;
        },
        positionDropdown(triggerEl) {
            if (!triggerEl || typeof triggerEl.getBoundingClientRect !== 'function') {
                return;
            }
            const rect = triggerEl.getBoundingClientRect();
            const minW = this.activeDropdown === 'price' ? 280 : 240;
            this.dropdownPos = {
                top: `${rect.bottom + 8}px`,
                left: `${rect.left}px`,
                minWidth: `${Math.max(rect.width, minW)}px`,
            };
        },
        toggleDropdown(name, event) {
            if (this.activeDropdown === name) {
                this.closeDropdown();
                return;
            }
            this.activeDropdown = name;
            this.$nextTick(() => this.positionDropdown(event?.currentTarget));
        },
        selectSort(value) {
            this.filters.sortBy = value;
            this.closeDropdown();
            this.loadProducts(1);
        },
        applyPrice() {
            let min = Number(this.priceDraft[0] ?? 0);
            let max = Number(this.priceDraft[1] ?? this.maxRange);
            if (min > max) {
                [min, max] = [max, min];
                this.priceDraft = [min, max];
            }
            min = Math.max(0, min);
            max = Math.min(this.maxRange, Math.max(min, max));

            const fullRange = min <= 0 && max >= this.maxRange;
            this.filters.priceActive = !fullRange;
            this.filters.minPrice = min;
            this.filters.maxPrice = max;
            this.closeDropdown();
            this.loadProducts(1);
        },
        resetFilters() {
            this.filters = this.defaultFilters();
            this.priceDraft = [0, this.maxRange];
            this.loadProducts(1);
        },
        variationCount(attrKey) {
            const options = this.categoryWiseVariations[attrKey] || [];
            return this.filters.variations.filter((v) =>
                options.some((o) => Number(o.product_attribute_id) === Number(v.attribute))
            ).length;
        },
        isVariationChecked(attributeId, optionId) {
            return this.filters.variations.some(
                (v) => Number(v.attribute) === Number(attributeId) && Number(v.option) === Number(optionId)
            );
        },
        toggleVariation(attributeId, optionId, checked) {
            const attr = Number(attributeId);
            const opt = Number(optionId);
            if (checked) {
                if (!this.isVariationChecked(attr, opt)) {
                    this.filters.variations.push({ attribute: attr, option: opt });
                }
            } else {
                this.filters.variations = this.filters.variations.filter(
                    (v) => !(Number(v.attribute) === attr && Number(v.option) === opt)
                );
            }
            this.loadProducts(1);
        },
        setupInfiniteScroll() {
            if (this.observer) {
                this.observer.disconnect();
            }
        this.observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && !this.loadingContent.isActive && !this.isLoadingMore) {
                if (this.pagination.meta && this.pagination.meta.current_page < this.pagination.meta.last_page) {
                    this.loadMoreProducts();
                }
            }
        }, { rootMargin: '200px' });
        
        if (this.$refs.infiniteScrollTrigger) {
            this.observer.observe(this.$refs.infiniteScrollTrigger);
            }
        },
        handleSearchInput() {
            this.showSuggestions = true;
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.fetchSuggestions();
            }, 300);
        },
        fetchSuggestions() {
            if (this.searchName && this.searchName.length > 1) {
                this.$store.dispatch('frontendProduct/lists', {
                    name: this.searchName,
                    paginate: 0,
                }).then((res) => {
                    this.searchSuggestions = (res.data.data || []).slice(0, 8);
                });
            } else {
                this.searchSuggestions = [];
            }
        },
        executeSearch() {
            this.showSuggestions = false;
            this.loadProducts(1);
        },
        selectSuggestion(name) {
            this.searchName = name;
            this.showSuggestions = false;
            this.loadProducts(1);
        },
        hideSuggestions() {
            setTimeout(() => {
                this.showSuggestions = false;
            }, 200);
        },
        clearNewSearch() {
            this.searchName = '';
            this.searchSuggestions = [];
            this.loadProducts(1);
        },
        onlyNumber: function (e) {
            return appService.onlyNumber(e);
        },
        underscoreToSpace: function (s) {
            return appService.underscoreToSpace(s);
        },
        colspanHideShow: function (event, id) {
            targetService.colspanHideShow(event, id);
        },
        showTarget: function (id, cClass) {
            targetService.showTarget(id, cClass);
        },
        hideTarget: function (id, cClass) {
            targetService.hideTarget(id, cClass);
        },
        loadMoreProducts() {
            if (!this.pagination.meta || this.pagination.meta.current_page >= this.pagination.meta.last_page) {
                return;
            }
            this.loadProducts(this.pagination.meta.current_page + 1);
        },
        onRouteChange() {
            this.filters = this.defaultFilters();
            this.priceDraft = [0, this.maxRange];
            this.initFromRoute();
            this.loadProducts(1);
        },
    },
    watch: {
        '$route.fullPath'(newPath, oldPath) {
            if (newPath !== oldPath) {
                this.onRouteChange();
            }
        },
        categoryWiseBands() {
            this.applyBrandFromQuery();
        },
    },
}
</script>

<style scoped>
/* Hide scrollbar for Chrome, Safari and Opera */
.scrollbar-none::-webkit-scrollbar {
  display: none;
}

/* Hide scrollbar for IE, Edge and Firefox */
.scrollbar-none {
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}
</style>