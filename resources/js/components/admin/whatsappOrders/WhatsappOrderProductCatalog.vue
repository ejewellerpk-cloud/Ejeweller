<template>
    <div class="db-card mb-6">
        <div class="db-card-header">
            <h3 class="db-card-title">{{ $t('label.add_products') }}</h3>
        </div>
        <div class="db-card-body">
            <form class="mb-4" @submit.prevent="$emit('search')">
                <div class="form-row">
                    <div class="form-col-12 sm:form-col-6 lg:form-col-4">
                        <label class="db-field-title">{{ $t('label.search') }}</label>
                        <div class="relative">
                            <button type="submit" class="lab-line-search absolute top-1/2 -translate-y-1/2 ltr:left-3 rtl:right-3 text-gray-400"></button>
                            <input type="search" v-model="searchName" :placeholder="$t('label.search_here')"
                                class="db-field-control ltr:pl-10 rtl:pr-10" @input="$emit('update:name', searchName)" />
                            <button v-if="searchName" type="button" @click="clearName"
                                class="absolute top-1/2 -translate-y-1/2 ltr:right-3 rtl:left-3 text-sm text-red-500 fa-regular fa-circle-xmark"></button>
                        </div>
                    </div>
                    <div class="form-col-12 sm:form-col-6 lg:form-col-4">
                        <label class="db-field-title">{{ $t('label.category') }}</label>
                        <vue-select :modelValue="categoryId" class="db-field-control f-b-custom-select"
                            :options="categories" label-by="option" value-by="id" :closeOnSelect="true" :searchable="true"
                            :clearOnClose="true" :placeholder="$t('label.select_category')"
                            :search-placeholder="$t('label.search_category')"
                            @update:modelValue="$emit('setCategory', $event)" />
                    </div>
                    <div class="form-col-12 sm:form-col-6 lg:form-col-4">
                        <label class="db-field-title">{{ $t('label.brand') }}</label>
                        <vue-select :modelValue="brandId" class="db-field-control f-b-custom-select" :options="brands"
                            label-by="name" value-by="id" :closeOnSelect="true" :searchable="true" :clearOnClose="true"
                            :placeholder="$t('label.select_brand')" :search-placeholder="$t('label.search_brand')"
                            @update:modelValue="$emit('setBrand', $event)" />
                    </div>
                    <div class="form-col-12 sm:form-col-6 lg:form-col-4" v-if="categoryId || brandId || searchName">
                        <label class="db-field-title">&nbsp;</label>
                        <button type="button" class="db-btn-outline w-full h-[38px] !text-[#FB4E4E] !border-[#FB4E4E]"
                            @click="$emit('reset')">
                            <i class="lab lab-line-reset"></i>
                            <span>{{ $t('button.reset') }}</span>
                        </button>
                    </div>
                </div>
            </form>

            <div class="rounded-lg border border-slate-100 bg-slate-50/60 p-4 mb-5">
                <div class="form-row !gap-4">
                    <div class="form-col-12 md:form-col-6">
                        <label class="db-field-title">{{ $t('label.barcode') }}</label>
                        <div class="relative">
                            <i class="lab-line-qrcode absolute top-1/2 -translate-y-1/2 ltr:left-3 rtl:right-3 text-lg text-gray-400"></i>
                            <input ref="barcodeInput" v-model="barcode" type="text" class="db-field-control ltr:pl-10 rtl:pr-10"
                                :placeholder="$t('label.barcode')" @keyup.enter="scanBarcode" />
                        </div>
                    </div>
                    <div class="form-col-12 md:form-col-6">
                        <label class="db-field-title">{{ $t('label.product') }}</label>
                        <vue-select v-model="quickProductId" class="db-field-control f-b-custom-select" :options="products"
                            label-by="name" value-by="id" :closeOnSelect="true" :searchable="true" :clearOnClose="true"
                            :placeholder="$t('label.select_one')" search-placeholder="--"
                            @update:modelValue="openProductModal" />
                    </div>
                </div>
            </div>

            <div v-if="products.length > 0"
                class="grid gap-3 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5">
                <div v-for="product in products" :key="product.id"
                    class="group flex flex-col rounded-lg border border-[#EFF0F6] bg-white p-2 transition hover:border-primary hover:shadow-sm">
                    <button type="button" class="relative mb-2 overflow-hidden rounded-md text-left" @click="openProductModal(product.id)">
                        <img :src="product.cover" :alt="product.name"
                            class="aspect-square w-full object-cover transition group-hover:scale-105" />
                        <span v-if="product.is_offer && product.flash_sale"
                            class="absolute left-2 top-2 rounded px-2 py-0.5 text-[10px] font-semibold uppercase text-white bg-secondary">
                            {{ $t('label.flash_sale') }}
                        </span>
                    </button>
                    <h4 class="mb-1 truncate text-sm font-medium capitalize" :title="product.name">{{ product.name }}</h4>
                    <p class="mb-2 text-sm font-semibold text-primary">
                        <span v-if="product.is_offer">{{ product.discounted_price }}</span>
                        <span v-else>{{ product.currency_price }}</span>
                    </p>
                    <button type="button" class="db-btn-outline mt-auto w-full py-1.5 text-xs"
                        @click="openProductModal(product.id)">
                        <i class="lab lab-line-bag"></i>
                        <span>{{ $t('button.add') }}</span>
                    </button>
                </div>
            </div>

            <div v-else class="flex flex-col items-center justify-center py-10 text-center">
                <img class="mb-3 w-32 opacity-70" :src="setting.image_cart" alt="empty" />
                <p class="text-sm text-gray-500">{{ $t('message.no_data_found') }}</p>
            </div>
        </div>
    </div>

    <div id="variation-modal"
        class="fixed inset-0 z-50 h-dvh w-screen overflow-y-auto bg-black/50 p-3 opacity-0 invisible transition-all duration-500">
        <div class="mx-auto w-full max-w-4xl rounded-xl bg-white transition-all duration-500">
            <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-4">
                <h3 class="text-lg font-bold capitalize">{{ $t('label.product_variation') }}</h3>
                <button type="button" class="lab-line-circle-cross text-lg text-[#E93C3C]" @click="closeProductModal"></button>
            </div>
            <ProductDetailsComponent v-if="activeProductId" :method="closeProductModal" :productId="activeProductId" />
        </div>
    </div>
</template>

<script>
import ProductDetailsComponent from "../pos/ProductDetailsComponent";
import targetService from "../../../services/targetService";
import alertService from "../../../services/alertService";
export default {
    name: "WhatsappOrderProductCatalog",
    components: {
        ProductDetailsComponent,
    },
    props: {
        products: {
            type: Array,
            default: () => [],
        },
        categories: {
            type: Array,
            default: () => [],
        },
        brands: {
            type: Array,
            default: () => [],
        },
        name: {
            type: String,
            default: "",
        },
        categoryId: {
            type: [Number, String, null],
            default: null,
        },
        brandId: {
            type: [Number, String, null],
            default: null,
        },
    },
    emits: ["search", "reset", "setCategory", "setBrand", "update:name"],
    data() {
        return {
            searchName: this.name,
            barcode: null,
            quickProductId: null,
            activeProductId: null,
            props: {
                search: {
                    product_id: null,
                },
            },
            temp: {},
            initProduct: {},
            selectedVariation: null,
            productArray: {},
        };
    },
    computed: {
        setting() {
            return this.$store.getters["frontendSetting/lists"];
        },
    },
    watch: {
        name(value) {
            this.searchName = value;
        },
    },
    methods: {
        clearName() {
            this.searchName = "";
            this.$emit("update:name", "");
            this.$emit("search");
        },
        openProductModal(productId) {
            if (!productId) return;
            this.activeProductId = productId;
            this.quickProductId = null;
        },
        closeProductModal() {
            targetService.hideTarget("variation-modal", "modal-active");
            setTimeout(() => {
                this.activeProductId = null;
            }, 300);
        },
        scanBarcode() {
            if (!this.barcode) return;

            let code = this.barcode.toString();
            if (code.length > 8) {
                code = code.slice(0, -1);
            } else if (code.length > 7) {
                code = code.slice(0, -1);
            }

            const barcode = parseInt(code, 10);
            if (Number.isNaN(barcode)) {
                alertService.error(this.$t("label.barcode"));
                this.barcode = null;
                return;
            }

            this.$store.dispatch("product/barcodeProduct", barcode).then((barcodeRes) => {
                this.props.search.product_id = barcodeRes.data.data.product_id;

                this.$store.dispatch("posProduct/show", this.props.search).then((res) => {
                    this.initProduct = {
                        isVariation: false,
                        variationId: null,
                        sku: res.data.data.sku,
                        stock: res.data.data.stock,
                        quantity: 1,
                        discount: 0,
                        price: res.data.data.price,
                        oldPrice: res.data.data.old_price,
                        totalPrice: res.data.data.price,
                    };
                    this.temp = {
                        name: res.data.data.name,
                        image: res.data.data.image,
                        isVariation: false,
                        variationId: null,
                        productId: res.data.data.id,
                        sku: res.data.data.sku,
                        stock: res.data.data.stock,
                        taxes: res.data.data.taxes,
                        quantity: 1,
                        discount: 0,
                        price: res.data.data.price,
                        oldPrice: res.data.data.old_price,
                        totalPrice: res.data.data.price,
                    };

                    if (barcodeRes.data.data.variation_id) {
                        this.selectedVariation = barcodeRes.data.data.variation_id;
                        this.$store.dispatch("posProductVariation/barcodeVariationProduct", barcodeRes.data.data.variation_id).then((variationRes) => {
                            this.temp.isVariation = true;
                            this.temp.variationId = variationRes.data.data.id;
                            this.temp.sku = variationRes.data.data.sku;
                            this.temp.stock = variationRes.data.data.stock;
                            this.temp.quantity = 1;
                            this.temp.discount = 0;
                            this.temp.price = variationRes.data.data.price;
                            this.temp.oldPrice = variationRes.data.data.old_price;
                            this.temp.totalPrice = variationRes.data.data.price;

                            if (this.temp.stock > 0) {
                                this.addToCart();
                            } else {
                                alertService.error(this.$t("label.stock_out"));
                                this.barcode = null;
                            }
                        }).catch(() => {
                            this.barcode = null;
                        });
                    } else if (this.temp.stock > 0) {
                        this.addToCart();
                    } else {
                        alertService.error(this.$t("label.stock_out"));
                        this.barcode = null;
                    }
                }).catch(() => {
                    this.barcode = null;
                });
            }).catch((err) => {
                alertService.error(err.response?.data?.message);
                this.barcode = null;
            });
        },
        addToCart() {
            this.productArray = {
                name: this.temp.name,
                product_id: this.temp.productId,
                image: this.temp.image,
                variation_names: "",
                variation_id: this.temp.variationId,
                sku: this.temp.sku,
                stock: this.temp.stock,
                taxes: this.temp.taxes,
                quantity: this.temp.quantity,
                discount: this.temp.discount,
                price: this.temp.price,
                old_price: this.temp.oldPrice,
                total_price: this.temp.totalPrice,
            };

            const resetBarcode = () => {
                this.barcode = null;
                this.productArray = {};
                this.selectedVariation = null;
                this.resetTemp();
            };

            if (this.selectedVariation) {
                this.$store.dispatch("posProductVariation/ancestorsToString", this.selectedVariation).then((res) => {
                    this.productArray.variation_names = res.data.data;
                    this.$store.dispatch("posCart/lists", this.productArray).then(() => {
                        alertService.success(this.$t("message.add_to_cart"));
                        resetBarcode();
                    }).catch(() => {
                        alertService.error(this.$t("message.maximum_quantity"));
                        resetBarcode();
                    });
                }).catch(() => {});
            } else {
                this.$store.dispatch("posCart/lists", this.productArray).then(() => {
                    alertService.success(this.$t("message.add_to_cart"));
                    resetBarcode();
                }).catch(() => {
                    alertService.error(this.$t("message.maximum_quantity"));
                    resetBarcode();
                });
            }
        },
        resetTemp() {
            this.temp.isVariation = this.initProduct.isVariation;
            this.temp.variationId = this.initProduct.variationId;
            this.temp.sku = this.initProduct.sku;
            this.temp.stock = this.initProduct.stock;
            this.temp.quantity = this.initProduct.quantity;
            this.temp.discount = this.initProduct.discount;
            this.temp.price = this.initProduct.price;
            this.temp.oldPrice = this.initProduct.oldPrice;
            this.temp.totalPrice = this.initProduct.price;
        },
    },
};
</script>
