<template>
    <LoadingComponent :props="loading" />
    <div class="col-12">
        <form @submit.prevent="save" class="block w-full">
            <div class="db-card">
                <div class="db-card-header">
                    <h3 class="db-card-title">
                        {{ isEditing ? $t('button.edit') : $t('button.add_product') }}
                    </h3>
                </div>
                <div class="db-card-body">
                    <div class="form-row">
                        <div class="form-col-12 sm:form-col-6">
                            <div class="flex items-center justify-between">
                                <label for="name" class="db-field-title">{{ $t("label.name") }}</label>
                                <button v-if="aiStatus" type="button" @click="generateAiName" class="flex items-center text-xs cursor-pointer text-primary">
                                    <i class="lab-fill-ai text-[#8B5CF6]"></i>
                                    <span :class="aiNameLoading ? '' : 'hidden'" class="ai-text-animation" role="status">
                                        {{ $t("label.just_a_second") }}
                                    </span>
                                    <span :class="!aiNameLoading && !form.name ? '' : 'hidden'" class="btn-text">
                                        {{ $t("label.generate") }}
                                    </span>
                                    <span :class="!aiNameLoading && form.name ? '' : 'hidden'" class="btn-text">
                                        {{ $t("label.regenerate") }}
                                    </span>
                                </button>
                            </div>
                            <input v-model="form.name" v-bind:class="errors.name ? 'invalid' : ''" type="text"
                                id="name" class="db-field-control">
                            <small class="db-field-alert" v-if="errors.name">{{ errors.name[0] }}</small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label for="sku" class="db-field-title required">{{ $t("label.sku") }}</label>
                            <div class="db-group-field">
                                <input v-on:keypress="onlyNumber($event)" v-model="form.sku"
                                    v-bind:class="errors.sku ? 'invalid' : ''" type="text" id="sku">
                                <button type="button" @click="getSku" class="lab lab-fill-shuffle"></button>
                            </div>
                            <small class="db-field-alert" v-if="errors.sku">{{ errors.sku[0] }}</small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label for="product_category_id" class="db-field-title required">
                                {{ $t("label.category") }}
                            </label>
                            <vue-select class="db-field-control f-b-custom-select" id="product_category_id"
                                v-bind:class="errors.product_category_id ? 'invalid' : ''"
                                v-model="form.product_category_id" :options="productCategories" label-by="option"
                                value-by="id" :closeOnSelect="true" :searchable="true" :clearOnClose="true" placeholder="--"
                                search-placeholder="--" />
                            <small class="db-field-alert" v-if="errors.product_category_id">
                                {{ errors.product_category_id[0] }}
                            </small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label for="barcode_id" class="db-field-title required">{{ $t("label.barcode") }}</label>
                            <vue-select class="db-field-control f-b-custom-select" id="barcode_id"
                                v-bind:class="errors.barcode_id ? 'invalid' : ''" v-model="form.barcode_id"
                                :options="barcodes" label-by="name" value-by="id" :closeOnSelect="true" :searchable="true"
                                :clearOnClose="true" placeholder="--" search-placeholder="--" />
                            <small class="db-field-alert" v-if="errors.barcode_id">
                                {{ errors.barcode_id[0] }}
                            </small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label for="buying_price" class="db-field-title required">{{ $t("label.buying_price") }}</label>
                            <input v-on:keypress="floatNumber($event)" v-model="form.buying_price"
                                v-bind:class="errors.buying_price ? 'invalid' : ''" type="text" id="buying_price"
                                class="db-field-control">
                            <small class="db-field-alert" v-if="errors.buying_price">{{ errors.buying_price[0] }}</small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label for="selling_price" class="db-field-title required">
                                {{ $t("label.selling_price") }}
                            </label>
                            <input v-on:keypress="floatNumber($event)" v-model="form.selling_price"
                                v-bind:class="errors.selling_price ? 'invalid' : ''" type="text" id="selling_price"
                                class="db-field-control">
                            <small class="db-field-alert" v-if="errors.selling_price">{{ errors.selling_price[0] }}</small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label for="tax_id" class="db-field-title">{{ $t("label.tax") }}</label>
                            <vue-select class="db-field-control f-b-custom-select" id="tax_id"
                                v-bind:class="errors.tax_id ? 'invalid' : ''" v-model="form.tax_id" :options="taxes"
                                label-by="name" value-by="id" :closeOnSelect="true" :searchable="true" :clearOnClose="true"
                                placeholder="--" search-placeholder="--" :multiple="true" />
                            <small class="db-field-alert" v-if="errors.tax_id">{{ errors.tax_id[0] }}</small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label for="product_brand_id" class="db-field-title">{{ $t("label.brand") }}</label>
                            <vue-select class="db-field-control f-b-custom-select" id="product_brand_id"
                                v-bind:class="errors.product_brand_id ? 'invalid' : ''"
                                v-model="form.product_brand_id" :options="productBrands" label-by="name" value-by="id"
                                :closeOnSelect="true" :searchable="true" :clearOnClose="true" placeholder="--"
                                search-placeholder="--" />
                            <small class="db-field-alert" v-if="errors.product_brand_id">
                                {{ errors.product_brand_id[0] }}
                            </small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label class="db-field-title required">{{ $t("label.status") }}</label>
                            <div class="db-field-radio-group">
                                <div class="db-field-radio">
                                    <div class="custom-radio">
                                        <input type="radio" v-model="form.status" id="active"
                                            :value="enums.statusEnum.ACTIVE" class="custom-radio-field">
                                        <span class="custom-radio-span"></span>
                                    </div>
                                    <label for="active" class="db-field-label">{{ $t('label.active') }}</label>
                                </div>
                                <div class="db-field-radio">
                                    <div class="custom-radio">
                                        <input type="radio" class="custom-radio-field" v-model="form.status"
                                            id="inactive" :value="enums.statusEnum.INACTIVE">
                                        <span class="custom-radio-span"></span>
                                    </div>
                                    <label for="inactive" class="db-field-label">{{ $t('label.inactive') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label class="db-field-title required" for="yes">{{ $t("label.can_purchasable") }}</label>
                            <div class="db-field-radio-group">
                                <div class="db-field-radio">
                                    <div class="custom-radio">
                                        <input type="radio" v-model="form.can_purchasable" id="yes"
                                            :value="enums.askEnum.YES" class="custom-radio-field">
                                        <span class="custom-radio-span"></span>
                                    </div>
                                    <label for="yes" class="db-field-label">{{ $t('label.yes') }}</label>
                                </div>
                                <div class="db-field-radio">
                                    <div class="custom-radio">
                                        <input type="radio" class="custom-radio-field" v-model="form.can_purchasable"
                                            id="no" :value="enums.askEnum.NO">
                                        <span class="custom-radio-span"></span>
                                    </div>
                                    <label for="no" class="db-field-label">{{ $t('label.no') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label class="db-field-title required" for="enable">{{ $t("label.show_stock_out") }}</label>
                            <div class="db-field-radio-group">
                                <div class="db-field-radio">
                                    <div class="custom-radio">
                                        <input type="radio" v-model="form.show_stock_out" id="enable"
                                            :value="enums.activityEnum.ENABLE" class="custom-radio-field">
                                        <span class="custom-radio-span"></span>
                                    </div>
                                    <label for="enable" class="db-field-label">{{ $t('label.enable') }}</label>
                                </div>
                                <div class="db-field-radio">
                                    <div class="custom-radio">
                                        <input type="radio" class="custom-radio-field" v-model="form.show_stock_out"
                                            id="disable" :value="enums.activityEnum.DISABLE">
                                        <span class="custom-radio-span"></span>
                                    </div>
                                    <label for="disable" class="db-field-label">{{ $t('label.disable') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label class="db-field-title required" for="refundableYes">{{ $t("label.refundable") }}</label>
                            <div class="db-field-radio-group">
                                <div class="db-field-radio">
                                    <div class="custom-radio">
                                        <input type="radio" v-model="form.refundable" id="refundableYes"
                                            :value="enums.askEnum.YES" class="custom-radio-field">
                                        <span class="custom-radio-span"></span>
                                    </div>
                                    <label for="refundableYes" class="db-field-label">{{ $t('label.yes') }}</label>
                                </div>
                                <div class="db-field-radio">
                                    <div class="custom-radio">
                                        <input type="radio" class="custom-radio-field" v-model="form.refundable"
                                            id="refundableNo" :value="enums.askEnum.NO">
                                        <span class="custom-radio-span"></span>
                                    </div>
                                    <label for="refundableNo" class="db-field-label">{{ $t('label.no') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label class="db-field-title" for="useRandomSale">
                                Simulated Starting Sales (0 or 10 to disable)
                            </label>
                            <input v-on:keypress="onlyNumber($event)" v-model="form.use_random_sale"
                                v-bind:class="errors.use_random_sale ? 'invalid' : ''" type="number" min="0"
                                id="useRandomSale" class="db-field-control">
                            <small class="db-field-alert" v-if="errors.use_random_sale">
                                {{ errors.use_random_sale[0] }}
                            </small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label class="db-field-title required" for="isShowViewersYes">Show Active Viewers</label>
                            <div class="db-field-radio-group">
                                <div class="db-field-radio">
                                    <div class="custom-radio">
                                        <input type="radio" v-model="form.is_show_viewers" id="isShowViewersYes"
                                            :value="enums.askEnum.YES" class="custom-radio-field">
                                        <span class="custom-radio-span"></span>
                                    </div>
                                    <label for="isShowViewersYes" class="db-field-label">{{ $t('label.yes') }}</label>
                                </div>
                                <div class="db-field-radio">
                                    <div class="custom-radio">
                                        <input type="radio" class="custom-radio-field" v-model="form.is_show_viewers"
                                            id="isShowViewersNo" :value="enums.askEnum.NO">
                                        <span class="custom-radio-span"></span>
                                    </div>
                                    <label for="isShowViewersNo" class="db-field-label">{{ $t('label.no') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label for="maximum_purchase_quantity" class="db-field-title required">
                                {{ $t("label.maximum_purchase_quantity") }}
                            </label>
                            <input v-on:keypress="onlyNumber($event)" v-model="form.maximum_purchase_quantity"
                                v-bind:class="errors.maximum_purchase_quantity ? 'invalid' : ''" type="text"
                                id="maximum_purchase_quantity" class="db-field-control">
                            <small class="db-field-alert" v-if="errors.maximum_purchase_quantity">
                                {{ errors.maximum_purchase_quantity[0] }}
                            </small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label for="low_stock_quantity_warning" class="db-field-title required">
                                {{ $t("label.low_stock_quantity_warning") }}
                            </label>
                            <input v-on:keypress="onlyNumber($event)" v-model="form.low_stock_quantity_warning"
                                v-bind:class="errors.low_stock_quantity_warning ? 'invalid' : ''" type="text"
                                id="low_stock_quantity_warning" class="db-field-control">
                            <small class="db-field-alert" v-if="errors.low_stock_quantity_warning">
                                {{ errors.low_stock_quantity_warning[0] }}
                            </small>
                        </div>

                        <div class="form-col-12 sm:form-col-12">
                            <label for="unit" class="db-field-title required">{{ $t("label.unit") }}</label>
                            <vue-select class="db-field-control f-b-custom-select" id="unit_id"
                                v-bind:class="errors.unit_id ? 'invalid' : ''" v-model="form.unit_id" :options="units"
                                label-by="name_code" value-by="id" :closeOnSelect="true" :searchable="true"
                                :clearOnClose="true" placeholder="--" search-placeholder="--" />
                            <small class="db-field-alert" v-if="errors.unit_id">
                                {{ errors.unit_id[0] }}
                            </small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label for="weight" class="db-field-title">{{ $t("label.weight") }}</label>
                            <input v-model="form.weight" v-bind:class="errors.weight ? 'invalid' : ''" type="text"
                                id="weight" class="db-field-control">
                            <small class="db-field-alert" v-if="errors.weight">{{ errors.weight[0] }}</small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label for="warranty" class="db-field-title">{{ $t("label.warranty") }}</label>
                            <input v-model="form.warranty" v-bind:class="errors.warranty ? 'invalid' : ''" type="text"
                                id="warranty" class="db-field-control">
                            <small class="db-field-alert" v-if="errors.warranty">{{ errors.warranty[0] }}</small>
                        </div>

                        <div class="form-col-12 sm:form-col-12">
                            <div class="relative flex items-center justify-between inline-block w-full group">
                                <label for="tags" class="db-field-title">{{ $t("label.tags") }}
                                    <i class="lab lab-fill-info lab-font-size-14"></i>
                                </label>
                                <span class="min-w-[200px] absolute bottom-full mb-2 hidden group-hover:block bg-gray-800 text-white text-sm rounded px-2 py-1">
                                    {{ $t('message.tags_hint') }}
                                </span>
                                <button v-if="aiStatus" type="button" @click="generateAiTags" class="flex items-center text-xs cursor-pointer text-primary">
                                    <i class="lab-fill-ai text-[#8B5CF6]"></i>
                                    <span :class="aiTagsLoading ? '' : 'hidden'" class="ai-text-animation" role="status">
                                        {{ $t("label.just_a_second") }}
                                    </span>
                                    <span :class="!aiTagsLoading && form.convertTags.length === 0 ? '' : 'hidden'" class="btn-text">
                                        {{ $t("label.generate") }}
                                    </span>
                                    <span :class="!aiTagsLoading && form.convertTags.length > 0 ? '' : 'hidden'" class="btn-text">
                                        {{ $t("label.regenerate") }}
                                    </span>
                                </button>
                            </div>
                            <vue-tags-input id="tags" v-bind:class="errors.tags ? 'invalid-tag' : ''" placeholder=""
                                v-model="tag" :tags="form.convertTags"
                                @tags-changed="newTags => form.convertTags = newTags" />
                            <small class="db-field-alert" v-if="errors.tags">{{ errors.tags[0] }}</small>
                        </div>

                        <div class="form-col-12">
                            <div class="flex items-center justify-between">
                                <label for="description" class="db-field-title">{{ $t("label.description") }}</label>
                                <button v-if="aiStatus" type="button" @click="generateAiDescription" class="flex items-center text-xs cursor-pointer text-primary">
                                    <i class="lab-fill-ai text-[#8B5CF6]"></i>
                                    <span :class="aiDescriptionLoading ? '' : 'hidden'" class="ai-text-animation" role="status">
                                        {{ $t("label.just_a_second") }}
                                    </span>
                                    <span :class="!aiDescriptionLoading && !form.description ? '' : 'hidden'" class="btn-text">
                                        {{ $t("label.generate") }}
                                    </span>
                                    <span :class="!aiDescriptionLoading && form.description ? '' : 'hidden'" class="btn-text">
                                        {{ $t("label.regenerate") }}
                                    </span>
                                </button>
                            </div>
                            <div :class="errors.description ? 'invalid textarea-error-box-style' : ''">
                                <quill-editor id="description" v-model:value="form.description"
                                    class="!h-40 textarea-border-radius" />
                            </div>
                            <small class="db-field-alert" v-if="errors.description">
                                {{ errors.description[0] }}
                            </small>
                        </div>

                        <div class="form-col-12">
                            <div class="flex flex-wrap gap-3 mt-4">
                                <button type="submit" class="py-2 text-white db-btn bg-primary">
                                    <i class="lab lab-fill-save"></i>
                                    <span>{{ $t("label.save") }}</span>
                                </button>
                                <router-link :to="{ name: 'admin.products.list' }" class="modal-btn-outline modal-close">
                                    <i class="lab lab-fill-close-circle"></i>
                                    <span>{{ $t("button.close") }}</span>
                                </router-link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<script>
import LoadingComponent from "../components/LoadingComponent";
import askEnum from "../../../enums/modules/askEnum";
import statusEnum from "../../../enums/modules/statusEnum";
import activityEnum from "../../../enums/modules/activityEnum";
import alertService from "../../../services/alertService";
import appService from "../../../services/appService";
import VueTagsInput from "@sipec/vue3-tags-input";
import { quillEditor } from 'vue3-quill';
import _ from "lodash";

export default {
    name: "ProductCreateAndEditComponent",
    components: { LoadingComponent, quillEditor, VueTagsInput },
    data() {
        return {
            tag: "",
            loading: {
                isActive: false
            },
            aiNameLoading: false,
            aiDescriptionLoading: false,
            aiTagsLoading: false,
            enums: {
                statusEnum: statusEnum,
                askEnum: askEnum,
                activityEnum: activityEnum,
            },
            errors: {},
            productCategories: [],
            units: [],
            productBrands: [],
            taxes: [],
            barcodes: [],
            form: {
                name: "",
                sku: "",
                product_category_id: null,
                barcode_id: null,
                buying_price: "",
                selling_price: "",
                tax_id: [],
                product_brand_id: null,
                status: statusEnum.ACTIVE,
                can_purchasable: askEnum.NO,
                show_stock_out: activityEnum.DISABLE,
                refundable: askEnum.NO,
                use_random_sale: askEnum.YES,
                is_show_viewers: askEnum.YES,
                maximum_purchase_quantity: "",
                low_stock_quantity_warning: "",
                unit_id: null,
                weight: "",
                warranty: "",
                tags: "",
                convertTags: [],
                description: "",
            },
            listSearch: {
                paginate: 1,
                page: 1,
                per_page: 10,
                order_column: 'id',
                order_type: 'desc',
            },
        }
    },
    computed: {
        isEditing: function () {
            return this.$store.getters['product/temp'].isEditing;
        },
        aiStatus: function () {
            return this.$store.getters['ai/status'];
        },
    },
    mounted() {
        this.loadOptions();
        this.productInfo();
    },
    methods: {
        floatNumber(e) {
            return appService.floatNumber(e);
        },
        onlyNumber(e) {
            return appService.onlyNumber(e);
        },
        loadOptions: function () {
            this.loading.isActive = true;
            Promise.all([
                this.$store.dispatch('productCategory/depthTrees'),
                this.$store.dispatch('productBrand/lists', { order_column: 'id', order_type: 'asc' }),
                this.$store.dispatch('unit/lists', { order_column: 'id', order_type: 'asc' }),
                this.$store.dispatch('tax/lists', { order_column: 'id', order_type: 'asc' }),
                this.$store.dispatch('barcode/lists', { order_column: 'id', order_type: 'asc' }),
                this.$store.dispatch('ai/fetchStatus'),
            ]).then(([categories, brands, units, taxes, barcodes]) => {
                this.productCategories = categories.data.data;
                this.productBrands = brands.data.data;
                this.units = units.data.data;
                this.taxes = taxes.data.data;
                this.barcodes = barcodes.data.data;
                this.loading.isActive = false;
            }).catch(() => {
                this.loading.isActive = false;
            });
        },
        productInfo: function () {
            const id = this.$route.params.id;
            if (id && !isNaN(id)) {
                this.loading.isActive = true;
                this.$store.dispatch('product/editProduct', id).then((res) => {
                    const product = res.data.data[0];
                    if (product) {
                        this.fillForm(product);
                    }
                    this.loading.isActive = false;
                }).catch(() => {
                    this.loading.isActive = false;
                    alertService.error(this.$t('message.no_data_found'));
                });
            } else {
                this.$store.dispatch('product/reset');
                this.getSku();
            }
        },
        fillForm: function (product) {
            this.form.name = product.name;
            this.form.sku = product.sku;
            this.form.product_category_id = product.product_category_id;
            this.form.barcode_id = product.barcode_id;
            this.form.buying_price = product.flat_buying_price;
            this.form.selling_price = product.flat_selling_price;
            this.form.tax_id = this.taxUpdate(product.tax_id);
            this.form.product_brand_id = product.product_brand_id;
            this.form.status = product.status;
            this.form.can_purchasable = product.can_purchasable;
            this.form.show_stock_out = product.show_stock_out;
            this.form.refundable = product.refundable;
            this.form.use_random_sale = product.use_random_sale;
            this.form.is_show_viewers = product.is_show_viewers;
            this.form.maximum_purchase_quantity = product.maximum_purchase_quantity;
            this.form.low_stock_quantity_warning = product.low_stock_quantity_warning;
            this.form.unit_id = product.unit_id;
            this.form.weight = product.weight;
            this.form.warranty = product.warranty;
            this.form.convertTags = this.tagUpdate(product.product_tags);
            this.form.tags = "";
            this.form.description = product.description;
        },
        tagUpdate: function (objects) {
            let tags = [];
            _.forEach(objects, (object) => {
                tags.push({ "text": object.name, "tiClasses": ["ti-valid"] });
            });
            return tags;
        },
        taxUpdate: function (objects) {
            let taxes = [];
            _.forEach(objects, (object) => {
                taxes.push(object.tax_id);
            });
            return taxes;
        },
        getSku: function () {
            this.$store.dispatch("product/getSku").then((res) => {
                this.form.sku = res.data.data.product_sku;
            }).catch(() => {});
        },
        save: function () {
            try {
                this.form.tags = JSON.stringify(this.form.convertTags);
                const tempId = this.$store.getters['product/temp'].temp_id;
                this.loading.isActive = true;
                this.$store.dispatch('product/save', {
                    form: this.form,
                    search: this.listSearch,
                }).then((res) => {
                    this.loading.isActive = false;
                    alertService.successFlip((tempId === null ? 0 : 1), this.$t('menu.products'));
                    const productId = tempId ?? res.data.data.id;
                    this.$router.push({ name: 'admin.product.show', params: { id: productId } });
                }).catch((err) => {
                    this.loading.isActive = false;
                    this.errors = {};
                    if (err.response && err.response.data && err.response.data.errors) {
                        this.errors = err.response.data.errors;
                    } else {
                        alertService.error(appService.apiErrorMessage(err));
                    }
                });
            } catch (err) {
                this.loading.isActive = false;
            }
        },
        generateAiName: function () {
            if (!this.form.name) {
                alertService.warning(this.$t('message.product_name_is_required_to_generate_name'));
                return;
            }
            this.aiNameLoading = true;
            this.$store.dispatch("ai/fetchName", { name: this.form.name }).then((res) => {
                this.aiNameLoading = false;
                if (res.data.data) {
                    this.form.name = res.data.data;
                }
            }).catch((err) => {
                this.aiNameLoading = false;
                alertService.error(err.response?.data?.message || err.message);
            });
        },
        generateAiDescription: function () {
            if (!this.form.name) {
                alertService.warning(this.$t('message.product_name_is_required_to_generate_description'));
                return;
            }
            this.aiDescriptionLoading = true;
            this.$store.dispatch("ai/fetchDescription", { name: this.form.name }).then((res) => {
                this.aiDescriptionLoading = false;
                if (res.data.data) {
                    this.form.description = res.data.data;
                }
            }).catch((err) => {
                this.aiDescriptionLoading = false;
                alertService.error(err.response?.data?.message || err.message);
            });
        },
        generateAiTags: function () {
            if (!this.form.name) {
                alertService.warning(this.$t('message.product_name_is_required_to_generate_tags'));
                return;
            }
            this.aiTagsLoading = true;
            this.$store.dispatch("ai/fetchTags", { name: this.form.name }).then((res) => {
                this.aiTagsLoading = false;
                if (res.data.data && Array.isArray(res.data.data)) {
                    this.form.convertTags = res.data.data.map(tag => ({ text: tag, tiClasses: ['ti-valid'] }));
                }
            }).catch((err) => {
                this.aiTagsLoading = false;
                alertService.error(err.response?.data?.message || err.message);
            });
        },
    }
}
</script>
