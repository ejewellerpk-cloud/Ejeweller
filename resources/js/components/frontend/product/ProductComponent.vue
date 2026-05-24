<template>
    <LoadingComponent :props="loading" />
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
                        v-model="productSearchForm.name" 
                        @input="handleSearchInput"
                        @keyup.enter="executeSearch"
                        @focus="showSuggestions = true"
                        @blur="hideSuggestions"
                        placeholder="Search our premium collection..." 
                        class="w-full pl-5 pr-3 py-2 bg-transparent outline-none text-heading font-medium text-sm sm:text-base placeholder:text-gray-500 rounded-full h-full"
                    />
                    <button 
                        v-if="productSearchForm.name" 
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

            <!-- Backdrop for Closing Dropdowns -->
            <div v-if="activeDropdown" @click="activeDropdown = null" class="fixed inset-0 z-40 bg-transparent"></div>

            <!-- Premium Horizontal Filter Row (Always Visible, Scrollable) -->
            <div class="relative mb-4 z-30">
                <div class="flex items-center gap-2 overflow-x-auto pb-1 whitespace-nowrap scrollbar-none scroll-smooth">
                    
                    <!-- Filters Button (Reset Filters) -->
                    <div class="shrink-0">
                        <button type="button" @click="clearAllFilters"
                            class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-gray-200 bg-gray-50 font-medium text-sm hover:border-gray-300 transition-all duration-300 active:scale-95"
                            :class="hasActiveFilters ? 'text-primary border-primary bg-primary/5' : 'text-gray-700'">
                            <i class="fa-solid fa-filter text-[11px]"></i>
                            <span>{{ hasActiveFilters ? 'Clear Filters' : 'Filters' }}</span>
                        </button>
                    </div>

                    <!-- 1. Sort By Dropdown -->
                    <div class="relative inline-block text-left shrink-0">
                        <button @click.prevent="toggleDropdown('sortBy')" type="button"
                            :class="productSearchForm.sort_by ? 'border-primary text-primary' : 'border-gray-200 text-gray-700'"
                            class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border bg-gray-50 font-medium text-sm hover:border-gray-300 transition-all duration-300 active:scale-95">
                            <i class="fa-solid fa-arrow-down-wide-short text-[11px]"></i>
                            <span>{{ getSortLabel() }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-300" :class="{ 'rotate-180': activeDropdown === 'sortBy' }"></i>
                        </button>
                        
                        <div v-if="activeDropdown === 'sortBy'" 
                            class="absolute top-full mt-2 left-0 z-50 min-w-[220px] bg-white border border-gray-100 rounded-2xl shadow-[0_10px_30px_rgba(0,0,0,0.08)] p-4 transition-all duration-200">
                            <div class="flex flex-col gap-3.5">
                                <label for="sortByNewest" class="flex items-center gap-3 cursor-pointer group">
                                    <input @change="sortByOption($event)" v-model="productSortBy"
                                        value="newest" type="radio" id="sortByNewest" class="cs-custom-radio">
                                    <span class="font-medium text-sm capitalize transition-all duration-300 group-hover:text-primary">
                                        {{ $t('label.newest') }}
                                    </span>
                                </label>

                                <label for="priceLowToHigh" class="flex items-center gap-3 cursor-pointer group">
                                    <input @change="sortByOption($event)"
                                        v-model="productSortBy" value="price_low_to_high" type="radio"
                                        id="priceLowToHigh" class="cs-custom-radio">
                                    <span class="font-medium text-sm capitalize transition-all duration-300 group-hover:text-primary">
                                        {{ $t('label.price_low_to_high') }}
                                    </span>
                                </label>

                                <label for="priceHighToLow" class="flex items-center gap-3 cursor-pointer group">
                                    <input @change="sortByOption($event)"
                                        v-model="productSortBy" value="price_high_to_low" type="radio"
                                        id="priceHighToLow" class="cs-custom-radio">
                                    <span class="font-medium text-sm capitalize transition-all duration-300 group-hover:text-primary">
                                        {{ $t('label.price_high_to_low') }}
                                    </span>
                                </label>

                                <label for="topRated" class="flex items-center gap-3 cursor-pointer group">
                                    <input @change="sortByOption($event)" v-model="productSortBy"
                                        value="top_rated" type="radio" id="topRated" class="cs-custom-radio">
                                    <span class="font-medium text-sm capitalize transition-all duration-300 group-hover:text-primary">
                                        {{ $t('label.top_rated') }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Price Range Dropdown -->
                    <div class="relative inline-block text-left shrink-0">
                        <button @click.prevent="toggleDropdown('price')" type="button"
                            :class="priceFilterActive ? 'border-primary text-primary' : 'border-gray-200 text-gray-700'"
                            class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border bg-gray-50 font-medium text-sm hover:border-gray-300 transition-all duration-300 active:scale-95">
                            <i class="fa-solid fa-dollar-sign text-[11px]"></i>
                            <span>{{ getPriceLabel() }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-300" :class="{ 'rotate-180': activeDropdown === 'price' }"></i>
                        </button>
                        
                        <div v-if="activeDropdown === 'price'" 
                            class="absolute top-full mt-2 left-0 z-50 min-w-[280px] bg-white border border-gray-100 rounded-2xl shadow-[0_10px_30px_rgba(0,0,0,0.08)] p-4 transition-all duration-200">
                            <div class="flex flex-col gap-4">
                                <div class="flex items-center gap-2">
                                    <input v-on:keypress="onlyNumber($event)"
                                        class="db-field-control text-center py-1.5 px-2.5 rounded-lg border border-gray-200 outline-none text-sm w-1/2" type="number"
                                        v-model.number="productPrice.range[0]" min="0" :max="maxRange">
                                    <span class="text-gray-400 font-medium text-sm">to</span>
                                    <input v-on:keypress="onlyNumber($event)"
                                        class="db-field-control text-center py-1.5 px-2.5 rounded-lg border border-gray-200 outline-none text-sm w-1/2" type="number"
                                        v-model.number="productPrice.range[1]" min="0" :max="maxRange">
                                </div>
                                <VueSimpleRangeSlider
                                    :keepJustSignificantFigures="true" popover-content-editable="false"
                                    significant-figures="1" active-bar-color="#FD8B0E" bar-color="#D9DBE9"
                                    class="p-1 w-full" :min="0" :max="maxRange || 1" v-model="productPrice.range" />
                                <button type="button" @click="applyPriceFilter"
                                    class="w-full py-2 rounded-xl bg-primary text-white text-sm font-semibold hover:opacity-90 transition-opacity">
                                    {{ $t('button.apply') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Brand Dropdown -->
                    <div v-if="categoryWiseBands.length > 0" class="relative inline-block text-left shrink-0">
                        <button @click.prevent="toggleDropdown('brand')" type="button"
                            :class="productBrands.length > 0 ? 'border-primary text-primary' : 'border-gray-200 text-gray-700'"
                            class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border bg-gray-50 font-medium text-sm hover:border-gray-300 transition-all duration-300 active:scale-95">
                            <i class="fa-solid fa-tags text-[11px]"></i>
                            <span>{{ $t('label.brand') }}{{ getBrandLabel() }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-300" :class="{ 'rotate-180': activeDropdown === 'brand' }"></i>
                        </button>
                        
                        <div v-if="activeDropdown === 'brand'" 
                            class="absolute top-full mt-2 left-0 z-50 min-w-[240px] max-h-[300px] overflow-y-auto bg-white border border-gray-100 rounded-2xl shadow-[0_10px_30px_rgba(0,0,0,0.08)] p-4 transition-all duration-200">
                            <div class="flex flex-col gap-3">
                                <label :for="'brand_' + categoryWiseBand.id"
                                    v-for="categoryWiseBand in categoryWiseBands"
                                    :key="categoryWiseBand.id"
                                    class="flex items-center gap-3 cursor-pointer group">
                                    <input @change="brandOption($event, categoryWiseBand.id)" type="checkbox"
                                        :id="'brand_' + categoryWiseBand.id" class="cs-custom-checkbox"
                                        :checked="isBrandSelected(categoryWiseBand.id)">
                                    <span class="font-medium text-sm capitalize transition-all duration-300 group-hover:text-primary">
                                        {{ categoryWiseBand.name }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Dynamic Variations Dropdown -->
                    <div v-if="Object.keys(categoryWiseVariations).length > 0"
                        v-for="(categoryWiseVariation, categoryWiseVariationKey) in categoryWiseVariations"
                        :key="categoryWiseVariationKey"
                        class="relative inline-block text-left shrink-0">
                        <button @click.prevent="toggleDropdown(categoryWiseVariationKey)" type="button"
                            :class="getVariationLabel(categoryWiseVariationKey) ? 'border-primary text-primary' : 'border-gray-200 text-gray-700'"
                            class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border bg-gray-50 font-medium text-sm hover:border-gray-300 transition-all duration-300 active:scale-95">
                            <span>{{ underscoreToSpace(categoryWiseVariationKey) }}{{ getVariationLabel(categoryWiseVariationKey) }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-300" :class="{ 'rotate-180': activeDropdown === categoryWiseVariationKey }"></i>
                        </button>
                        
                        <div v-if="activeDropdown === categoryWiseVariationKey" 
                            class="absolute top-full mt-2 left-0 z-50 min-w-[240px] max-h-[300px] overflow-y-auto bg-white border border-gray-100 rounded-2xl shadow-[0_10px_30px_rgba(0,0,0,0.08)] p-4 transition-all duration-200">
                            <div class="flex flex-col gap-3">
                                <label
                                    :for="variation.product_attribute_id + '_' + variation.product_attribute_option_id"
                                    v-for="variation in categoryWiseVariation"
                                    :key="variation.product_attribute_option_id"
                                    class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox"
                                        @change="variationOption($event, variation.product_attribute_id, variation.product_attribute_option_id)"
                                        :id="variation.product_attribute_id + '_' + variation.product_attribute_option_id"
                                        class="cs-custom-checkbox"
                                        :checked="isVariationSelected(variation.product_attribute_id, variation.product_attribute_option_id)">
                                    <span class="font-medium text-sm capitalize transition-all duration-300 group-hover:text-primary">
                                        {{ variation.attribute_option_name }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

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
            loading: {
                isActive: false,
            },
            loadingContent: {
                isActive: false
            },
            productSortBy: null,
            productBrands: [],
            productVariations: [],
            productPrice: {
                range: [0, 0]
            },
            maxRange: 0,
            productSearchForm: {
                page: 1,
                status: StatusEnum.ACTIVE,
                sort_by: null,
                category: null,
                name: null,
                brand: [],
                variation: [],
                min_price: null,
                max_price: null
            },
            isLoadingMore: false,
            observer: null,
            activeDropdown: null,
            searchTimeout: null,
            showSuggestions: false,
            searchSuggestions: [],
            pendingBrandSlug: null,
            priceFilterActive: false,
            filterRequestId: 0,
        }
    },
    computed: {
        pagination: function () {
            return this.$store.getters["frontendProduct/categoryWiseProductPagination"];
        },
        ancestorsAndSelfCategories: function () {
            return this.$store.getters["frontendProductCategory/ancestorsAndSelf"];
        },
        categoryWiseProducts: function () {
            return this.$store.getters["frontendProduct/categoryWiseProducts"];
        },
        categoryWiseBands: function () {
            return this.$store.getters["frontendProduct/categoryWiseBands"];
        },
        categoryWiseVariations: function () {
            return this.$store.getters["frontendProduct/categoryWiseVariations"];
        },
        hasActiveFilters() {
            return this.productSearchForm.sort_by !== null ||
                   this.priceFilterActive ||
                   this.productBrands.length > 0 ||
                   this.productVariations.length > 0;
        }
    },
    mounted() {
        this.ancestorsAndSelf();
        this.$nextTick(() => this.setupInfiniteScroll());
    },
    beforeUnmount() {
        if (this.observer) {
            this.observer.disconnect();
        }
    },
    methods: {
        getSortLabel: function () {
            if (this.productSearchForm.sort_by === 'newest') return this.$t('label.newest') || 'Newest';
            if (this.productSearchForm.sort_by === 'price_low_to_high') return this.$t('label.price_low_to_high') || 'Price: Low to High';
            if (this.productSearchForm.sort_by === 'price_high_to_low') return this.$t('label.price_high_to_low') || 'Price: High to Low';
            if (this.productSearchForm.sort_by === 'top_rated') return this.$t('label.top_rated') || 'Top Rated';
            return this.$t('label.sort_by') || 'Sort By';
        },
        getPriceLabel: function () {
            if (this.priceFilterActive) {
                const min = this.productSearchForm.min_price ?? 0;
                const max = this.productSearchForm.max_price ?? this.maxRange;
                return `${min} - ${max}`;
            }
            return this.$t('label.price') || 'Price';
        },
        parseMaxPrice: function (value) {
            const num = parseFloat(String(value ?? 0).replace(/,/g, ''));
            return Number.isFinite(num) && num > 0 ? Math.ceil(num) : 1;
        },
        isBrandSelected: function (brandId) {
            const id = Number(brandId);
            return this.productBrands.some((b) => Number(b) === id);
        },
        isVariationSelected: function (attributeId, optionId) {
            const attr = Number(attributeId);
            const opt = Number(optionId);
            return this.productVariations.some((v) => Number(v.attribute) === attr && Number(v.option) === opt);
        },
        setupInfiniteScroll: function () {
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
        getBrandLabel: function () {
            if (this.productBrands.length > 0) {
                return ` (${this.productBrands.length})`;
            }
            return '';
        },
        getVariationLabel: function (key) {
            const attrOptions = this.categoryWiseVariations[key] || [];
            const selectedCount = this.productVariations.filter(v => 
                attrOptions.some(opt => opt.product_attribute_id === v.attribute && opt.product_attribute_option_id === v.option)
            ).length;
            return selectedCount > 0 ? ` (${selectedCount})` : '';
        },
        toggleDropdown: function (dropdownName) {
            if (this.activeDropdown === dropdownName) {
                this.activeDropdown = null;
            } else {
                this.activeDropdown = dropdownName;
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
            if (this.productSearchForm.name && this.productSearchForm.name.length > 1) {
                this.$store.dispatch("frontendProduct/lists", {
                    name: this.productSearchForm.name,
                    paginate: 0
                }).then(res => {
                    this.searchSuggestions = res.data.data.slice(0, 8);
                });
            } else {
                this.searchSuggestions = [];
            }
        },
        executeSearch() {
            this.showSuggestions = false;
            this.products();
        },
        selectSuggestion(name) {
            this.productSearchForm.name = name;
            this.showSuggestions = false;
            this.products();
        },
        hideSuggestions() {
            setTimeout(() => {
                this.showSuggestions = false;
            }, 200);
        },
        clearNewSearch() {
            this.productSearchForm.name = null;
            this.searchSuggestions = [];
            this.products();
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
        ancestorsAndSelf: function () {
            if (typeof this.$route.query.category !== "undefined" && this.$route.query.category !== "") {
                this.loading.isActive = true;
                this.productSearchForm.category = this.$route.query.category;
                this.$store.dispatch("frontendProductCategory/ancestorsAndSelf", this.$route.query.category).then(res => {
                    this.loading.isActive = false;
                }).catch((err) => {
                    this.loading.isActive = false;
                });
            } else {
                this.productSearchForm.category = null;
            }

            if (typeof this.$route.query.name !== "undefined" && this.$route.query.name !== "") {
                this.productSearchForm.name = this.$route.query.name;
            } else {
                this.productSearchForm.name = null;
            }

            this.pendingBrandSlug = (typeof this.$route.query.brand !== "undefined" && this.$route.query.brand !== "")
                ? this.$route.query.brand
                : null;
            this.productBrands = [];
            this.productSearchForm.brand = [];

            this.loading.isActive = true;
            this.$store.dispatch("frontendProduct/categoryWiseProducts", this.buildProductPayload(1)).then(res => {
                const max = this.parseMaxPrice(res.data.data.max_price);
                this.maxRange = max;
                if (!this.priceFilterActive) {
                    this.productPrice.range = [0, max];
                }
                this.syncBrandFromRoute();
                this.productSortBy = this.productSearchForm.sort_by;
                this.loading.isActive = false;
                this.$nextTick(() => this.setupInfiniteScroll());
            }).catch((err) => {
                this.loading.isActive = false;
            });
        },
        buildProductPayload: function (page = 1) {
            const payload = {
                page: page,
                status: StatusEnum.ACTIVE,
                sort_by: this.productSearchForm.sort_by || null,
                category: this.productSearchForm.category || null,
                name: this.productSearchForm.name || null,
            };

            if (this.productBrands.length > 0) {
                payload.brand = JSON.stringify(this.productBrands.map((id) => Number(id)));
            } else if (this.pendingBrandSlug) {
                payload.brand = JSON.stringify([this.pendingBrandSlug]);
            }

            if (this.productVariations.length > 0) {
                payload.variation = JSON.stringify(this.productVariations.map((v) => ({
                    attribute: Number(v.attribute),
                    option: Number(v.option),
                })));
            }

            if (this.priceFilterActive) {
                payload.min_price = Number(this.productSearchForm.min_price ?? 0);
                payload.max_price = Number(this.productSearchForm.max_price ?? this.maxRange);
            }

            return payload;
        },
        syncBrandFromRoute: function () {
            if (!this.pendingBrandSlug || this.categoryWiseBands.length === 0) {
                return;
            }
            const slug = this.pendingBrandSlug;
            const brand = this.categoryWiseBands.find((b) => b.slug === slug || String(b.id) === String(slug));
            if (brand) {
                this.productBrands = [Number(brand.id)];
                this.pendingBrandSlug = null;
                this.products();
            }
        },
        async products(page = 1) {
            const requestId = ++this.filterRequestId;
            this.loadingContent.isActive = true;
            this.activeDropdown = null;

            try {
                await this.$store.dispatch("frontendProduct/categoryWiseProducts", this.buildProductPayload(page));
                if (requestId !== this.filterRequestId) {
                    return;
                }
            } catch (err) {
                // ignore
            } finally {
                if (requestId === this.filterRequestId) {
                    this.loadingContent.isActive = false;
                }
            }
        },
        async loadMoreProducts() {
            if (!this.pagination.meta || this.pagination.meta.current_page >= this.pagination.meta.last_page) {
                return;
            }
            this.isLoadingMore = true;
            const nextPage = this.pagination.meta.current_page + 1;
            await this.$store.dispatch("frontendProduct/categoryWiseProducts", this.buildProductPayload(nextPage)).then(res => {
                this.isLoadingMore = false;
            }).catch((err) => {
                this.isLoadingMore = false;
            });
        },
        sortByOption: function (event) {
            const sortBy = event.target.value;
            this.productSortBy = sortBy;
            this.productSearchForm.sort_by = sortBy;
            this.products();
        },
        applyPriceFilter() {
            let min = Number(this.productPrice.range[0] ?? 0);
            let max = Number(this.productPrice.range[1] ?? this.maxRange);
            if (min > max) {
                [min, max] = [max, min];
                this.productPrice.range = [min, max];
            }
            min = Math.max(0, min);
            max = Math.min(this.maxRange, Math.max(min, max));

            const isFullRange = min <= 0 && max >= this.maxRange;
            this.priceFilterActive = !isFullRange;
            this.productSearchForm.min_price = min;
            this.productSearchForm.max_price = max;
            this.products();
        },
        clearAllFilters() {
            if (!this.hasActiveFilters) {
                return;
            }
            this.productSearchForm.sort_by = null;
            this.productSortBy = null;
            this.priceFilterActive = false;
            this.productSearchForm.min_price = null;
            this.productSearchForm.max_price = null;
            this.productBrands = [];
            this.productVariations = [];
            this.productPrice.range = [0, this.maxRange];
            this.products();
        },
        brandOption: function (event, brand) {
            const id = Number(brand);
            if (event.target.checked) {
                if (!this.isBrandSelected(id)) {
                    this.productBrands.push(id);
                }
            } else {
                this.productBrands = this.productBrands.filter((b) => Number(b) !== id);
            }
            this.products();
        },
        variationOption: function (event, attribute, option) {
            const attr = Number(attribute);
            const opt = Number(option);
            if (event.target.checked) {
                if (!this.isVariationSelected(attr, opt)) {
                    this.productVariations.push({ attribute: attr, option: opt });
                }
            } else {
                this.productVariations = this.productVariations.filter(
                    (v) => !(Number(v.attribute) === attr && Number(v.option) === opt)
                );
            }
            this.products();
        },
    },
    watch: {
        $route() {
            this.ancestorsAndSelf();
        },
        categoryWiseBands() {
            this.syncBrandFromRoute();
        }
    }
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