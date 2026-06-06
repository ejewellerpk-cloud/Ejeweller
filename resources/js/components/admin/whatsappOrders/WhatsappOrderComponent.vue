<template>
    <LoadingComponent :props="loading" />
    <PoscustomerComponent store-module="whatsappOrder" v-on:onCustomverCreate="onCustomverCreate" />
    <ReceiptComponent :order="order" />

    <div class="col-12 pb-20 xl:pb-6">
        <div class="db-card mb-6">
            <div class="db-card-header flex-wrap gap-3">
                <h3 class="db-card-title">{{ $t('menu.whatsapp_order') }}</h3>
                <router-link :to="{ name: 'admin.whatsapp.orders.list' }" class="db-btn-outline py-1.5 text-sm">
                    <i class="lab lab-line-bag"></i>
                    <span>{{ $t('menu.whatsapp_orders') }}</span>
                </router-link>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 xl:col-8">
                <div class="db-card mb-6">
                    <div class="db-card-header">
                        <h3 class="db-card-title">{{ $t('label.customer') }} & {{ $t('label.shipping_address') }}</h3>
                    </div>
                    <div class="db-card-body">
                        <div class="form-row">
                            <div class="form-col-12">
                                <label class="db-field-title required">{{ $t('label.customer') }}</label>
                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <vue-select v-model="checkoutProps.form.customer_id" class="db-field-control flex-1 f-b-custom-select"
                                        :options="customers" label-by="name" value-by="id" :closeOnSelect="true"
                                        :searchable="true" :clearOnClose="true" :placeholder="$t('label.select_customer')"
                                        :search-placeholder="$t('label.search_customer')"
                                        @update:modelValue="fillShippingFromCustomer" />
                                    <button type="button" class="db-btn h-[38px] flex-shrink-0 bg-primary text-white"
                                        @click="addCustomer">
                                        <i class="lab lab-add-circle-line"></i>
                                        <span>{{ $t('button.add') }}</span>
                                    </button>
                                </div>
                            </div>
                            <div class="form-col-12 sm:form-col-6">
                                <label class="db-field-title required">{{ $t('label.full_name') }}</label>
                                <input v-model="shippingForm.shipping_full_name" type="text" class="db-field-control" />
                            </div>
                            <div class="form-col-12 sm:form-col-6">
                                <label class="db-field-title required">{{ $t('label.phone') }}</label>
                                <input v-model="shippingForm.shipping_phone" type="text" class="db-field-control" />
                            </div>
                            <div class="form-col-12 sm:form-col-6">
                                <label class="db-field-title">{{ $t('label.email') }}</label>
                                <input v-model="shippingForm.shipping_email" type="email" class="db-field-control" />
                            </div>
                            <div class="form-col-12">
                                <label class="db-field-title required">{{ $t('label.address') }}</label>
                                <textarea v-model="shippingForm.shipping_address" rows="2" class="db-field-control"></textarea>
                            </div>
                            <div class="form-col-12 sm:form-col-6">
                                <label class="db-field-title">{{ $t('label.city') }}</label>
                                <input v-model="shippingForm.shipping_city" type="text" class="db-field-control" />
                            </div>
                            <div class="form-col-12 sm:form-col-6">
                                <label class="db-field-title">{{ $t('label.state') }}</label>
                                <input v-model="shippingForm.shipping_state" type="text" class="db-field-control" />
                            </div>
                            <div class="form-col-12 sm:form-col-6">
                                <label class="db-field-title">{{ $t('label.country') }}</label>
                                <input v-model="shippingForm.shipping_country" type="text" class="db-field-control" />
                            </div>
                            <div class="form-col-12 sm:form-col-6">
                                <label class="db-field-title">{{ $t('label.zip_code') }}</label>
                                <input v-model="shippingForm.shipping_zip_code" type="text" class="db-field-control" />
                            </div>
                            <div class="form-col-12">
                                <label class="db-field-title">{{ $t('label.note') }}</label>
                                <textarea v-model="shippingForm.note" rows="2" class="db-field-control"
                                    :placeholder="$t('label.note')"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <WhatsappOrderProductCatalog :products="products" :categories="categories" :brands="brands"
                    :name="props.search.name" :category-id="checkoutProps.form.category" :brand-id="checkoutProps.form.brand"
                    @search="search" @reset="reset" @setCategory="setCategory" @setBrand="setBrand"
                    @update:name="props.search.name = $event" />

                <WhatsappOrderLineItemsTable :carts="carts" :setting="setting" @increment="quantityIncrement"
                    @decrement="quantityDecrement" @quantityUp="quantityUp" @remove="removeProduct"
                    @onlyNumber="onlyNumber" />
            </div>

            <div class="col-12 xl:col-4">
                <div class="xl:sticky xl:top-24">
                    <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title">{{ $t('label.order_summary') }}</h3>
                        </div>
                        <div class="db-card-body">
                            <div v-if="carts.length > 0" class="mb-4">
                                <label class="db-field-title">{{ $t('label.discount') }}</label>
                                <div class="flex h-[38px]">
                                    <div class="db-field-down-arrow">
                                        <select v-model="discountType"
                                            class="h-full w-[110px] cursor-pointer appearance-none border border-[#EFF0F6] ltr:rounded-tl ltr:rounded-bl ltr:pl-3 rtl:rounded-tr rtl:rounded-br rtl:pr-3 text-sm">
                                            <option :value="discountTypeEnum.PERCENTAGE">{{ $t('label.percentage') }}</option>
                                            <option :value="discountTypeEnum.FIXED">{{ $t('label.fixed') }}</option>
                                        </select>
                                    </div>
                                    <input v-model="discount" type="text" class="h-full flex-1 border-y border-[#EFF0F6] px-3 text-sm"
                                        :placeholder="$t('label.add_discount')" @keypress="floatNumber($event)" />
                                    <button type="button" class="h-full w-16 flex-shrink-0 bg-primary text-sm text-white ltr:rounded-tr ltr:rounded-br rtl:rounded-tl rtl:rounded-bl"
                                        @click.prevent="applyDiscount">
                                        {{ $t('button.apply') }}
                                    </button>
                                </div>
                                <p v-if="discountErrorMessage" class="db-field-alert mt-1">{{ discountErrorMessage }}</p>
                            </div>

                            <div class="form-col-12 mb-4">
                                <label class="db-field-title">{{ $t('label.shipping_charge') }}</label>
                                <input v-model="shippingForm.shipping_charge" type="text" class="db-field-control"
                                    @keypress="floatNumber($event)" />
                            </div>

                            <ul class="mb-5 space-y-2 border-b border-[#EFF0F6] pb-4">
                                <li class="flex items-center justify-between text-sm">
                                    <span>{{ $t('label.sub_total') }}</span>
                                    <span>{{ currencyFormat(subtotal) }}</span>
                                </li>
                                <li class="flex items-center justify-between text-sm">
                                    <span>{{ $t('label.tax') }}</span>
                                    <span>{{ currencyFormat(totalTax) }}</span>
                                </li>
                                <li class="flex items-center justify-between text-sm">
                                    <span>{{ $t('label.discount') }}</span>
                                    <span class="text-[#FB4E4E]">- {{ currencyFormat(posDiscount) }}</span>
                                </li>
                                <li class="flex items-center justify-between text-sm">
                                    <span>{{ $t('label.shipping_charge') }}</span>
                                    <span>{{ currencyFormat(shippingChargeAmount) }}</span>
                                </li>
                                <li class="flex items-center justify-between border-t border-[#EFF0F6] pt-3 text-base font-semibold">
                                    <span>{{ $t('label.total') }}</span>
                                    <span>{{ currencyFormat(orderGrandTotal) }}</span>
                                </li>
                            </ul>

                            <div class="flex flex-col gap-2 sm:flex-row xl:flex-col">
                                <button type="button" class="db-btn w-full bg-[#FB4E4E] text-white"
                                    :disabled="carts.length === 0" @click.prevent="resetCart">
                                    <i class="lab lab-line-reset"></i>
                                    <span>{{ $t('button.cancel') }}</span>
                                </button>
                                <button type="button" class="db-btn w-full bg-primary text-white"
                                    :disabled="carts.length === 0" @click.prevent="placeOrder">
                                    <i class="lab lab-fill-bag"></i>
                                    <span>{{ $t('button.order') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-if="carts.length > 0"
        class="fixed bottom-0 left-0 right-0 z-40 flex items-center gap-3 border-t bg-white p-3 shadow-lg xl:hidden">
        <div class="min-w-0 flex-1">
            <p class="truncate text-xs text-gray-500">{{ totalProducts() }} {{ $t('label.products') }}</p>
            <p class="text-base font-bold">{{ currencyFormat(orderGrandTotal) }}</p>
        </div>
        <button type="button" class="db-btn flex-shrink-0 bg-primary px-5 text-white" @click.prevent="placeOrder">
            {{ $t('button.order') }}
        </button>
    </div>
</template>

<script>
import LoadingComponent from "../components/LoadingComponent";
import WhatsappOrderProductCatalog from "./WhatsappOrderProductCatalog";
import WhatsappOrderLineItemsTable from "./WhatsappOrderLineItemsTable";
import sourceEnum from "../../../enums/modules/sourceEnum";
import orderTypeEnum from "../../../enums/modules/orderTypeEnum";
import statusEnum from "../../../enums/modules/statusEnum";
import paymentTypeEnum from "../../../enums/modules/paymentTypeEnum";
import roleEnum from "../../../enums/modules/roleEnum";
import appService from "../../../services/appService";
import discountTypeEnum from "../../../enums/modules/discountTypeEnum";
import alertService from "../../../services/alertService";
import ReceiptComponent from "../pos/ReceiptComponent";
import PoscustomerComponent from "../pos/PosCustomerComponent";

export default {
    name: "WhatsappOrderComponent",
    components: {
        ReceiptComponent,
        LoadingComponent,
        PoscustomerComponent,
        WhatsappOrderProductCatalog,
        WhatsappOrderLineItemsTable,
    },
    data() {
        return {
            loading: { isActive: false },
            order: {},
            discount: null,
            checkoutProps: {
                form: {
                    customer_id: null,
                    category: null,
                    brand: null,
                    discount: 0,
                },
            },
            shippingForm: {
                shipping_full_name: "",
                shipping_phone: "",
                shipping_email: "",
                shipping_country_code: "",
                shipping_address: "",
                shipping_city: "",
                shipping_state: "",
                shipping_country: "",
                shipping_zip_code: "",
                shipping_charge: "",
                note: "",
            },
            props: {
                search: {
                    paginate: 0,
                    order_column: "id",
                    order_type: "asc",
                    name: "",
                    product_category_id: "",
                    product_brand_id: "",
                    status: statusEnum.ACTIVE,
                },
            },
            searchProps: {
                paginate: 0,
                order_column: "id",
                order_type: "asc",
                status: statusEnum.ACTIVE,
            },
            discountTypeEnum: discountTypeEnum,
            discountType: discountTypeEnum.PERCENTAGE,
            discountErrorMessage: "",
        };
    },
    computed: {
        setting() {
            return this.$store.getters["frontendSetting/lists"];
        },
        categories() {
            return this.$store.getters["productCategory/depthTrees"];
        },
        brands() {
            return this.$store.getters["productBrand/lists"];
        },
        products() {
            return this.$store.getters["product/lists"];
        },
        customers() {
            return this.$store.getters["user/lists"];
        },
        carts() {
            return this.$store.getters["posCart/lists"];
        },
        subtotal() {
            return this.$store.getters["posCart/subtotal"];
        },
        total() {
            return this.$store.getters["posCart/total"];
        },
        totalTax() {
            return this.$store.getters["posCart/totalTax"];
        },
        posCartProducts() {
            return this.$store.getters["posCart/lists"];
        },
        posDiscount() {
            return this.$store.getters["posCart/discount"];
        },
        shippingChargeAmount() {
            const charge = parseFloat(this.shippingForm.shipping_charge);
            return isNaN(charge) ? 0 : charge;
        },
        orderGrandTotal() {
            return this.total + this.shippingChargeAmount;
        },
    },
    mounted() {
        this.productCategories();
        this.productBrands();
        this.productList();
        this.customerList();
    },
    methods: {
        onlyNumber(e) {
            return appService.onlyNumber(e);
        },
        floatNumber(e) {
            return appService.floatNumber(e);
        },
        currencyFormat(amount) {
            return appService.currencyFormat(
                amount,
                this.setting.site_digit_after_decimal_point,
                this.setting.site_default_currency_symbol,
                this.setting.site_currency_position
            );
        },
        reset() {
            this.props.search.name = "";
            this.checkoutProps.form.category = null;
            this.props.search.product_category_id = "";
            this.checkoutProps.form.brand = null;
            this.props.search.product_brand_id = "";
            this.productList();
        },
        search() {
            this.productList();
        },
        addCustomer() {
            appService.modalShow("#customerModal");
        },
        productCategories() {
            this.loading.isActive = true;
            this.$store.dispatch("productCategory/depthTrees", this.searchProps).finally(() => {
                this.loading.isActive = false;
            });
        },
        productBrands() {
            this.loading.isActive = true;
            this.$store.dispatch("productBrand/lists", this.searchProps).finally(() => {
                this.loading.isActive = false;
            });
        },
        customerList(id = null) {
            this.loading.isActive = true;
            this.$store.dispatch("user/lists", {
                order_column: "id",
                order_type: "asc",
                status: statusEnum.ACTIVE,
                role_id: roleEnum.CUSTOMER,
            }).then((res) => {
                if (res.data.data.length > 0) {
                    this.checkoutProps.form.customer_id = id === null ? res.data.data[0].id : id;
                    this.fillShippingFromCustomer(this.checkoutProps.form.customer_id);
                }
            }).finally(() => {
                this.loading.isActive = false;
            });
        },
        fillShippingFromCustomer(customerId) {
            if (!customerId) return;
            const customer = this.customers.find((c) => c.id === customerId);
            if (!customer) return;
            this.shippingForm.shipping_full_name = customer.name || "";
            this.shippingForm.shipping_phone = customer.phone || "";
            this.shippingForm.shipping_email = customer.email || "";
            this.shippingForm.shipping_country_code = customer.country_code || "";
        },
        productList() {
            this.loading.isActive = true;
            this.$store.dispatch("product/lists", this.props.search).finally(() => {
                this.loading.isActive = false;
            });
        },
        setCategory(id) {
            this.checkoutProps.form.category = id;
            this.props.search.product_category_id = id || "";
            this.productList();
        },
        setBrand(id) {
            this.checkoutProps.form.brand = id;
            this.props.search.product_brand_id = id || "";
            this.productList();
        },
        quantityUp(id, product, e) {
            let quantity = e.target.value;
            if (quantity === 0 || quantity < 0 || quantity === "0") quantity = 1;
            if (quantity > product.stock) quantity = product.stock;
            this.$store.dispatch("posCart/quantity", { id, status: quantity }).then().catch();
            this.resetDiscount();
        },
        quantityIncrement(id, product) {
            let quantity = product.quantity + 1;
            if (quantity > product.stock) quantity = product.stock;
            this.$store.dispatch("posCart/quantity", { id, status: quantity }).then().catch();
            this.resetDiscount();
        },
        quantityDecrement(id, product) {
            let quantity = product.quantity - 1;
            if (quantity <= 0) quantity = 1;
            this.$store.dispatch("posCart/quantity", { id, status: quantity }).then().catch();
            this.resetDiscount();
        },
        removeProduct(id) {
            this.$store.dispatch("posCart/remove", { id }).then().catch();
            this.resetDiscount();
        },
        resetDiscount() {
            this.checkoutProps.form.discount = 0;
            this.$store.dispatch("posCart/discount", this.checkoutProps.form.discount).then().catch();
        },
        applyDiscount() {
            this.discountErrorMessage = "";
            if (this.discountType === discountTypeEnum.FIXED) {
                if (this.subtotal < this.discount) {
                    this.discountErrorMessage = this.$t("message.discount_fixed_error_message");
                    this.resetDiscount();
                } else {
                    this.checkoutProps.form.discount = parseFloat(+this.discount).toFixed(this.setting.site_digit_after_decimal_point);
                    this.$store.dispatch("posCart/discount", this.checkoutProps.form.discount).then().catch();
                }
            } else if (this.discount > 100) {
                this.discountErrorMessage = this.$t("message.discount_error_message");
                this.resetDiscount();
            } else {
                this.checkoutProps.form.discount = parseFloat((this.subtotal * this.discount) / 100).toFixed(this.setting.site_digit_after_decimal_point);
                this.$store.dispatch("posCart/discount", this.checkoutProps.form.discount).then().catch();
            }
        },
        resetCart() {
            this.$store.dispatch("posCart/resetCart").then().catch();
            this.discount = null;
            this.discountErrorMessage = "";
        },
        validateShipping() {
            if (!this.checkoutProps.form.customer_id) {
                alertService.error(this.$t("label.select_customer"));
                return false;
            }
            if (!this.shippingForm.shipping_full_name?.trim()) {
                alertService.error(this.$t("label.full_name"));
                return false;
            }
            if (!this.shippingForm.shipping_phone?.trim()) {
                alertService.error(this.$t("label.phone"));
                return false;
            }
            if (!this.shippingForm.shipping_address?.trim()) {
                alertService.error(this.$t("label.address"));
                return false;
            }
            if (this.carts.length === 0) {
                alertService.error(this.$t("label.products"));
                return false;
            }
            return true;
        },
        placeOrder() {
            if (!this.validateShipping()) return;
            appService.submitConfirmation().then(() => {
                this.orderSubmit();
            }).catch(() => {});
        },
        orderSubmit() {
            this.loading.isActive = true;
            const form = {
                customer_id: this.checkoutProps.form.customer_id,
                subtotal: this.subtotal,
                discount: parseFloat(this.posDiscount),
                tax: this.totalTax,
                shipping_charge: this.shippingChargeAmount,
                total: this.orderGrandTotal,
                order_type: orderTypeEnum.DELIVERY,
                source: sourceEnum.WHATSAPP,
                payment_method: paymentTypeEnum.CASH_ON_DELIVERY,
                products: JSON.stringify(this.posCartProducts),
                note: this.shippingForm.note,
                shipping_full_name: this.shippingForm.shipping_full_name,
                shipping_phone: this.shippingForm.shipping_phone,
                shipping_email: this.shippingForm.shipping_email,
                shipping_country_code: this.shippingForm.shipping_country_code,
                shipping_address: this.shippingForm.shipping_address,
                shipping_city: this.shippingForm.shipping_city,
                shipping_state: this.shippingForm.shipping_state,
                shipping_country: this.shippingForm.shipping_country,
                shipping_zip_code: this.shippingForm.shipping_zip_code,
            };
            this.$store.dispatch("whatsappOrder/save", form).then((orderResponse) => {
                this.$store.dispatch("posCart/resetCart").then(() => {
                    this.discount = null;
                    this.discountErrorMessage = "";
                    this.shippingForm.note = "";
                    this.shippingForm.shipping_charge = "";
                }).catch();
                alertService.success(this.$t("message.whatsapp_order_created"));
                this.$store.dispatch("whatsappOrder/show", orderResponse.data.data.id).then((res) => {
                    this.order = res.data.data;
                    this.loading.isActive = false;
                    appService.modalShow("#posReceiptModal");
                }).catch((error) => {
                    this.loading.isActive = false;
                    alertService.error(error.response?.data?.message);
                });
            }).catch((err) => {
                this.loading.isActive = false;
                if (err.response?.data?.errors) {
                    _.forEach(err.response.data.errors, (error) => {
                        alertService.error(error[0]);
                    });
                } else if (err.response?.data?.message) {
                    alertService.error(err.response.data.message);
                }
            });
        },
        totalProducts() {
            if (this.carts.length > 0) {
                return this.carts.reduce((sum, cart) => sum + cart.quantity, 0);
            }
            return 0;
        },
        onCustomverCreate(customerId) {
            appService.modalHide();
            this.customerList(customerId);
        },
    },
};
</script>
