<template>
    <div class="db-card">
        <div class="db-card-header border-none flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" class="db-btn-outline h-[34px] text-xs" @click="$emit('back')">
                    <i class="lab lab-line-arrow-left"></i>
                    <span>{{ $t("button.back_to_users") }}</span>
                </button>
                <div>
                    <h3 class="db-card-title">{{ $t("label.device_session_history") }}</h3>
                    <p class="text-xs text-[#6E7191] mt-1">
                        {{ $t("label.total_devices") }}: <span class="font-semibold text-heading">{{ allTotalDevices }}</span>
                    </p>
                </div>
            </div>
            <div class="db-card-filter">
                <TableLimitComponent :method="list" :search="props.search" :page="paginationPage" />
                <button type="button" class="db-card-filter-btn table-filter-btn" @click.prevent="toggleFilterPanel">
                    <i class="lab lab-line-filter lab-font-size-14"></i>
                    <span>{{ $t("button.filter") }}</span>
                </button>
            </div>
        </div>

        <div class="table-filter-div" :class="{ 'is-open': isFilterOpen }" :id="filterId">
            <form class="p-4 sm:p-5 mb-5 w-full d-block" @submit.prevent="search">
                <div class="row">
                    <div class="col-12 sm:col-6 md:col-4 xl:col-3">
                        <label class="db-field-title after:hidden">{{ $t("label.name") }}</label>
                        <input v-model="props.search.name" type="text" class="db-field-control">
                    </div>
                    <div class="col-12 sm:col-6 md:col-4 xl:col-3">
                        <label class="db-field-title after:hidden">{{ $t("label.email") }}</label>
                        <input v-model="props.search.email" type="text" class="db-field-control">
                    </div>
                    <div class="col-12 sm:col-6 md:col-4 xl:col-3">
                        <label class="db-field-title after:hidden">{{ $t("label.phone") }}</label>
                        <input v-model="props.search.phone" v-on:keypress="phoneNumber($event)" type="text" class="db-field-control">
                    </div>
                    <div class="col-12 sm:col-6 md:col-4 xl:col-3">
                        <label class="db-field-title after:hidden">{{ $t("label.device") }}</label>
                        <input v-model="props.search.device_name" type="text" class="db-field-control">
                    </div>
                    <div class="col-12 sm:col-6 md:col-4 xl:col-3">
                        <label class="db-field-title after:hidden">{{ $t("label.browser") }}</label>
                        <input v-model="props.search.browser" type="text" class="db-field-control">
                    </div>
                    <div class="col-12 sm:col-6 md:col-4 xl:col-3">
                        <label class="db-field-title after:hidden">{{ $t("label.ip_address") }}</label>
                        <input v-model="props.search.ip_address" type="text" class="db-field-control" dir="ltr">
                    </div>
                    <div class="col-12">
                        <div class="flex flex-wrap gap-3 mt-4">
                            <button type="submit" class="db-btn py-2 text-white bg-primary">
                                <i class="lab lab-line-search lab-font-size-16"></i>
                                <span>{{ $t("button.search") }}</span>
                            </button>
                            <button type="button" class="db-btn py-2 text-white bg-gray-600" @click="clear">
                                <i class="lab lab-line-cross lab-font-size-22"></i>
                                <span>{{ $t("button.clear") }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="db-card-body !pt-0">
            <div v-if="loading.isActive" class="py-8 text-center text-sm text-[#6E7191]">
                {{ $t("label.loading") || "Loading..." }}
            </div>

            <div v-else-if="loadError" class="py-8 text-center">
                <span class="text-sm text-[#FB4E4E]">{{ loadError }}</span>
            </div>

            <div v-else class="db-table-responsive">
                <table class="db-table stripe">
                    <thead class="db-table-head">
                        <tr class="db-table-head-tr">
                            <th class="db-table-head-th">{{ $t("label.user") }}</th>
                            <th class="db-table-head-th">{{ $t("label.email") }}</th>
                            <th class="db-table-head-th">{{ $t("label.device") }}</th>
                            <th class="db-table-head-th">{{ $t("label.browser") }}</th>
                            <th class="db-table-head-th">{{ $t("label.ip_address") }}</th>
                            <th class="db-table-head-th">{{ $t("label.last_active") }}</th>
                            <th class="db-table-head-th">{{ $t("label.logged_in_at") }}</th>
                            <th class="db-table-head-th">{{ $t("label.action") }}</th>
                        </tr>
                    </thead>
                    <tbody class="db-table-body" v-if="sessions.length > 0">
                        <tr v-for="session in sessions" :key="session.id" class="db-table-body-tr">
                            <td class="db-table-body-td">
                                <router-link
                                    v-if="session.user"
                                    :to="{ name: showRoute, params: { id: session.user.id } }"
                                    class="text-primary font-medium hover:underline"
                                >
                                    {{ textShortener(session.user.name, 24) }}
                                </router-link>
                                <span v-else>—</span>
                            </td>
                            <td class="db-table-body-td">{{ session.user?.email || "—" }}</td>
                            <td class="db-table-body-td">
                                <div class="flex items-center gap-2">
                                    <i class="lab lab-line-monitor text-lg text-primary"></i>
                                    <span class="capitalize">{{ session.device_name }}</span>
                                </div>
                            </td>
                            <td class="db-table-body-td">{{ session.browser }}</td>
                            <td class="db-table-body-td" dir="ltr">{{ session.ip_address || "—" }}</td>
                            <td class="db-table-body-td">{{ formatDate(session.last_used_at || session.created_at) }}</td>
                            <td class="db-table-body-td">{{ formatDate(session.created_at) }}</td>
                            <td class="db-table-body-td">
                                <button
                                    v-if="session.user"
                                    type="button"
                                    @click="logoutDevice(session)"
                                    class="db-btn-outline h-[30px] text-xs !text-[#FB4E4E] !border-[#FB4E4E]"
                                >
                                    <i class="lab lab-line-logout"></i>
                                    <span>{{ $t("button.logout_device") }}</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tbody class="db-table-body" v-else>
                        <tr class="db-table-body-tr">
                            <td class="db-table-body-td text-center" colspan="8">
                                <span class="text-sm text-[#6E7191]">{{ $t("message.no_data_found") }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-6" v-if="sessions.length > 0">
                <PaginationSMBox :pagination="pagination" :method="list" />
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <PaginationTextComponent :props="{ page: paginationPage }" />
                    <PaginationBox :pagination="pagination" :method="list" />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import alertService from "../../../services/alertService";
import appService from "../../../services/appService";
import TableLimitComponent from "./TableLimitComponent";
import PaginationTextComponent from "./pagination/PaginationTextComponent";
import PaginationBox from "./pagination/PaginationBox";
import PaginationSMBox from "./pagination/PaginationSMBox";

export default {
    name: "AllUserSessionsComponent",
    emits: ["back"],
    components: {
        TableLimitComponent,
        PaginationTextComponent,
        PaginationBox,
        PaginationSMBox,
    },
    props: {
        apiPrefix: {
            type: String,
            required: true,
        },
        showRoute: {
            type: String,
            required: true,
        },
        filterId: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            isFilterOpen: false,
            loading: {
                isActive: false,
            },
            loadError: null,
            props: {
                search: {
                    paginate: 1,
                    page: 1,
                    per_page: 10,
                    order_column: "last_used_at",
                    order_type: "desc",
                    name: "",
                    email: "",
                    phone: "",
                    device_name: "",
                    browser: "",
                    ip_address: "",
                },
            },
        };
    },
    computed: {
        sessions: function () {
            return this.$store.getters["userSession/allLists"];
        },
        allTotalDevices: function () {
            return this.$store.getters["userSession/allTotalDevices"];
        },
        pagination: function () {
            return this.$store.getters["userSession/allPagination"];
        },
        paginationPage: function () {
            return this.$store.getters["userSession/allPage"];
        },
    },
    mounted() {
        this.list();
    },
    methods: {
        toggleFilterPanel: function () {
            this.isFilterOpen = !this.isFilterOpen;
        },
        phoneNumber: function (event) {
            return appService.phoneNumber(event);
        },
        list: function (page = 1) {
            this.loading.isActive = true;
            this.loadError = null;
            this.props.search.page = page;
            this.$store.dispatch("userSession/allLists", {
                apiPrefix: this.apiPrefix,
                search: this.props.search,
            }).then(() => {
                this.loading.isActive = false;
            }).catch((err) => {
                this.loading.isActive = false;
                this.loadError = err.response?.data?.message || this.$t("error.something_wrong");
            });
        },
        search: function () {
            this.list(1);
        },
        clear: function () {
            this.props.search.paginate = 1;
            this.props.search.page = 1;
            this.props.search.name = "";
            this.props.search.email = "";
            this.props.search.phone = "";
            this.props.search.device_name = "";
            this.props.search.browser = "";
            this.props.search.ip_address = "";
            this.list(1);
        },
        textShortener: function (text, length) {
            return appService.textShortener(text, length);
        },
        formatDate: function (value) {
            if (!value) {
                return "—";
            }
            try {
                return new Date(value).toLocaleString();
            } catch (e) {
                return value;
            }
        },
        logoutDevice: function (session) {
            appService.destroyConfirmation().then(() => {
                this.loading.isActive = true;
                this.$store.dispatch("userSession/destroy", {
                    mode: "admin",
                    apiPrefix: this.apiPrefix,
                    userId: session.user.id,
                    tokenId: session.id,
                }).then((res) => {
                    alertService.success(res.data.message || this.$t("message.session_logout_success"));
                    this.list(this.props.search.page);
                }).catch((err) => {
                    this.loading.isActive = false;
                    alertService.error(err.response?.data?.message || this.$t("error.something_wrong"));
                });
            }).catch(() => {});
        },
    },
};
</script>
