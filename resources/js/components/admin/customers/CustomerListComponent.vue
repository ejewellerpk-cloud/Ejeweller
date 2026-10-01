<template>
    <LoadingComponent :props="loading" />
    <div class="col-12">
        <div class="db-card" v-if="listView === 'users'">
            <div class="db-card-header border-none">
                <h3 class="db-card-title">{{ $t("menu.customers") }}</h3>
                <div class="db-card-filter">
                    <TableLimitComponent :method="list" :search="props.search" :page="paginationPage" />
                    <ListFilterPanel as-dropdown v-slot="{ close }">
                        <form class="w-full" @submit.prevent="applySearch(close)">
                            <label for="searchName" class="db-field-title after:hidden">{{ $t("label.name") }}</label>
                            <input id="searchName" v-model="props.search.name" type="text" class="db-field-control" />

                            <label for="searchEmail" class="db-field-title after:hidden">{{ $t("label.email") }}</label>
                            <input id="searchEmail" v-model="props.search.email" type="text" class="db-field-control" />

                            <label for="searchPhone" class="db-field-title after:hidden">{{ $t("label.phone") }}</label>
                            <input id="searchPhone" v-model="props.search.phone" v-on:keypress="phoneNumber($event)"
                                type="text" class="db-field-control" />

                            <label for="searchStatus" class="db-field-title after:hidden">{{ $t("label.status") }}</label>
                            <select id="searchStatus" v-model="props.search.status" class="db-field-control">
                                <option :value="null">--</option>
                                <option :value="enums.statusEnum.ACTIVE">{{ $t('label.active') }}</option>
                                <option :value="enums.statusEnum.INACTIVE">{{ $t('label.inactive') }}</option>
                            </select>

                            <div class="db-card-filter-form-actions">
                                <button type="submit" class="db-btn py-2 text-white bg-primary">
                                    <i class="lab lab-line-search lab-font-size-16"></i>
                                    <span>{{ $t("button.search") }}</span>
                                </button>
                                <button type="button" class="db-btn py-2 text-white bg-gray-600" @click="applyClear(close)">
                                    <i class="lab lab-line-cross lab-font-size-22"></i>
                                    <span>{{ $t("button.clear") }}</span>
                                </button>
                            </div>
                        </form>
                    </ListFilterPanel>
                    <div class="dropdown-group">
                        <ExportComponent />
                        <div class="dropdown-list db-card-filter-dropdown-list">
                            <PrintComponent :props="printObj" />
                            <ExcelComponent :method="xls" />
                        </div>
                    </div>
                    <div class="dropdown-group" v-if="permissionChecker('customers_show')">
                        <button type="button" class="db-card-filter-btn dropdown-btn">
                            <i class="lab lab-monitor-mobbile lab-font-size-16"></i>
                            <span>{{ $t("button.history") }}</span>
                        </button>
                        <div class="dropdown-list db-card-filter-dropdown-list">
                            <button type="button" class="db-card-filter-dropdown-menu w-full" @click="listView = 'sessions'">
                                <i class="lab lab-monitor-mobbile"></i>
                                <span>{{ $t("label.device_session_history") }}</span>
                            </button>
                            <button type="button" class="db-card-filter-dropdown-menu w-full" @click="listView = 'fcm_tokens'">
                                <i class="lab lab-line-notification"></i>
                                <span>{{ $t("label.push_device_history") }}</span>
                            </button>
                        </div>
                    </div>
                    <CustomerCreateComponent :props="props" v-if="permissionChecker('customers_create')" />
                </div>
            </div>

            <div class="db-table-responsive">
                <table class="db-table stripe" id="print">
                    <thead class="db-table-head">
                        <tr class="db-table-head-tr">
                            <th class="db-table-head-th">{{ $t("label.name") }}</th>
                            <th class="db-table-head-th">{{ $t("label.email") }}</th>
                            <th class="db-table-head-th">{{ $t("label.phone") }}</th>
                            <th class="db-table-head-th">{{ $t("label.status") }}</th>
                            <th class="db-table-head-th hidden-print"
                                v-if="permissionChecker('customers_show') || permissionChecker('customers_edit') || permissionChecker('customers_delete')">
                                {{ $t("label.action") }}</th>
                        </tr>
                    </thead>
                    <tbody class="db-table-body" v-if="customers.length > 0">
                        <tr class="db-table-body-tr" v-for="customer in customers" :key="customer">
                            <td class="db-table-body-td">
                                {{ textShortener(customer.name, 20) }}
                            </td>
                            <td class="db-table-body-td">
                                {{ customer.email }}
                            </td>
                            <td class="db-table-body-td">
                                <span dir="ltr">{{ customer.phone ? customer.country_code + '' + customer.phone :
                                    ''}}</span>
                            </td>
                            <td class="db-table-body-td">
                                <span :class="statusClass(customer.status)">
                                    {{ enums.statusEnumArray[customer.status] }}
                                </span>
                            </td>
                            <td class="db-table-body-td hidden-print"
                                v-if="permissionChecker('customers_show') || permissionChecker('customers_edit') || permissionChecker('customers_delete')">
                                <div class="flex justify-start items-center sm:items-start sm:justify-start gap-1.5">
                                    <SmIconViewComponent :link="'admin.customers.show'" :id="customer.id"
                                        v-if="permissionChecker('customers_show')" />
                                    <SmIconSidebarModalEditComponent @click="edit(customer)"
                                        v-if="permissionChecker('customers_edit')" />
                                    <SmIconDeleteComponent @click="destroy(customer.id)"
                                        v-if="customer.id !== 2 && permissionChecker('customers_delete')" />

                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody class="db-table-body" v-else>
                        <tr class="db-table-body-tr">
                            <td class="db-table-body-td text-center" colspan="5">
                                <div class="p-4">
                                    <div class="max-w-[300px] mx-auto mt-2">
                                        <img class="w-full h-full" :src="ENV.API_URL+'/images/default/not-found/not_found.png'" alt="Not Found">
                                    </div>
                                    <span class="d-block mt-3 text-lg">{{ $t('message.no_data_found') }}</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-6" v-if="customers.length > 0">
                <PaginationSMBox :pagination="pagination" :method="list" />
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <PaginationTextComponent :props="{ page: paginationPage }" />
                    <PaginationBox :pagination="pagination" :method="list" />
                </div>
            </div>
        </div>

        <AllUserSessionsComponent
            v-if="listView === 'sessions' && permissionChecker('customers_show')"
            :key="'customer-sessions'"
            api-prefix="customer"
            show-route="admin.customers.show"
            @back="listView = 'users'"
        />

        <AllUserFcmTokensComponent
            v-if="listView === 'fcm_tokens' && permissionChecker('customers_show')"
            :key="'customer-fcm-tokens'"
            api-prefix="customer"
            show-route="admin.customers.show"
            @back="listView = 'users'"
        />
    </div>
</template>
<script>
import LoadingComponent from "../components/LoadingComponent";
import CustomerCreateComponent from "./CustomerCreateComponent";
import alertService from "../../../services/alertService";
import PaginationTextComponent from "../components/pagination/PaginationTextComponent";
import PaginationBox from "../components/pagination/PaginationBox";
import PaginationSMBox from "../components/pagination/PaginationSMBox";
import appService from "../../../services/appService";
import statusEnum from "../../../enums/modules/statusEnum";
import TableLimitComponent from "../components/TableLimitComponent";
import SmIconViewComponent from "../components/buttons/SmIconViewComponent";
import SmIconSidebarModalEditComponent from "../components/buttons/SmIconSidebarModalEditComponent";
import SmIconDeleteComponent from "../components/buttons/SmIconDeleteComponent";
import print from "vue3-print-nb";
import ListFilterPanel from "../components/ListFilterPanel";
import ExportComponent from "../components/buttons/export/ExportComponent";
import PrintComponent from "../components/buttons/export/PrintComponent";
import ExcelComponent from "../components/buttons/export/ExcelComponent";
import AllUserSessionsComponent from "../components/AllUserSessionsComponent";
import AllUserFcmTokensComponent from "../components/AllUserFcmTokensComponent";
import ENV from "../../../config/env";

export default {
    name: "CustomerListComponent",
    components: {
        TableLimitComponent,
        PaginationSMBox,
        PaginationBox,
        PaginationTextComponent,
        CustomerCreateComponent,
        LoadingComponent,
        SmIconViewComponent,
        SmIconSidebarModalEditComponent,
        SmIconDeleteComponent,
        ListFilterPanel,
        ExportComponent,
        PrintComponent,
        ExcelComponent,
        AllUserSessionsComponent,
        AllUserFcmTokensComponent,
    },
    data() {
        return {
            loading: {
                isActive: false,
            },
            listView: "users",
            enums: {
                statusEnum: statusEnum,
                statusEnumArray: {
                    [statusEnum.ACTIVE]: this.$t("label.active"),
                    [statusEnum.INACTIVE]: this.$t("label.inactive"),
                },
            },
            printLoading: true,
            printObj: {
                id: "print",
                popTitle: this.$t("menu.customers"),
            },
            props: {
                form: {
                    name: "",
                    email: "",
                    phone: "",
                    password: "",
                    password_confirmation: "",
                    country_code: "",
                    status: statusEnum.ACTIVE,
                },
                search: {
                    paginate: 1,
                    page: 1,
                    per_page: 10,
                    order_column: "id",
                    order_type: "desc",
                    name: "",
                    email: "",
                    phone: "",
                    status: null,
                },
                flag: ""
            },
            ENV:ENV,
        };
    },
    mounted() {
        this.list();
    },
    computed: {
        customers: function () {
            return this.$store.getters["customer/lists"];
        },
        pagination: function () {
            return this.$store.getters["customer/pagination"];
        },
        paginationPage: function () {
            return this.$store.getters["customer/page"];
        },
        countryCode: function () {
            return this.$store.getters['countryCode/show'];
        }
    },
    methods: {
        permissionChecker(e) {
            return appService.permissionChecker(e);
        },
        statusClass: function (status) {
            return appService.statusClass(status);
        },
        phoneNumber(e) {
            return appService.phoneNumber(e);
        },
        textShortener: function (text, number = 30) {
            return appService.textShortener(text, number);
        },
        search: function () {
            this.list();
        },
        applySearch: function (close) {
            this.search();
            if (typeof close === "function") {
                close();
            }
        },
        clear: function () {
            this.props.search.paginate = 1;
            this.props.search.page = 1;
            this.props.search.name = "";
            this.props.search.email = "";
            this.props.search.phone = "";
            this.props.search.status = null;
            this.list();
        },
        applyClear: function (close) {
            this.clear();
            if (typeof close === "function") {
                close();
            }
        },
        list: function (page = 1) {
            this.loading.isActive = true;
            this.props.search.page = page;
            this.$store
                .dispatch("customer/lists", this.props.search)
                .then((res) => {
                    this.loading.isActive = false;
                })
                .catch((err) => {
                    this.loading.isActive = false;
                });
        },
        edit: function (customer) {
            appService.sideDrawerShow();
            this.loading.isActive = true;
            this.$store
                .dispatch("customer/edit", customer.id)
                .then((res) => {
                    this.loading.isActive = false;
                    this.props.errors = {};
                    this.props.form = {
                        name: customer.name,
                        email: customer.email,
                        phone: customer.phone,
                        password: customer.password,
                        status: customer.status,
                        country_code: customer.country_code,
                    };
                    this.$store.dispatch('countryCode/callingCode', customer.country_code).then(res => {
                        this.props.flag = res.data.data.flag_emoji;
                        this.loading.isActive = false;
                    }).catch((err) => {
                        this.loading.isActive = false;
                    });
                })
                .catch((err) => {
                    alertService.error(err.response.data.message);
                });
        },
        destroy: function (id) {
            appService
                .destroyConfirmation()
                .then((res) => {
                    try {
                        this.loading.isActive = true;
                        this.$store
                            .dispatch("customer/destroy", {
                                id: id,
                                search: this.props.search,
                            })
                            .then((res) => {
                                this.loading.isActive = false;
                                alertService.successFlip(null, this.$t("menu.customers"));
                            })
                            .catch((err) => {
                                this.loading.isActive = false;
                                alertService.error(err.response.data.message);
                            });
                    } catch (err) {
                        this.loading.isActive = false;
                        alertService.error(err.response.data.message);
                    }
                })
                .catch((err) => {
                    this.loading.isActive = false;
                });
        },
        xls: function () {
            this.loading.isActive = true;
            this.$store
                .dispatch("customer/export", this.props.search)
                .then((res) => {
                    this.loading.isActive = false;
                    const blob = new Blob([res.data], {
                        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                    });
                    const link = document.createElement("a");
                    link.href = URL.createObjectURL(blob);
                    link.download = this.$t("menu.customers");
                    link.click();
                    URL.revokeObjectURL(link.href);
                })
                .catch((err) => {
                    this.loading.isActive = false;
                    alertService.error(err.response.data.message);
                });
        },
    },
};
</script>
<style scoped>
@media print {
    .hidden-print {
        display: none !important;
    }
}
</style>