<template>
    <Teleport to="body">
        <div id="whatsapp-variation-modal"
            class="fixed inset-0 z-[9999] h-dvh w-screen overflow-y-auto bg-black/50 p-3 opacity-0 invisible transition-all duration-500">
            <div class="mx-auto w-full max-w-2xl rounded-xl bg-white transition-all duration-500">
                <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-4">
                    <div class="min-w-0">
                        <h3 class="truncate text-lg font-bold capitalize">{{ product.name || $t('label.product_variation') }}</h3>
                        <p v-if="product.sku" class="text-xs text-gray-400">SKU: {{ displaySku }}</p>
                    </div>
                    <button type="button" class="lab-line-circle-cross flex-shrink-0 text-lg text-[#E93C3C]" @click="close"></button>
                </div>

                <LoadingComponent :props="loading" />

                <div v-if="!loading.isActive && product.id" class="p-4">
                    <div class="mb-5 flex items-start gap-4">
                        <img :src="product.image" :alt="product.name"
                            class="h-24 w-24 flex-shrink-0 rounded-lg border object-cover" />
                        <div>
                            <p class="text-xl font-bold">
                                {{ currencyFormat(unitPrice) }}
                            </p>
                            <del v-if="product.is_offer && unitOldPrice > unitPrice" class="text-sm text-[#FF6262]">
                                {{ currencyFormat(unitOldPrice) }}
                            </del>
                        </div>
                    </div>

                    <div v-if="hasVariations" class="mb-5 rounded-lg border border-slate-100 bg-slate-50/60 p-4">
                        <p class="mb-3 text-sm font-medium text-gray-600">{{ $t('label.product_variation') }}</p>
                        <VariationComponent :key="variationTreeKey" :method="onVariationSelect"
                            :variations="initialVariations" />
                        <p v-if="hasVariations && !finalVariation" class="mt-2 text-xs text-amber-600">
                            {{ $t('label.please_select') }}
                        </p>
                    </div>

                    <div v-if="canShowQuantity" class="mb-5">
                        <label class="db-field-title required">{{ $t('label.quantity') }}</label>
                        <div class="mt-2 flex flex-wrap items-center gap-4">
                            <div class="inline-flex items-center gap-1 rounded-full bg-[#F7F7FC] p-1">
                                <button type="button" @click="quantityDecrement"
                                    :class="quantity <= 1 ? 'cursor-not-allowed opacity-50' : ''"
                                    class="lab-fill-circle-minus text-lg leading-none transition hover:text-primary"></button>
                                <input type="number" v-model.number="quantity" min="1" :max="stock"
                                    class="h-5 w-14 border-0 bg-transparent text-center text-sm font-medium focus:outline-none"
                                    @keypress="onlyNumber($event)" @keyup="normalizeQuantity" @change="normalizeQuantity" />
                                <button type="button" @click="quantityIncrement"
                                    :class="quantity >= stock ? 'cursor-not-allowed opacity-50' : ''"
                                    class="lab-fill-circle-plus text-lg leading-none transition hover:text-primary"></button>
                            </div>
                            <p v-if="stock > 0" class="text-sm capitalize text-gray-600">
                                {{ $t('label.available') }}: <b>{{ stock }}</b>
                                <span v-if="product.unit"> {{ product.unit }}</span>
                            </p>
                            <p v-else class="text-sm capitalize text-red-500">{{ $t('label.stock_out') }}</p>
                        </div>
                        <p v-if="quantity > 1" class="mt-3 text-sm font-semibold text-green-600">
                            {{ $t('label.total_price') }}: {{ currencyFormat(lineTotal) }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                        <button type="button" class="db-btn bg-primary text-white" :disabled="!canAddToCart"
                            @click="addToCart">
                            <i class="lab lab-line-bag"></i>
                            <span>{{ $t('button.add_to_cart') }}</span>
                        </button>
                        <button type="button" class="db-btn-outline" @click="close">
                            {{ $t('button.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script>
import LoadingComponent from "../components/LoadingComponent";
import VariationComponent from "../components/VariationComponent";
import targetService from "../../../services/targetService";
import appService from "../../../services/appService";
import alertService from "../../../services/alertService";

export default {
    name: "WhatsappOrderVariationModal",
    components: {
        LoadingComponent,
        VariationComponent,
    },
    props: {
        productId: {
            type: Number,
            default: null,
        },
    },
    emits: ["close", "added"],
    data() {
        return {
            loading: { isActive: false },
            product: {},
            hasVariations: false,
            finalVariation: null,
            quantity: 1,
            stock: 0,
            unitPrice: 0,
            unitOldPrice: 0,
            displaySku: "",
            variationTreeKey: 0,
        };
    },
    computed: {
        setting() {
            return this.$store.getters["frontendSetting/lists"];
        },
        initialVariations() {
            return this.$store.getters["posProductVariation/initialVariation"];
        },
        canShowQuantity() {
            if (!this.hasVariations) {
                return true;
            }
            return this.finalVariation !== null && !!this.finalVariation.sku;
        },
        canAddToCart() {
            if (!this.product.id || this.stock <= 0 || this.quantity < 1) {
                return false;
            }
            if (this.hasVariations && (!this.finalVariation || !this.finalVariation.sku)) {
                return false;
            }
            return this.quantity <= this.stock;
        },
        lineTotal() {
            return this.unitPrice * this.quantity;
        },
    },
    watch: {
        productId: {
            immediate: true,
            handler(id) {
                if (id) {
                    this.open(id);
                } else {
                    this.resetState();
                }
            },
        },
    },
    beforeUnmount() {
        this.hideModal();
    },
    methods: {
        onlyNumber(e) {
            return appService.onlyNumber(e);
        },
        currencyFormat(amount) {
            return appService.currencyFormat(
                amount,
                this.setting.site_digit_after_decimal_point,
                this.setting.site_default_currency_symbol,
                this.setting.site_currency_position
            );
        },
        resetState() {
            this.product = {};
            this.hasVariations = false;
            this.finalVariation = null;
            this.quantity = 1;
            this.stock = 0;
            this.unitPrice = 0;
            this.unitOldPrice = 0;
            this.displaySku = "";
            this.$store.commit("posProductVariation/initialVariation", []);
            this.$store.commit("posProductVariation/childrenVariation", []);
        },
        open(productId) {
            this.resetState();
            this.variationTreeKey = Date.now();
            this.loading.isActive = true;

            this.$store.dispatch("posProduct/show", { product_id: productId, review_limit: 3 }).then((res) => {
                const data = res.data.data;
                this.product = data;
                this.unitPrice = data.price;
                this.unitOldPrice = data.old_price;
                this.stock = Number(data.stock) || 0;
                this.displaySku = data.sku;

                return this.$store.dispatch("posProductVariation/initialVariation", data.id);
            }).then((varRes) => {
                const variations = varRes.data.data || [];
                this.hasVariations = variations.length > 0;

                if (!this.hasVariations && this.stock <= 0) {
                    alertService.error(this.$t("label.stock_out"));
                    this.close();
                }

                this.loading.isActive = false;
                this.showModal();
            }).catch((err) => {
                this.loading.isActive = false;
                alertService.error(err.response?.data?.message || this.$t("message.no_data_found"));
                this.close();
            });
        },
        showModal() {
            this.$nextTick(() => {
                targetService.showTarget("whatsapp-variation-modal", "modal-active");
            });
        },
        hideModal() {
            targetService.hideTarget("whatsapp-variation-modal", "modal-active");
        },
        close() {
            this.hideModal();
            this.$emit("close");
        },
        onVariationSelect(variation) {
            this.finalVariation = null;
            this.quantity = 1;

            if (variation && variation.sku) {
                this.finalVariation = variation;
                this.unitPrice = variation.price;
                this.unitOldPrice = variation.old_price;
                this.stock = Number(variation.stock) || 0;
                this.displaySku = variation.sku;
            } else if (!this.hasVariations) {
                this.unitPrice = this.product.price;
                this.unitOldPrice = this.product.old_price;
                this.stock = Number(this.product.stock) || 0;
                this.displaySku = this.product.sku;
            } else {
                this.unitPrice = this.product.price;
                this.unitOldPrice = this.product.old_price;
                this.stock = 0;
                this.displaySku = this.product.sku;
            }
        },
        normalizeQuantity() {
            let qty = parseInt(this.quantity, 10);
            if (Number.isNaN(qty) || qty < 1) {
                qty = 1;
            }
            if (qty > this.stock) {
                qty = this.stock;
            }
            this.quantity = qty;
        },
        quantityIncrement() {
            if (this.quantity < this.stock) {
                this.quantity++;
            }
        },
        quantityDecrement() {
            if (this.quantity > 1) {
                this.quantity--;
            }
        },
        addToCart() {
            if (!this.canAddToCart) {
                if (this.hasVariations && !this.finalVariation) {
                    alertService.error(this.$t("label.please_select"));
                }
                return;
            }

            const payload = {
                name: this.product.name,
                product_id: this.product.id,
                image: this.product.image,
                variation_names: "",
                variation_id: this.finalVariation ? this.finalVariation.id : null,
                sku: this.displaySku || this.product.sku,
                stock: this.stock,
                taxes: this.product.taxes,
                quantity: this.quantity,
                discount: 0,
                price: this.unitPrice,
                old_price: this.unitOldPrice,
                total_price: this.lineTotal,
            };

            const addItem = () => {
                this.$store.dispatch("posCart/lists", payload).then(() => {
                    alertService.success(this.$t("message.add_to_cart"));
                    this.$emit("added");
                    this.close();
                }).catch(() => {
                    alertService.error(this.$t("message.maximum_quantity"));
                });
            };

            if (this.finalVariation) {
                this.$store.dispatch("posProductVariation/ancestorsToString", this.finalVariation.id).then((res) => {
                    payload.variation_names = res.data.data;
                    addItem();
                }).catch(() => {
                    addItem();
                });
            } else {
                addItem();
            }
        },
    },
};
</script>
