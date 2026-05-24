<template>
    <LoadingComponent :props="loading" />
    <section class="mb-10 sm:mb-20">
        <div class="container">
            <!-- Category Breadcrumb -->
            <CategoryBreadcrumbComponent
                v-if="typeof $route.query.category !== 'undefined' && $route.query.category !== ''"
                :categories="ancestorsAndSelfCategories" />

            <!-- Page Title Header -->
            <div class="flex items-center justify-between gap-5 mb-6">
                <div class="flex flex-wrap items-end gap-3 max-md:flex-col max-md:items-start max-md:gap-1.5">
                    <h3 class="text-3xl font-bold capitalize max-sm:text-xl">
                        {{ $t('label.explore_all_products') }}
                    </h3>
                    <span class="text-xl font-medium capitalize max-sm:text-sm text-gray-500">
                        ({{
                            pagination.meta ? pagination.meta.total : 0
                        }} {{
                            categoryWiseProducts.length > 1 ? $t('label.products_found') : $t('label.product_found')
                        }})
                    </span>
                </div>
            </div>

            <!-- Premium Full-Width Search Bar -->
            <div class="w-full mb-6 relative">
                <div class="relative w-full shadow-sm rounded-2xl bg-white border border-gray-200 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/20 transition-all duration-300">
                    <i class="fa-solid fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                    <input 
                        type="text" 
                        v-model="productSearchForm.name" 
                        @input="debounceSearch"
                        placeholder="Search our premium collection..." 
                        class="w-full pl-14 pr-12 py-4 bg-transparent outline-none text-heading font-semibold text-base placeholder:text-gray-400 placeholder:font-normal rounded-2xl"
                    />
                    <button 
                        v-if="productSearchForm.name" 
                        @click="clearSearch" 
                        type="button" 
                        class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 transition-colors"
                    >
                        <i class="fa-solid fa-circle-xmark text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Backdrop for Closing Dropdowns -->
            <div v-if="activeDropdown" @click="activeDropdown = null" class="fixed inset-0 z-40 bg-transparent"></div>

            <!-- Premium Horizontal Filter Row (Always Visible, Scrollable) -->
            <div class="relative mb-8 z-50">
                <div class="flex items-center gap-3 overflow-x-auto pb-3 pt-1 whitespace-nowrap scrollbar-none scroll-smooth">
                    
                    <!-- 1. Sort By Dropdown -->
                    <div class="relative inline-block text-left shrink-0">
                        <button @click.prevent="toggleDropdown('sortBy')" type="button"
                            :class="productSearchForm.sort_by ? 'border-primary bg-primary/5 text-primary' : 'border-gray-200 text-heading'"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border bg-white font-semibold text-sm hover:border-primary hover:text-primary transition-all duration-300 active:scale-95 shadow-sm">
                            <i class="fa-solid fa-arrow-down-wide-short text-xs"></i>
                            <span>{{ getSortLabel() }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-300" :class="{ 'rotate-180': activeDropdown === 'sortBy' }"></i>
                        </button>
                        
                        <div v-if="activeDropdown === 'sortBy'" 
                            class="absolute top-full mt-2 left-0 z-50 min-w-[220px] bg-white border border-gray-100 rounded-2xl shadow-[0_10px_30px_rgba(0,0,0,0.08)] p-4 transition-all duration-200">
                            <div class="flex flex-col gap-3.5">
                                <label for="sortByNewest" class="flex items-center gap-3 cursor-pointer group">
                                    <input @click="sortByOption($event, 'newest')" v-model="productSortBy"
                                        value="latest" type="radio" id="sortByNewest" class="cs-custom-radio">
                                    <span class="font-medium text-sm capitalize transition-all duration-300 group-hover:text-primary">
                                        {{ $t('label.newest') }}
                                    </span>
                                </label>

                                <label for="priceLowToHigh" class="flex items-center gap-3 cursor-pointer group">
                                    <input @click="sortByOption($event, 'price_low_to_high')"
                                        v-model="productSortBy" value="price_low_to_high" type="radio"
                                        id="priceLowToHigh" class="cs-custom-radio">
                                    <span class="font-medium text-sm capitalize transition-all duration-300 group-hover:text-primary">
                                        {{ $t('label.price_low_to_high') }}
                                    </span>
                                </label>

                                <label for="priceHighToLow" class="flex items-center gap-3 cursor-pointer group">
                                    <input @click="sortByOption($event, 'price_high_to_low')"
                                        v-model="productSortBy" value="price_high_to_low" type="radio"
                                        id="priceHighToLow" class="cs-custom-radio">
                                    <span class="font-medium text-sm capitalize transition-all duration-300 group-hover:text-primary">
                                        {{ $t('label.price_high_to_low') }}
                                    </span>
                                </label>

                                <label for="topRated" class="flex items-center gap-3 cursor-pointer group">
                                    <input @click="sortByOption($event, 'top_rated')" v-model="productSortBy"
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
                            :class="(productSearchForm.min_price !== null || productSearchForm.max_price !== null) ? 'border-primary bg-primary/5 text-primary' : 'border-gray-200 text-heading'"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border bg-white font-semibold text-sm hover:border-primary hover:text-primary transition-all duration-300 active:scale-95 shadow-sm">
                            <i class="fa-solid fa-dollar-sign text-xs"></i>
                            <span>{{ getPriceLabel() }}</span>
                            <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-300" :class="{ 'rotate-180': activeDropdown === 'price' }"></i>
                        </button>
                        
                        <div v-if="activeDropdown === 'price'" 
                            class="absolute top-full mt-2 left-0 z-50 min-w-[280px] bg-white border border-gray-100 rounded-2xl shadow-[0_10px_30px_rgba(0,0,0,0.08)] p-4 transition-all duration-200">
                            <div class="flex flex-col gap-4">
                                <div class="flex items-center gap-2">
                                    <input @keyup="priceOptionUpdate" v-on:keypress="onlyNumber($event)"
                                        class="db-field-control text-center py-1.5 px-2.5 rounded-lg border border-gray-200 outline-none text-sm w-1/2" type="number"
                                        v-model="productPrice.range[0]">
                                    <span class="text-gray-400 font-medium text-sm">to</span>
                                    <input @keyup="priceOptionUpdate" v-on:keypress="onlyNumber($event)"
                                        class="db-field-control text-center py-1.5 px-2.5 rounded-lg border border-gray-200 outline-none text-sm w-1/2" type="number"
                                        v-model="productPrice.range[1]">
                                </div>
                                <VueSimpleRangeSlider @mouseup="priceOptionRange" @touchend="priceOptionRange"
                                    :keepJustSignificantFigures="true" popover-content-editable="false"
                                    significant-figures="1" active-bar-color="#FD8B0E" bar-color="#D9DBE9"
                                    class="p-1 w-full" :min="0" :max="maxRange" v-model="productPrice.range" />
                            </div>
                        </div>
                    </div>

                    <!-- 3. Brand Dropdown -->
                    <div v-if="categoryWiseBands.length > 0" class="relative inline-block text-left shrink-0">
                        <button @click.prevent="toggleDropdown('brand')" type="button"
                            :class="productBrands.length > 0 ? 'border-primary bg-primary/5 text-primary' : 'border-gray-200 text-heading'"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border bg-white font-semibold text-sm hover:border-primary hover:text-primary transition-all duration-300 active:scale-95 shadow-sm">
                            <i class="fa-solid fa-tags text-xs"></i>
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
                                    <input @click="brandOption($event, categoryWiseBand.id)" type="checkbox"
                                        :id="'brand_' + categoryWiseBand.id" class="cs-custom-checkbox"
                                        :checked="productBrands.includes(categoryWiseBand.id)">
                                    <span class="font-medium text-sm capitalize transition-all duration-300 group-hover:text-primary">
                                        {{ categoryWiseBand.name }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Dynamic Variation Dropdowns -->
                    <div v-if="Object.keys(categoryWiseVariations).length > 0"
                        v-for="(categoryWiseVariation, categoryWiseVariationKey) in categoryWiseVariations"
                        :key="categoryWiseVariationKey"
                        class="relative inline-block text-left shrink-0">
                        <button @click.prevent="toggleDropdown(categoryWiseVariationKey)" type="button"
                            :class="getVariationLabel(categoryWiseVariationKey) ? 'border-primary bg-primary/5 text-primary' : 'border-gray-200 text-heading'"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full border bg-white font-semibold text-sm hover:border-primary hover:text-primary transition-all duration-300 active:scale-95 shadow-sm">
                            <i class="fa-solid fa-sliders text-xs"></i>
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
                                        @click="variationOption($event, variation.product_attribute_id, variation.product_attribute_option_id)"
                                        :id="variation.product_attribute_id + '_' + variation.product_attribute_option_id"
                                        class="cs-custom-checkbox"
                                        :checked="productVariations.some(v => v.attribute === variation.product_attribute_id && v.option === variation.product_attribute_option_id)">
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
            <div class="w-full border-t border-gray-100 pt-6">
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-12 componentLoading">
                    <LoadingContentComponent :props="loadingContent" />
                    <ProductListComponent v-if="categoryWiseProducts.length > 0" :products="categoryWiseProducts" />
                </div>

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
            searchTimeout: null
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
        }
    },
    mounted() {
        this.ancestorsAndSelf();
        
        // Setup Infinite Scroll Observer
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
            if (this.productSearchForm.min_price !== null || this.productSearchForm.max_price !== null) {
                let min = this.productSearchForm.min_price || 0;
                let max = this.productSearchForm.max_price || this.maxRange;
                return `${min} - ${max}`;
            }
            return this.$t('label.price') || 'Price';
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
        debounceSearch: function () {
            if (this.searchTimeout) clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.products();
            }, 400);
        },
        clearSearch: function () {
            this.productSearchForm.name = null;
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

            if (typeof this.$route.query.brand !== "undefined" && this.$route.query.brand !== "") {
                this.productSearchForm.brand = JSON.stringify([this.$route.query.brand]);
            } else {
                this.productSearchForm.brand = [];
            }

            this.loading.isActive = true;
            this.$store.dispatch("frontendProduct/categoryWiseProducts", this.productSearchForm).then(res => {
                this.productPrice.range = [0, res.data.data.max_price];
                this.maxRange = res.data.data.max_price;
                this.loading.isActive = false;
            }).catch((err) => {
                this.loading.isActive = false;
            });
        },
        async products(page = 1) {
            this.loadingContent.isActive = true;
            this.productSearchForm.page = page;
            await this.$store.dispatch("frontendProduct/categoryWiseProducts", this.productSearchForm).then(res => {
                this.loadingContent.isActive = false;
            }).catch((err) => {
                this.loadingContent.isActive = false;
            });
        },
        async loadMoreProducts() {
            this.isLoadingMore = true;
            this.productSearchForm.page += 1;
            await this.$store.dispatch("frontendProduct/categoryWiseProducts", this.productSearchForm).then(res => {
                this.isLoadingMore = false;
            }).catch((err) => {
                this.isLoadingMore = false;
            });
        },
        sortByOption: function (event, sortBy) {
            this.productSearchForm.sort_by = sortBy;
            this.products();
        },
        priceOptionRange() {
            this.productSearchForm.min_price = this.productPrice.range[0];
            this.productSearchForm.max_price = this.productPrice.range[1];
            this.products();
        },
        priceOptionUpdate() {
            this.productPrice.range = [this.productPrice.range[0], this.productPrice.range[1]];
            this.productSearchForm.min_price = this.productPrice.range[0];
            this.productSearchForm.max_price = this.productPrice.range[1];
            this.products();
        },
        brandOption: function (event, brand) {
            if (event.target.checked) {
                this.productBrands.push(brand);
            } else {
                for (let i = 0; i < this.productBrands.length; i++) {
                    if (this.productBrands[i] === brand) {
                        this.productBrands.splice(i, 1);
                    }
                }
            }
            this.productSearchForm.brand = JSON.stringify(this.productBrands);
            this.products();
        },
        variationOption: function (event, attribute, option) {
            if (event.target.checked) {
                this.productVariations.push({
                    attribute: attribute,
                    option: option
                });
            } else {
                for (let i = 0; i < this.productVariations.length; i++) {
                    if (this.productVariations[i].attribute === attribute && this.productVariations[i].option === option) {
                        this.productVariations.splice(i, 1);
                    }
                }
            }
            this.productSearchForm.variation = JSON.stringify(this.productVariations);
            this.products();
        },
    },
    watch: {
        $route() {
            this.ancestorsAndSelf();
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