<template>
    <div class="db-card">
        <div class="db-card-header flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="db-card-title">{{ $t("label.active_devices") }}</h3>
                <p class="text-xs text-[#6E7191] mt-1">
                    {{ $t("label.total_devices") }}: <span class="font-semibold text-heading">{{ totalDevices }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2" v-if="sessions.length > 0">
                <button
                    v-if="mode === 'self' && sessions.length > 1"
                    type="button"
                    @click="logoutOthers"
                    class="db-btn-outline h-[34px] text-xs !text-[#FB4E4E] !border-[#FB4E4E]"
                >
                    <i class="lab lab-line-logout"></i>
                    <span>{{ $t("button.logout_other_devices") }}</span>
                </button>
                <button
                    type="button"
                    @click="logoutAll"
                    class="db-btn-outline h-[34px] text-xs !text-[#FB4E4E] !border-[#FB4E4E]"
                >
                    <i class="lab lab-line-logout"></i>
                    <span>{{ $t("button.logout_all_devices") }}</span>
                </button>
            </div>
        </div>

        <div class="db-card-body">
            <div v-if="loading.isActive" class="py-8 text-center text-sm text-[#6E7191]">
                {{ $t("label.loading") }}
            </div>

            <div v-else-if="loadError" class="py-8 text-center">
                <span class="text-sm text-[#FB4E4E]">{{ loadError }}</span>
            </div>

            <div v-else-if="sessions.length === 0" class="py-8 text-center">
                <span class="text-sm text-[#6E7191]">{{ $t("message.no_data_found") }}</span>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="db-table stripe">
                    <thead class="db-table-head">
                        <tr class="db-table-head-tr">
                            <th class="db-table-head-th">{{ $t("label.device") }}</th>
                            <th class="db-table-head-th">{{ $t("label.browser") }}</th>
                            <th class="db-table-head-th">{{ $t("label.ip_address") }}</th>
                            <th class="db-table-head-th">{{ $t("label.last_active") }}</th>
                            <th class="db-table-head-th">{{ $t("label.logged_in_at") }}</th>
                            <th class="db-table-head-th">{{ $t("label.action") }}</th>
                        </tr>
                    </thead>
                    <tbody class="db-table-body">
                        <tr v-for="session in sessions" :key="session.id" class="db-table-body-tr">
                            <td class="db-table-body-td">
                                <div class="flex items-center gap-2">
                                    <i class="lab lab-monitor-mobbile text-lg text-primary"></i>
                                    <div>
                                        <p class="font-medium capitalize">{{ session.device_name }}</p>
                                        <span
                                            v-if="session.is_current"
                                            class="inline-block mt-0.5 py-0.5 px-2 rounded text-[10px] font-medium uppercase bg-[#E8F8EF] text-[#1AB759]"
                                        >
                                            {{ $t("label.current_device") }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="db-table-body-td">{{ session.browser }}</td>
                            <td class="db-table-body-td" dir="ltr">{{ session.ip_address || "—" }}</td>
                            <td class="db-table-body-td">{{ formatDate(session.last_used_at || session.created_at) }}</td>
                            <td class="db-table-body-td">{{ formatDate(session.created_at) }}</td>
                            <td class="db-table-body-td">
                                <button
                                    v-if="!session.is_current || mode === 'admin'"
                                    type="button"
                                    @click="logoutDevice(session)"
                                    class="db-btn-outline h-[30px] text-xs !text-[#FB4E4E] !border-[#FB4E4E]"
                                >
                                    <i class="lab lab-line-logout"></i>
                                    <span>{{ $t("button.logout_device") }}</span>
                                </button>
                                <span v-else class="text-xs text-[#6E7191]">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
import alertService from "../../../services/alertService";
import appService from "../../../services/appService";

export default {
    name: "UserSessionsComponent",
    props: {
        userId: {
            type: [String, Number],
            default: null,
        },
        apiPrefix: {
            type: String,
            default: "administrator",
        },
        mode: {
            type: String,
            default: "admin",
        },
    },
    data() {
        return {
            loading: {
                isActive: false,
            },
            loadError: null,
        };
    },
    computed: {
        sessions: function () {
            return this.$store.getters["userSession/lists"];
        },
        totalDevices: function () {
            return this.$store.getters["userSession/totalDevices"];
        },
    },
    mounted() {
        this.loadSessions();
    },
    methods: {
        loadSessions: function () {
            this.loading.isActive = true;
            this.loadError = null;
            this.$store.dispatch("userSession/lists", {
                mode: this.mode,
                apiPrefix: this.apiPrefix,
                userId: this.userId,
            }).then(() => {
                this.loading.isActive = false;
            }).catch((err) => {
                this.loading.isActive = false;
                this.loadError = err.response?.data?.message || this.$t("error.something_wrong");
            });
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
                    mode: this.mode,
                    apiPrefix: this.apiPrefix,
                    userId: this.userId,
                    tokenId: session.id,
                }).then((res) => {
                    alertService.success(res.data.message || this.$t("message.session_logout_success"));
                    this.loadSessions();
                }).catch((err) => {
                    this.loading.isActive = false;
                    alertService.error(err.response?.data?.message || this.$t("error.something_wrong"));
                });
            }).catch(() => {});
        },
        logoutAll: function () {
            appService.destroyConfirmation().then(() => {
                this.loading.isActive = true;
                this.$store.dispatch("userSession/destroyAll", {
                    mode: this.mode,
                    apiPrefix: this.apiPrefix,
                    userId: this.userId,
                    othersOnly: false,
                }).then((res) => {
                    alertService.success(res.data.message || this.$t("message.all_sessions_logout_success"));
                    this.loadSessions();
                }).catch((err) => {
                    this.loading.isActive = false;
                    alertService.error(err.response?.data?.message || this.$t("error.something_wrong"));
                });
            }).catch(() => {});
        },
        logoutOthers: function () {
            appService.destroyConfirmation().then(() => {
                this.loading.isActive = true;
                this.$store.dispatch("userSession/destroyAll", {
                    mode: this.mode,
                    apiPrefix: this.apiPrefix,
                    userId: this.userId,
                    othersOnly: true,
                }).then((res) => {
                    alertService.success(res.data.message || this.$t("message.other_sessions_logout_success"));
                    this.loadSessions();
                }).catch((err) => {
                    this.loading.isActive = false;
                    alertService.error(err.response?.data?.message || this.$t("error.something_wrong"));
                });
            }).catch(() => {});
        },
    },
};
</script>
