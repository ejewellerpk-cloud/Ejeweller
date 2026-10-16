<template>
    <LoadingComponent :props="loading" />

    <div id="orderCancelModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">{{ $t('label.canceled') || 'Cancel Order' }}</h3>
                <button type="button" class="modal-close fa-solid fa-xmark text-xl text-slate-400 hover:text-red-500"
                    @click.prevent="close"></button>
            </div>
            <div class="modal-body">
                <div v-if="showDeliveryWarning"
                    class="mb-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <p class="font-semibold mb-1">
                        {{ $t('message.cancel_delivery_warning_title') || 'This order may already be out for delivery' }}
                    </p>
                    <p class="leading-relaxed">
                        {{ $t('message.cancel_delivery_warning_body')
                            || 'Cancelling here only updates the shop order. The PostEx parcel can still move with the courier unless you also cancel PostEx.' }}
                    </p>
                    <p v-if="order?.postex_tracking_number" class="mt-2 text-xs font-medium">
                        {{ $t('label.tracking_no') || 'Tracking' }}: {{ order.postex_tracking_number }}
                        <span v-if="order.postex_status"> · {{ order.postex_status }}</span>
                    </p>
                </div>

                <form @submit.prevent="submitCancelOrder" class="w-full block">
                    <div class="form-row">
                        <div class="form-col-12">
                            <label for="cancel_reason" class="db-field-title required">
                                {{ $t('label.reason') }}
                            </label>
                            <input id="cancel_reason" v-model="form.reason" type="text"
                                class="db-field-control" :class="error ? 'invalid' : ''"
                                :placeholder="$t('label.reason') || 'Reason'" />
                            <small class="db-field-alert" v-if="error">{{ error }}</small>
                        </div>

                        <div class="form-col-12" v-if="hasPostEx">
                            <label class="flex items-start gap-2 cursor-pointer select-none">
                                <input type="checkbox" v-model="form.cancelPostEx"
                                    class="mt-1 cs-custom-checkbox" />
                                <span class="text-sm leading-5">
                                    <span class="font-semibold text-heading">
                                        {{ $t('button.cancel_postex') || 'Cancel PostEx' }}
                                    </span>
                                    <span class="block text-xs text-slate-500 mt-0.5">
                                        {{ $t('message.cancel_postex_with_order_hint')
                                            || 'Also call PostEx cancel for this tracking number when saving.' }}
                                    </span>
                                </span>
                            </label>
                        </div>

                        <div class="form-col-12">
                            <div class="modal-btns flex flex-wrap gap-2 justify-end">
                                <button type="button" class="modal-btn-outline modal-close" @click.prevent="close">
                                    <i class="lab lab-fill-close-circle"></i>
                                    <span>{{ $t('button.close') }}</span>
                                </button>

                                <button v-if="hasPostEx" type="button"
                                    class="db-btn py-2 text-white bg-rose-600"
                                    :disabled="loading.isActive"
                                    @click.prevent="submitCancelPostExOnly">
                                    <i class="fa-solid fa-truck"></i>
                                    <span>{{ $t('button.cancel_postex') || 'Cancel PostEx' }}</span>
                                </button>

                                <button type="submit" class="db-btn py-2 text-white bg-[#FB4E4E]"
                                    :disabled="loading.isActive">
                                    <i class="lab lab-fill-close-circle"></i>
                                    <span>{{ $t('button.cancel_order') || $t('label.canceled') || 'Cancel Order' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import appService from "../../../services/appService";
import alertService from "../../../services/alertService";
import orderStatusEnum from "../../../enums/modules/orderStatusEnum";
import LoadingComponent from "../components/LoadingComponent.vue";

export default {
    name: "OnlineOrderCancelModal",
    components: { LoadingComponent },
    emits: ["done"],
    data() {
        return {
            loading: { isActive: false },
            order: null,
            form: {
                reason: "",
                cancelPostEx: true,
            },
            error: "",
        };
    },
    computed: {
        hasPostEx() {
            return !!(this.order?.postex_tracking_number);
        },
        isOnTheWay() {
            return Number(this.order?.status) === orderStatusEnum.ON_THE_WAY;
        },
        postexLooksInTransit() {
            const s = String(this.order?.postex_status || "").toLowerCase();
            if (!s) {
                return false;
            }
            return s.includes("out for")
                || s.includes("transit")
                || s.includes("deliver")
                || s.includes("picked")
                || s.includes("warehouse")
                || s.includes("en-route")
                || s.includes("en route");
        },
        showDeliveryWarning() {
            return this.isOnTheWay || this.hasPostEx || this.postexLooksInTransit;
        },
    },
    methods: {
        open(order) {
            this.order = order || null;
            this.form.reason = "";
            this.form.cancelPostEx = !!this.hasPostEx;
            this.error = "";
            appService.modalShow("#orderCancelModal");
        },
        close() {
            appService.modalHide("#orderCancelModal");
            this.error = "";
            this.form.reason = "";
            this.order = null;
        },
        async cancelPostExApi(orderId) {
            await axios.put(`admin/online-order/${orderId}/postex/cancel`);
        },
        async submitCancelPostExOnly() {
            if (!this.order?.id || !this.hasPostEx) {
                return;
            }
            try {
                this.loading.isActive = true;
                await this.cancelPostExApi(this.order.id);
                alertService.success(this.$t("button.cancel_postex") || "PostEx cancelled");
                this.$emit("done", { orderId: this.order.id, canceledOrder: false, canceledPostEx: true });
                this.close();
            } catch (err) {
                alertService.error(err?.response?.data?.message || err?.message || this.$t("message.something_wrong"));
            } finally {
                this.loading.isActive = false;
            }
        },
        async submitCancelOrder() {
            if (!this.order?.id) {
                return;
            }
            const reason = String(this.form.reason || "").trim();
            if (!reason) {
                this.error = this.$t("message.reason_required") || "Reason is required.";
                return;
            }
            this.error = "";

            try {
                this.loading.isActive = true;
                await axios.post(`admin/online-order/change-status/${this.order.id}`, {
                    status: orderStatusEnum.CANCELED,
                    reason,
                });

                let canceledPostEx = false;
                if (this.form.cancelPostEx && this.hasPostEx) {
                    try {
                        await this.cancelPostExApi(this.order.id);
                        canceledPostEx = true;
                    } catch (postExErr) {
                        alertService.error(
                            (this.$t("message.postex_cancel_failed_order_canceled")
                                || "Order canceled, but PostEx cancel failed: ")
                            + (postExErr?.response?.data?.message || postExErr?.message || "")
                        );
                    }
                }

                alertService.successFlip(1, this.$t("label.status"));
                this.$emit("done", {
                    orderId: this.order.id,
                    canceledOrder: true,
                    canceledPostEx,
                });
                this.close();
            } catch (err) {
                this.error = err?.response?.data?.message || err?.message || this.$t("message.something_wrong");
            } finally {
                this.loading.isActive = false;
            }
        },
    },
};
</script>
