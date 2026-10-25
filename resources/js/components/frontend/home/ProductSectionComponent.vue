<template>
    <div class="relative">
        <LoadingComponent v-if="loading.isActive" :props="loading" :is-full-screen="false" skeleton="products" :skeleton-count="4" />

        <template v-if="productSections.length > 0">
            <template v-for="(productSection, key) in productSections" :key="productSection.id || key">
                <section
                    v-if="sectionProducts(productSection).length > 0"
                    class="mb-10 sm:mb-20 homepage-section-block"
                >
                    <div class="container">
                        <div class="flex items-center justify-between gap-4 mb-5 sm:mb-7">
                            <h2 class="text-2xl sm:text-4xl font-bold capitalize">
                                {{ productSection.name }}
                            </h2>
                            <router-link
                                :to="{ name: 'frontend.productSection.products', params: { slug: productSection.slug } }"
                                class="py-2 px-4 text-sm sm:py-3 sm:px-6 rounded-3xl capitalize sm:text-base font-semibold whitespace-nowrap bg-primary-slate text-primary transition-colors duration-200 hover:bg-primary hover:text-white">
                                {{ $t('label.show_more') }}
                            </router-link>
                        </div>

                        <!-- Native scroll-snap rail — lighter & smoother than nested Swipers on mobile -->
                        <div class="homepage-product-rail">
                            <div
                                v-for="product in sectionProducts(productSection)"
                                :key="product.id"
                                class="homepage-product-rail__item"
                            >
                                <ProductListComponent :products="[product]" :lite="true" />
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    v-if="promotions[key]"
                    :key="`promo-${promotions[key].id || key}`"
                    class="mb-10 sm:mb-20 homepage-section-block"
                >
                    <div class="container">
                        <router-link :to="{ name: 'frontend.promotion.products', params: { slug: promotions[key].slug } }">
                            <img
                                v-if="promotions[key].preview && !promotions[key].preview.includes('default/promotion')"
                                class="w-full rounded-3xl"
                                :src="promotions[key].preview"
                                alt="promotion"
                                loading="lazy"
                                decoding="async"
                                @error="$event.target.src=$store.getters['frontendSetting/lists'].theme_logo; $event.target.classList.remove('w-full', 'rounded-3xl'); $event.target.classList.add('w-1/2', 'mx-auto', 'object-contain', 'opacity-40')"
                            >
                            <div v-else class="w-full rounded-3xl flex items-center justify-center bg-gray-50/50 py-10">
                                <img :src="$store.getters['frontendSetting/lists'].theme_logo" alt="logo" loading="lazy" decoding="async" class="w-1/4 object-contain opacity-40">
                            </div>
                        </router-link>
                    </div>
                </section>
            </template>
        </template>

        <template v-else-if="promotions.length > 0">
            <section v-for="promotion in promotions" :key="promotion.id" class="mb-10 sm:mb-20 homepage-section-block">
                <div class="container">
                    <router-link :to="{ name: 'frontend.promotion.products', params: { slug: promotion.slug } }">
                        <img
                            v-if="promotion.preview && !promotion.preview.includes('default/promotion')"
                            class="w-full rounded-3xl"
                            :src="promotion.preview"
                            alt="promotion"
                            loading="lazy"
                            decoding="async"
                            @error="$event.target.src=$store.getters['frontendSetting/lists'].theme_logo; $event.target.classList.remove('w-full', 'rounded-3xl'); $event.target.classList.add('w-1/2', 'mx-auto', 'object-contain', 'opacity-40')"
                        >
                        <div v-else class="w-full rounded-3xl flex items-center justify-center bg-gray-50/50 py-10">
                            <img :src="$store.getters['frontendSetting/lists'].theme_logo" alt="logo" loading="lazy" decoding="async" class="w-1/4 object-contain opacity-40">
                        </div>
                    </router-link>
                </div>
            </section>
        </template>
    </div>
</template>

<script>
import LoadingComponent from "../components/LoadingComponent.vue";
import promotionTypeEnum from "../../../enums/modules/promotionTypeEnum";
import statusEnum from "../../../enums/modules/statusEnum";
import ProductListComponent from "../components/ProductListComponent.vue";
import frontendSectionFetch from '../../../mixins/frontendSectionFetch';

const HOME_SECTION_PRODUCT_LIMIT = 8;

export default {
    name: "ProductSectionComponent",
    mixins: [frontendSectionFetch],
    components: {
        ProductListComponent,
        LoadingComponent,
    },
    data() {
        return {
            loading: {
                isActive: false,
            },
            promotions: []
        }
    },
    computed: {
        productSections: function () {
            return this.$store.getters["frontendProductSection/lists"];
        },
    },
    methods: {
        sectionProducts(section) {
            const list = section?.products || [];
            return list.length > HOME_SECTION_PRODUCT_LIMIT
                ? list.slice(0, HOME_SECTION_PRODUCT_LIMIT)
                : list;
        },
        fetchData() {
            if (this.productSections.length > 0 && this.promotions.length > 0) {
                this.loading.isActive = false;
                return;
            }

            const needsSections = this.productSections.length === 0;
            if (needsSections) {
                this.loading.isActive = true;
            }

            const sectionPromise = needsSections
                ? this.$store.dispatch("frontendProductSection/lists")
                : Promise.resolve();

            const promoPromise = this.promotions.length > 0
                ? Promise.resolve()
                : this.$store.dispatch("frontendPromotion/lists", {
                    paginate: 0,
                    order_column: "id",
                    order_type: "asc",
                    type: promotionTypeEnum.BIG,
                    status: statusEnum.ACTIVE,
                    vuex: false
                }).then(res => {
                    this.promotions = res.data.data;
                });

            Promise.all([sectionPromise, promoPromise]).finally(() => {
                this.loading.isActive = false;
            }).catch(() => {
                this.loading.isActive = false;
            });
        }
    }
}
</script>
