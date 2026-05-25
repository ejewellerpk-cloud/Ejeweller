<template>
    <div class="db-card-header">
        <h3 class="db-card-title">{{ $t('label.variation') }}</h3>
        <div class="db-card-filter">
            <ProductVariationCreateComponent :attributeProps="attributeProps" />
        </div>
    </div>
    <div class="db-card-body">
        <div v-if="variations.length > 0" class="db-table-responsive">
            <table class="db-table stripe">
                <thead class="db-table-head">
                    <tr class="db-table-head-tr">
                        <th class="db-table-head-th">{{ $t('label.variation') }}</th>
                        <th class="db-table-head-th">{{ $t('label.sku') }}</th>
                        <th class="db-table-head-th">{{ $t('label.price') }}</th>
                        <th class="db-table-head-th text-right">{{ $t('label.action') }}</th>
                    </tr>
                </thead>
                <tbody class="db-table-body">
                    <tr v-for="variation in variations" :key="variation.id" class="db-table-body-tr">
                        <td class="db-table-body-td">
                            <div class="flex flex-wrap gap-1.5">
                                <span v-for="(option, key) in variation.options" :key="key"
                                    class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-xs font-medium capitalize">
                                    <span class="text-slate-500">{{ key }}:</span>
                                    <span class="ml-1 text-slate-800">{{ option }}</span>
                                </span>
                            </div>
                        </td>
                        <td class="db-table-body-td font-mono text-sm">{{ variation.sku || '—' }}</td>
                        <td class="db-table-body-td font-semibold">{{ floatFormat(variation.price) }}</td>
                        <td class="db-table-body-td">
                            <div class="flex justify-end items-center gap-2">
                                <button type="button" @click="showVariationBarcode(variation)" class="db-table-action">
                                    <i class="lab lab-fill-scan-barcode text-cyan-500 bg-cyan-100"></i>
                                    <span class="db-tooltip">{{ $t('button.barcode') }}</span>
                                </button>
                                <SmIconModalEditComponent @click="editVariation(variation.id)" />
                                <SmIconDeleteComponent @click="destroyVariation(variation.id)" />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="text-sm text-slate-500 py-4">{{ $t('message.no_variations_yet') || 'No variations yet.' }}</p>
    </div>

    <ProductVariationBarcodeComponent :barcodeProps="barcodeProps" />
</template>

<script>
import alertService from "../../../../services/alertService";
import appService from "../../../../services/appService";
import SmIconModalEditComponent from "../../components/buttons/SmIconModalEditComponent.vue";
import SmIconDeleteComponent from "../../components/buttons/SmIconDeleteComponent.vue";
import ProductVariationCreateComponent from "./ProductVariationCreateComponent";
import ProductVariationBarcodeComponent from "./ProductVariationBarcodeComponent.vue";
import _ from "lodash";

export default {
    name: "ProductVariationListComponent",
    components: { ProductVariationCreateComponent, SmIconDeleteComponent, SmIconModalEditComponent, ProductVariationBarcodeComponent },
    data() {
        return {
            loading: {
                isActive: false
            },
            productId: null,
            barcodeProps: {
                variation_id: null,
                sku: null,
                barcode_image: ''
            },
            attributeProps: {
                price: null,
                sku: null,
                image: null,
                elements: {},
                attribute: []
            },
        }
    },
    computed: {
        variations: function () {
            return this.$store.getters['productVariation/singleTree'];
        }
    },
    mounted() {
        try {
            this.loading.isActive = true;
            this.productId = this.$route.params.id;
            this.$store.dispatch('productVariation/singleTree', this.productId).then(res => {
                this.loading.isActive = false;
            }).catch((err) => {
                this.loading.isActive = false;
                alertService.error(err.response.data.message);
            });
        } catch (err) {
            this.loading.isActive = false;
            alertService.error(err.response.data.message);
        }
    },
    methods: {
        variationLabel: function (variation) {
            if (!variation?.options) {
                return '—';
            }
            return Object.entries(variation.options)
                .map(([key, value]) => `${key}: ${value}`)
                .join(' / ');
        },
        editVariation: function (productVariation) {
            appService.modalShow('#variationModal');
            this.loading.isActive = true;
            this.$store.dispatch('productVariation/edit', {
                productId: this.productId,
                id: productVariation
            }).then((res) => {
                this.loading.isActive = false;
                _.forEach(res.data.data, (element) => {
                    this.recursiveVariation(element);
                });
            }).catch((err) => {
                this.loading.isActive = false;
            })
        },
        showVariationBarcode: function (variation) {
            appService.modalShow('#variationBarcodeModal');
            this.barcodeProps.variation_id = variation.id;
            this.barcodeProps.sku = variation.sku;
            this.barcodeProps.barcode_image = variation.media[0].original_url;
        },
        destroyVariation: function (id) {
            appService.destroyConfirmation().then(res => {
                try {
                    this.loading.isActive = true;
                    this.$store.dispatch('productVariation/destroy', {
                        productVariationId: id,
                        productId: this.productId
                    }).then((res) => {
                        this.loading.isActive = false;
                        alertService.successFlip(null, this.$t('label.variation'));
                    }).catch((err) => {
                        this.loading.isActive = false;
                        alertService.error(err.response.data.message);
                    })
                } catch (err) {
                    this.loading.isActive = false;
                    alertService.error(err.response.data.message);
                }
            }).catch((err) => {
                this.loading.isActive = false;
            })
        },
        floatFormat(amount, decimal) {
            return appService.floatFormat(amount, decimal);
        },
        recursiveVariation: function (data) {
            this.attributeProps.elements[data.product_attribute_id] = data.product_attribute_option_id;
            if (data.sku !== null) {
                this.attributeProps.price = data.price;
                this.attributeProps.sku = data.sku;
                if (data.image) {
                    this.attributeProps.image = data.image;
                }
            }
            if (data.children) {
                _.forEach(data.children, (element) => {
                    this.recursiveVariation(element);
                });
            }
        },
    }
}
</script>
