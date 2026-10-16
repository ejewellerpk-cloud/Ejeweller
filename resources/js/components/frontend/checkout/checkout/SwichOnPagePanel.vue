<template>
    <div class="mb-6 mt-6 rounded-2xl shadow-card overflow-hidden bg-white">
        <h4 class="font-bold capitalize p-4 border-b border-gray-100 flex items-center justify-between gap-3">
            <span>JazzCash / EasyPaisa</span>
            <span v-if="amountLabel" class="text-sm font-extrabold text-primary normal-case tracking-normal">
                {{ amountLabel }}
            </span>
        </h4>

        <div class="p-4 space-y-4" v-if="phase === 'form'">
            <div>
                <p class="block mb-2 text-sm font-medium text-heading">Choose method</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <label v-if="ewalletEnabled" class="cursor-pointer">
                        <input class="peer sr-only" type="radio" value="jazzcash" v-model="form.swich_method">
                        <span class="flex flex-col h-full rounded-lg border border-[#F7F7F7] bg-[#F7F7F7] px-3 py-3 peer-checked:border-primary/50 peer-checked:bg-[#FFF4F1] transition">
                            <span class="text-sm font-semibold text-heading">JazzCash</span>
                            <span class="mt-0.5 text-[11px] text-paragraph">E-Wallet · OTP</span>
                        </span>
                    </label>
                    <label v-if="ewalletEnabled" class="cursor-pointer">
                        <input class="peer sr-only" type="radio" value="easypaisa" v-model="form.swich_method">
                        <span class="flex flex-col h-full rounded-lg border border-[#F7F7F7] bg-[#F7F7F7] px-3 py-3 peer-checked:border-primary/50 peer-checked:bg-[#FFF4F1] transition">
                            <span class="text-sm font-semibold text-heading">EasyPaisa</span>
                            <span class="mt-0.5 text-[11px] text-paragraph">E-Wallet · OTP</span>
                        </span>
                    </label>
                    <label v-if="billerEnabled" class="cursor-pointer">
                        <input class="peer sr-only" type="radio" value="biller" v-model="form.swich_method">
                        <span class="flex flex-col h-full rounded-lg border border-[#F7F7F7] bg-[#F7F7F7] px-3 py-3 peer-checked:border-primary/50 peer-checked:bg-[#FFF4F1] transition">
                            <span class="text-sm font-semibold text-heading">1Bill / PSID</span>
                            <span class="mt-0.5 text-[11px] text-paragraph">Pay via voucher</span>
                        </span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block mb-2 text-sm font-medium text-heading">Wallet number</label>
                <input
                    v-model="form.swich_mobile"
                    type="tel"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="03072753841"
                    class="w-full h-12 rounded-lg px-4 border border-gray-200 bg-white text-heading focus:outline-none focus:border-primary"
                />
            </div>

            <div v-if="showEmailField">
                <label class="block mb-2 text-sm font-medium text-heading">Email</label>
                <input
                    v-model="form.swich_email"
                    type="email"
                    class="w-full h-12 rounded-lg px-4 border border-gray-200 bg-white text-heading focus:outline-none focus:border-primary"
                />
            </div>
        </div>

        <div class="p-4 space-y-4" v-else>
            <div class="flex items-center justify-between text-sm" v-if="orderSerial">
                <span class="text-paragraph">Order</span>
                <span class="font-bold text-heading">{{ orderSerial }}</span>
            </div>

            <div v-if="phase === 'paid'" class="rounded-xl bg-green-50 border border-green-200 p-5 text-center">
                <p class="text-xl font-extrabold text-green-700">Thank you</p>
                <p class="mt-1 text-sm font-semibold text-heading">Payment received</p>
            </div>

            <div v-else-if="phase === 'cancelled' || phase === 'failed'" class="rounded-xl bg-red-50 border border-red-200 p-5 text-center space-y-3">
                <p class="text-lg font-extrabold text-red-600">Payment cancelled</p>
                <p class="text-sm text-paragraph">{{ statusMessage || 'Request reject / fail ho gayi. Dobara try karein.' }}</p>
                <button type="button" class="inline-flex items-center justify-center w-full h-11 rounded-lg bg-primary text-white font-semibold" @click="resetToForm">
                    Try again
                </button>
            </div>

            <template v-else>
                <div v-if="consumerNumber" class="rounded-xl bg-[#FFF4F1] border border-primary/20 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-paragraph mb-2 text-center">1Bill PSID</p>
                    <p class="text-xl font-extrabold tracking-wide text-heading select-all break-all text-center">{{ consumerNumber }}</p>
                    <ol class="mt-4 space-y-1.5 text-sm text-heading list-decimal list-inside leading-6">
                        <li><strong>JazzCash</strong> app kholen</li>
                        <li><strong>More</strong> → <strong>Corporate Payments</strong></li>
                        <li><strong>1Bill</strong> mein yeh PSID paste karke Pay karein</li>
                    </ol>
                </div>

                <div v-else class="rounded-xl bg-[#F7F7FC] border border-gray-100 p-5 space-y-3 text-center">
                    <span v-if="phase === 'sending'" class="inline-block h-10 w-10 rounded-full border-4 border-primary/20 border-t-primary animate-spin"></span>
                    <p class="text-sm font-semibold text-heading">{{ walletTitle }}</p>
                    <p class="text-sm text-paragraph">{{ walletHelp }}</p>
                </div>

                <p class="text-sm font-semibold text-center text-primary">{{ statusMessage }}</p>
            </template>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import alertService from "../../../../services/alertService";
import appService from "../../../../services/appService";

export default {
    name: "SwichOnPagePanel",
    props: {
        amount: { type: [Number, String], default: 0 },
        defaultEmail: { type: String, default: "" },
        defaultMobile: { type: String, default: "" },
    },
    emits: ["paid", "failed"],
    data() {
        return {
            ewalletEnabled: true,
            billerEnabled: true,
            phase: "form",
            form: {
                swich_method: "jazzcash",
                swich_mobile: "",
                swich_email: "",
            },
            orderId: null,
            orderSerial: "",
            consumerNumber: null,
            statusMessage: "",
            pollTimer: null,
            stopped: false,
        };
    },
    computed: {
        setting() {
            return this.$store.getters["frontendSetting/lists"] || {};
        },
        amountLabel() {
            const amount = Number(this.amount || 0);
            if (!amount) {
                return "";
            }
            return appService.currencyFormat(
                amount,
                this.setting.site_digit_after_decimal_point,
                this.setting.site_default_currency_symbol || "PKR",
                this.setting.site_currency_position
            );
        },
        resolvedEmail() {
            return String(this.form.swich_email || this.defaultEmail || "").trim();
        },
        showEmailField() {
            return !String(this.defaultEmail || "").trim();
        },
        walletName() {
            return this.form.swich_method === "easypaisa" ? "EasyPaisa" : "JazzCash";
        },
        walletTitle() {
            if (this.phase === "waiting") {
                return `${this.walletName} par payment request chali gayi hai.`;
            }
            return `${this.walletName} par payment request bheji ja rahi hai.`;
        },
        walletHelp() {
            return `Apne ${this.walletName} app mein Approve karein. Yeh page band na karein.`;
        },
    },
    watch: {
        defaultEmail(value) {
            if (!String(this.form.swich_email || "").trim() && value) {
                this.form.swich_email = value;
            }
        },
        defaultMobile(value) {
            if (!String(this.form.swich_mobile || "").trim() && value) {
                this.form.swich_mobile = value;
            }
        },
    },
    mounted() {
        this.form.swich_email = this.defaultEmail || "";
        this.form.swich_mobile = this.defaultMobile || "";
        this.loadOptions();
    },
    beforeUnmount() {
        this.stopPolling();
    },
    methods: {
        loadOptions() {
            axios
                .get("frontend/order/swich/options")
                .then((res) => {
                    const data = res.data?.data || {};
                    this.ewalletEnabled = data.ewallet !== false;
                    this.billerEnabled = data.biller !== false;
                    if (!this.ewalletEnabled && this.billerEnabled) {
                        this.form.swich_method = "biller";
                    }
                })
                .catch(() => {
                    this.ewalletEnabled = true;
                    this.billerEnabled = true;
                });
        },
        validateForm() {
            if (!this.form.swich_method) {
                alertService.error("Please choose a payment method.");
                return false;
            }
            if (!String(this.form.swich_mobile || "").trim()) {
                alertService.error("Please enter your JazzCash / EasyPaisa wallet number.");
                return false;
            }
            if (!this.resolvedEmail) {
                alertService.error("Please enter your email for payment.");
                return false;
            }
            return true;
        },
        getPayload() {
            return {
                swich_method: this.form.swich_method,
                swich_mobile: String(this.form.swich_mobile || "").trim(),
                swich_email: this.resolvedEmail,
            };
        },
        async startForOrder(orderId, orderSerial = "") {
            if (!this.validateForm()) {
                return false;
            }
            this.orderId = orderId;
            this.orderSerial = orderSerial;
            this.stopped = false;
            this.phase = "sending";
            this.statusMessage = `Sending request to ${this.walletName}…`;
            this.consumerNumber = null;

            try {
                const startRes = await axios.post(`frontend/order/${orderId}/swich/start`, this.getPayload());
                if (!startRes.data?.ok) {
                    throw new Error(startRes.data?.message || "Could not start payment");
                }
                await axios.post(`frontend/order/${orderId}/swich/initiate`);
                this.startPolling();
                return true;
            } catch (err) {
                this.phase = "failed";
                this.statusMessage = err.response?.data?.message || err.message || "Payment failed to start";
                alertService.error(this.statusMessage);
                this.$emit("failed", this.statusMessage);
                return false;
            }
        },
        startPolling() {
            this.stopPolling();
            this.pollTimer = setInterval(() => this.pollStatus(), 3000);
            this.pollStatus();
        },
        stopPolling() {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },
        pollStatus() {
            if (this.stopped || !this.orderId) {
                return;
            }
            axios
                .get(`frontend/order/${this.orderId}/swich/status`)
                .then((res) => {
                    const data = res.data || {};
                    const phase = data.phase || data.status || "sending";
                    if (data.consumerNumber) {
                        this.consumerNumber = data.consumerNumber;
                    }
                    if (data.message) {
                        this.statusMessage = data.message;
                    }

                    if (phase === "paid" || data.status === "paid" || data.status === "success") {
                        this.phase = "paid";
                        this.statusMessage = "Payment received";
                        this.stopped = true;
                        this.stopPolling();
                        this.$emit("paid", { orderId: this.orderId });
                        return;
                    }
                    if (["cancelled", "canceled", "failed", "declined", "rejected"].includes(String(phase).toLowerCase())
                        || ["cancelled", "canceled", "failed", "declined", "rejected"].includes(String(data.status).toLowerCase())) {
                        this.phase = "cancelled";
                        this.statusMessage = data.message || "Payment cancelled";
                        this.stopped = true;
                        this.stopPolling();
                        this.$emit("failed", this.statusMessage);
                        return;
                    }
                    this.phase = phase === "waiting" ? "waiting" : "sending";
                    if (!data.message) {
                        this.statusMessage = this.phase === "waiting"
                            ? `Waiting — approve in your ${this.walletName} app.`
                            : `Sending request to ${this.walletName}…`;
                    }
                })
                .catch(() => {});
        },
        resetToForm() {
            this.stopPolling();
            this.stopped = false;
            this.phase = "form";
            this.orderId = null;
            this.consumerNumber = null;
            this.statusMessage = "";
        },
    },
};
</script>
