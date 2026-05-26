<template>
    <LoadingComponent :props="loading" />

    <div class="db-card db-tab-div active">
        <div class="db-card-header">
            <h3 class="db-card-title">{{ $t("menu.postex_cod") }}</h3>
        </div>
        <div class="db-card-body">
            <form @submit.prevent="save">
                <div class="form-row">
                    <div class="form-col-12">
                        <label class="db-field-title required">{{ $t("label.status") }}</label>
                        <div class="db-field-radio-group">
                            <div class="db-field-radio">
                                <div class="custom-radio">
                                    <input type="radio" v-model="form.postex_status" id="postex_enable"
                                        :value="enums.activityEnum.ENABLE" class="custom-radio-field">
                                    <span class="custom-radio-span"></span>
                                </div>
                                <label for="postex_enable" class="db-field-label">{{ $t('label.enable') }}</label>
                            </div>
                            <div class="db-field-radio">
                                <div class="custom-radio">
                                    <input type="radio" v-model="form.postex_status" id="postex_disable"
                                        :value="enums.activityEnum.DISABLE" class="custom-radio-field">
                                    <span class="custom-radio-span"></span>
                                </div>
                                <label for="postex_disable" class="db-field-label">{{ $t('label.disable') }}</label>
                            </div>
                        </div>
                    </div>

                    <template v-if="form.postex_status === enums.activityEnum.ENABLE">
                        <div class="form-col-12 sm:form-col-6">
                            <label for="postex_api_token" class="db-field-title required">
                                {{ $t("label.postex_api_token") }}
                            </label>
                            <input v-model="form.postex_api_token" type="password" id="postex_api_token"
                                :class="errors.postex_api_token ? 'invalid' : ''" class="db-field-control"
                                autocomplete="off" />
                            <small class="db-field-alert" v-if="errors.postex_api_token">
                                {{ errors.postex_api_token[0] }}
                            </small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label for="postex_base_url" class="db-field-title">
                                {{ $t("label.postex_base_url") }}
                            </label>
                            <input v-model="form.postex_base_url" type="url" id="postex_base_url"
                                class="db-field-control" />
                            <small class="db-field-alert" v-if="errors.postex_base_url">
                                {{ errors.postex_base_url[0] }}
                            </small>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label for="postex_pickup_address_code" class="db-field-title">
                                {{ $t("label.postex_pickup_address_code") }}
                            </label>
                            <input v-model="form.postex_pickup_address_code" type="text" id="postex_pickup_address_code"
                                class="db-field-control" />
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label for="postex_default_order_type" class="db-field-title">
                                {{ $t("label.postex_order_type") }}
                            </label>
                            <select v-model="form.postex_default_order_type" id="postex_default_order_type"
                                class="db-field-control">
                                <option value="Normal">Normal</option>
                                <option value="Reverse">Reverse</option>
                                <option value="Replacement">Replacement</option>
                            </select>
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label for="postex_invoice_division" class="db-field-title">
                                {{ $t("label.postex_invoice_division") }}
                            </label>
                            <input v-model.number="form.postex_invoice_division" type="number" min="1" max="99"
                                id="postex_invoice_division" class="db-field-control" />
                        </div>

                        <div class="form-col-12 sm:form-col-6">
                            <label for="postex_booking_weight" class="db-field-title">
                                {{ $t("label.postex_booking_weight") }}
                            </label>
                            <input v-model="form.postex_booking_weight" type="number" step="0.01" min="0"
                                id="postex_booking_weight" class="db-field-control" />
                        </div>

                        <div class="form-col-12">
                            <label class="db-field-title">{{ $t("label.postex_auto_ship") }}</label>
                            <div class="db-field-radio-group">
                                <div class="db-field-radio">
                                    <div class="custom-radio">
                                        <input type="radio" v-model="form.postex_auto_ship" id="postex_auto_yes"
                                            :value="enums.activityEnum.ENABLE" class="custom-radio-field">
                                        <span class="custom-radio-span"></span>
                                    </div>
                                    <label for="postex_auto_yes" class="db-field-label">{{ $t('label.enable') }}</label>
                                </div>
                                <div class="db-field-radio">
                                    <div class="custom-radio">
                                        <input type="radio" v-model="form.postex_auto_ship" id="postex_auto_no"
                                            :value="enums.activityEnum.DISABLE" class="custom-radio-field">
                                        <span class="custom-radio-span"></span>
                                    </div>
                                    <label for="postex_auto_no" class="db-field-label">{{ $t('label.disable') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-col-12 flex flex-wrap gap-3">
                            <button type="button" @click="testConnection" :disabled="connectionLoading"
                                class="db-btn py-2 text-white bg-gray-700">
                                <i class="lab lab-line-link"></i>
                                <span>{{ $t("button.test_connection") }}</span>
                            </button>
                            <button type="button" @click="loadPickupAddresses" :disabled="pickupLoading"
                                class="db-btn py-2 text-white bg-gray-600">
                                <span>{{ $t("button.load_pickup_addresses") }}</span>
                            </button>
                        </div>

                        <div class="form-col-12" v-if="pickupAddresses.length">
                            <label class="db-field-title">{{ $t("label.postex_pickup_addresses") }}</label>
                            <div class="overflow-x-auto">
                                <table class="db-table stripe">
                                    <thead class="db-table-head">
                                        <tr class="db-table-head-tr">
                                            <th class="db-table-head-th">{{ $t("label.code") }}</th>
                                            <th class="db-table-head-th">{{ $t("label.city") }}</th>
                                            <th class="db-table-head-th">{{ $t("label.address") }}</th>
                                            <th class="db-table-head-th">{{ $t("label.contact") }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="db-table-body">
                                        <tr v-for="(row, idx) in pickupAddresses" :key="idx" class="db-table-body-tr">
                                            <td class="db-table-body-td">
                                                <button type="button" class="text-primary underline"
                                                    @click="form.postex_pickup_address_code = row.addressCode">
                                                    {{ row.addressCode }}
                                                </button>
                                            </td>
                                            <td class="db-table-body-td">{{ row.cityName }}</td>
                                            <td class="db-table-body-td">{{ row.address }}</td>
                                            <td class="db-table-body-td">{{ row.contactPersonName }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>

                    <div class="form-col-12">
                        <button type="submit" class="db-btn text-white bg-primary">
                            <i class="lab lab-fill-save"></i>
                            <span>{{ $t("button.save") }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import LoadingComponent from "../../components/LoadingComponent";
import alertService from "../../../../services/alertService";
import activityEnum from "../../../../enums/modules/activityEnum";

export default {
    name: "PostExComponent",
    components: { LoadingComponent },
    data() {
        return {
            loading: { isActive: false },
            connectionLoading: false,
            pickupLoading: false,
            pickupAddresses: [],
            form: {
                postex_status: null,
                postex_api_token: "",
                postex_base_url: "",
                postex_pickup_address_code: "",
                postex_default_order_type: "Normal",
                postex_invoice_division: 1,
                postex_booking_weight: "",
                postex_auto_ship: null,
            },
            enums: { activityEnum },
            errors: {},
        };
    },
    mounted() {
        this.loadSettings();
    },
    methods: {
        loadSettings() {
            this.loading.isActive = true;
            this.$store.dispatch("postex/lists")
                .then((res) => {
                    const data = res.data.data;
                    this.form.postex_status = Number(data.postex_status);
                    this.form.postex_api_token = data.postex_api_token || "";
                    this.form.postex_base_url = data.postex_base_url || "";
                    this.form.postex_pickup_address_code = data.postex_pickup_address_code || "";
                    this.form.postex_default_order_type = data.postex_default_order_type || "Normal";
                    this.form.postex_invoice_division = Number(data.postex_invoice_division || 1);
                    this.form.postex_booking_weight = data.postex_booking_weight || "";
                    this.form.postex_auto_ship = Number(data.postex_auto_ship ?? activityEnum.DISABLE);
                })
                .catch((err) => alertService.error(err))
                .finally(() => {
                    this.loading.isActive = false;
                });
        },
        save() {
            this.loading.isActive = true;
            this.$store.dispatch("postex/save", this.form)
                .then((res) => {
                    alertService.successFlip(res.config.method === "put" ?? 0, this.$t("menu.postex_cod"));
                    this.errors = {};
                })
                .catch((err) => {
                    this.errors = err.response?.data?.errors || {};
                })
                .finally(() => {
                    this.loading.isActive = false;
                });
        },
        testConnection() {
            this.connectionLoading = true;
            this.$store.dispatch("postex/save", this.form)
                .then(() => this.$store.dispatch("postex/testConnection"))
                .then((res) => {
                    alertService.success(res.data.message || "Connected");
                })
                .catch((err) => {
                    alertService.error(err.response?.data?.message || err);
                })
                .finally(() => {
                    this.connectionLoading = false;
                });
        },
        loadPickupAddresses() {
            this.pickupLoading = true;
            this.$store.dispatch("postex/save", this.form)
                .then(() => this.$store.dispatch("postex/merchantAddresses"))
                .then((res) => {
                    this.pickupAddresses = res.data.data || [];
                    if (!this.pickupAddresses.length) {
                        alertService.info(this.$t("message.no_pickup_addresses"));
                    }
                })
                .catch((err) => alertService.error(err.response?.data?.message || err))
                .finally(() => {
                    this.pickupLoading = false;
                });
        },
    },
};
</script>
