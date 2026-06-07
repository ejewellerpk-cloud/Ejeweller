<template>
    <div class="db-card">
        <div class="db-card-header border-none flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" class="db-btn-outline h-[34px] text-xs" @click="$emit('back')">
                    <i class="lab lab-line-arrow-left"></i>
                    <span>{{ $t("button.back_to_users") }}</span>
                </button>
                <div>
                    <h3 class="db-card-title">{{ $t("label.push_device_history") }}</h3>
                    <p class="text-xs text-[#6E7191] mt-1">
                        {{ $t("label.total_devices") }}: <span class="font-semibold text-heading">{{ allTotalDevices }}</span>
                    </p>
                </div>
            </div>
            <div class="db-card-filter">
                <TableLimitComponent :method="list" :search="props.search" :page="paginationPage" />
            </div>
        </div>

        <div class="db-card-body !pt-0">
            <div v-if="loadError" class="py-8 text-center">
                <span class="text-sm text-[#FB4E4E]">{{ loadError }}</span>
            </div>

            <div v-else class="db-table-responsive">
                <table class="db-table stripe">
                    <thead class="db-table-head">
                        <tr class="db-table-head-tr">
                            <th class="db-table-head-th">{{ $t("label.user") }}</th>
                            <th class="db-table-head-th">{{ $t("label.email") }}</th>
                            <th class="db-table-head-th">{{ $t("label.device") }}</th>
                            <th class="db-table-head-th">{{ $t("label.platform") }}</th>
                            <th class="db-table-head-th">{{ $t("label.token_preview") }}</th>
                            <th class="db-table-head-th">{{ $t("label.ip_address") }}</th>
                            <th class="db-table-head-th">{{ $t("label.last_active") }}</th>
                            <th class="db-table-head-th">{{ $t("label.action") }}</th>
                        </tr>
                        <tr class="db-table-filter-tr">
                            <th class="db-table-head-th">
                                <input v-model="props.search.name" type="text" class="db-table-filter-control" :placeholder="$t('label.name')" @keyup.enter="search">
                            </th>
                            <th class="db-table-head-th">
                                <input v-model="props.search.email" type="text" class="db-table-filter-control" :placeholder="$t('label.email')" @keyup.enter="search">
                            </th>
                            <th class="db-table-head-th">
                                <input v-model="props.search.device_name" type="text" class="db-table-filter-control" :placeholder="$t('label.device')" @keyup.enter="search">
                            </th>
                            <th class="db-table-head-th">
                                <input v-model="props.search.platform" type="text" class="db-table-filter-control" :placeholder="$t('label.platform')" @keyup.enter="search">
                            </th>
                            <th class="db-table-head-th"></th>
                            <th class="db-table-head-th">
                                <input v-model="props.search.ip_address" type="text" class="db-table-filter-control" :placeholder="$t('label.ip_address')" @keyup.enter="search">
                            </th>
                            <th class="db-table-head-th"></th>
                            <th class="db-table-head-th">
                                <button type="button" class="db-table-filter-btn bg-primary text-white" @click="search"><i class="lab lab-line-search"></i></button>
                                <button type="button" class="db-table-filter-btn bg-gray-600 text-white ml-1" @click="clear"><i class="lab lab-line-cross"></i></button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="db-table-body" v-if="tokens.length > 0">
                        <tr class="db-table-body-tr" v-for="token in tokens" :key="token.id">
                            <td class="db-table-body-td">
                                <router-link :to="{ name: showRoute, params: { id: token.user_id } }" class="text-primary font-medium">
                                    {{ textShortener(token.user_name, 24) }}
                                </router-link>
                            </td>
                            <td class="db-table-body-td">{{ token.user_email }}</td>
                            <td class="db-table-body-td capitalize">{{ token.device_name }}</td>
                            <td class="db-table-body-td capitalize">{{ token.platform }}</td>
                            <td class="db-table-body-td font-mono text-xs" dir="ltr">{{ token.token_preview }}</td>
                            <td class="db-table-body-td" dir="ltr">{{ token.ip_address || "—" }}</td>
                            <td class="db-table-body-td">{{ formatDate(token.last_used_at) }}</td>
                            <td class="db-table-body-td">
                                <button type="button" @click="revokeDevice(token)" class="db-btn-outline h-[30px] text-xs !text-[#FB4E4E] !border-[#FB4E4E]">
                                    <i class="lab lab-line-cross"></i>
                                    <span>{{ $t("button.revoke_push_device") }}</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tbody class="db-table-body" v-else>
                        <tr class="db-table-body-tr">
                            <td class="db-table-body-td text-center" colspan="8">{{ $t("message.no_data_found") }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-6" v-if="tokens.length > 0">
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
    name: "AllUserFcmTokensComponent",
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
    },
    data() {
        return {
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
                    platform: "",
                    ip_address: "",
                },
            },
        };
    },
    computed: {
        tokens: function () {
            return this.$store.getters["userFcmToken/allLists"];
        },
        allTotalDevices: function () {
            return this.$store.getters["userFcmToken/allTotalDevices"];
        },
        pagination: function () {
            return this.$store.getters["userFcmToken/allPagination"];
        },
        paginationPage: function () {
            return this.$store.getters["userFcmToken/allPage"];
        },
    },
    mounted() {
        this.list();
    },
    methods: {
        list: function (page = 1) {
            this.loading.isActive = true;
            this.loadError = null;
            this.props.search.page = page;
            this.$store.dispatch("userFcmToken/allLists", {
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
            this.props.search.name = "";
            this.props.search.email = "";
            this.props.search.phone = "";
            this.props.search.device_name = "";
            this.props.search.platform = "";
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
        revokeDevice: function (token) {
            appService.destroyConfirmation().then(() => {
                this.loading.isActive = true;
                this.$store.dispatch("userFcmToken/destroy", {
                    mode: "admin",
                    apiPrefix: this.apiPrefix,
                    userId: token.user_id,
                    tokenId: token.id,
                }).then((res) => {
                    alertService.success(res.data.message || this.$t("message.push_device_removed"));
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

<style scoped>
.db-table-filter-tr .db-table-head-th {
    padding-top: 0;
    padding-bottom: 0.75rem;
    vertical-align: top;
}

.db-table-filter-control {
    width: 100%;
    min-width: 72px;
    height: 32px;
    padding: 0 0.625rem;
    border: 1px solid rgb(229 231 235);
    border-radius: 0.375rem;
    font-size: 0.75rem;
}

.db-table-filter-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 0.375rem;
}
</style>
