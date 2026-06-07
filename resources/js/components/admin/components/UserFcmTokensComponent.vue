<template>
    <div class="db-card">
        <div class="db-card-header flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="db-card-title">{{ $t("label.push_devices") }}</h3>
                <p class="text-xs text-[#6E7191] mt-1">
                    {{ $t("label.total_devices") }}: <span class="font-semibold text-heading">{{ totalDevices }}</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2" v-if="tokens.length > 0">
                <button
                    v-if="mode === 'self' && tokens.length > 1"
                    type="button"
                    @click="revokeOthers"
                    class="db-btn-outline h-[34px] text-xs !text-[#FB4E4E] !border-[#FB4E4E]"
                >
                    <i class="lab lab-line-notification"></i>
                    <span>{{ $t("button.revoke_other_push_devices") }}</span>
                </button>
                <button
                    type="button"
                    @click="revokeAll"
                    class="db-btn-outline h-[34px] text-xs !text-[#FB4E4E] !border-[#FB4E4E]"
                >
                    <i class="lab lab-line-notification"></i>
                    <span>{{ $t("button.revoke_all_push_devices") }}</span>
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

            <div v-else-if="tokens.length === 0" class="py-8 text-center">
                <span class="text-sm text-[#6E7191]">{{ $t("message.no_data_found") }}</span>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="db-table stripe">
                    <thead class="db-table-head">
                        <tr class="db-table-head-tr">
                            <th class="db-table-head-th">{{ $t("label.device") }}</th>
                            <th class="db-table-head-th">{{ $t("label.platform") }}</th>
                            <th class="db-table-head-th">{{ $t("label.token_preview") }}</th>
                            <th class="db-table-head-th">{{ $t("label.ip_address") }}</th>
                            <th class="db-table-head-th">{{ $t("label.last_active") }}</th>
                            <th class="db-table-head-th">{{ $t("label.registered_at") }}</th>
                            <th class="db-table-head-th">{{ $t("label.action") }}</th>
                        </tr>
                    </thead>
                    <tbody class="db-table-body">
                        <tr v-for="token in tokens" :key="token.id" class="db-table-body-tr">
                            <td class="db-table-body-td">
                                <div class="flex items-center gap-2">
                                    <i class="lab lab-line-notification text-lg text-primary"></i>
                                    <span class="font-medium capitalize">{{ token.device_name }}</span>
                                </div>
                            </td>
                            <td class="db-table-body-td capitalize">{{ token.platform }}</td>
                            <td class="db-table-body-td font-mono text-xs" dir="ltr">{{ token.token_preview }}</td>
                            <td class="db-table-body-td" dir="ltr">{{ token.ip_address || "—" }}</td>
                            <td class="db-table-body-td">{{ formatDate(token.last_used_at) }}</td>
                            <td class="db-table-body-td">{{ formatDate(token.created_at) }}</td>
                            <td class="db-table-body-td">
                                <button
                                    type="button"
                                    @click="revokeDevice(token)"
                                    class="db-btn-outline h-[30px] text-xs !text-[#FB4E4E] !border-[#FB4E4E]"
                                >
                                    <i class="lab lab-line-cross"></i>
                                    <span>{{ $t("button.revoke_push_device") }}</span>
                                </button>
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
    name: "UserFcmTokensComponent",
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
        tokens: function () {
            return this.$store.getters["userFcmToken/lists"];
        },
        totalDevices: function () {
            return this.$store.getters["userFcmToken/totalDevices"];
        },
    },
    mounted() {
        this.loadTokens();
    },
    methods: {
        loadTokens: function () {
            this.loading.isActive = true;
            this.loadError = null;
            this.$store.dispatch("userFcmToken/lists", {
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
        revokeDevice: function (token) {
            appService.destroyConfirmation().then(() => {
                this.loading.isActive = true;
                this.$store.dispatch("userFcmToken/destroy", {
                    mode: this.mode,
                    apiPrefix: this.apiPrefix,
                    userId: this.userId,
                    tokenId: token.id,
                }).then((res) => {
                    alertService.success(res.data.message || this.$t("message.push_device_removed"));
                    this.loadTokens();
                }).catch((err) => {
                    this.loading.isActive = false;
                    alertService.error(err.response?.data?.message || this.$t("error.something_wrong"));
                });
            }).catch(() => {});
        },
        revokeAll: function () {
            appService.destroyConfirmation().then(() => {
                this.loading.isActive = true;
                this.$store.dispatch("userFcmToken/destroyAll", {
                    mode: this.mode,
                    apiPrefix: this.apiPrefix,
                    userId: this.userId,
                    othersOnly: false,
                }).then((res) => {
                    alertService.success(res.data.message || this.$t("message.push_device_removed"));
                    this.loadTokens();
                }).catch((err) => {
                    this.loading.isActive = false;
                    alertService.error(err.response?.data?.message || this.$t("error.something_wrong"));
                });
            }).catch(() => {});
        },
        revokeOthers: function () {
            appService.destroyConfirmation().then(() => {
                this.loading.isActive = true;
                this.$store.dispatch("userFcmToken/destroyAll", {
                    mode: this.mode,
                    apiPrefix: this.apiPrefix,
                    userId: this.userId,
                    othersOnly: true,
                }).then((res) => {
                    alertService.success(res.data.message || this.$t("message.push_device_removed"));
                    this.loadTokens();
                }).catch((err) => {
                    this.loading.isActive = false;
                    alertService.error(err.response?.data?.message || this.$t("error.something_wrong"));
                });
            }).catch(() => {});
        },
    },
};
</script>
