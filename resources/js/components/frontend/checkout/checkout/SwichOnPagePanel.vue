<template>
    <div class="mb-6 rounded-2xl border border-[#E8E4DC] bg-white shadow-card overflow-hidden">
        <div class="bg-heading text-white px-4 sm:px-6 py-4 flex items-center justify-between gap-3">
            <div>
                <p class="text-[10px] uppercase tracking-[0.18em] text-white/70 mb-1">Pay on this page</p>
                <h3 class="text-base sm:text-lg font-extrabold leading-tight">JazzCash / EasyPaisa / 1Bill</h3>
            </div>
            <div class="text-right shrink-0" v-if="amountLabel">
                <p class="text-[10px] text-white/70">Amount</p>
                <p class="text-lg font-black">{{ amountLabel }}</p>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-5" v-if="phase === 'form'">
            <p class="text-sm text-paragraph">
                Payment yahin complete hogi — page leave nahi karna. Wallet OTP approve karein ya 1Bill PSID se pay karein.
            </p>

            <div>
                <p class="block mb-2 text-sm font-bold text-heading">Choose method</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <label v-if="ewalletEnabled" class="cursor-pointer">
                        <input class="peer sr-only" type="radio" value="jazzcash" v-model="form.swich_method">
                        <span class="flex flex-col h-full rounded-xl border-2 border-[#E8E4DC] px-3 py-3 peer-checked:border-primary peer-checked:bg-[#FFF4F1] transition">
                            <span class="text-sm font-extrabold text-heading">JazzCash</span>
                            <span class="mt-0.5 text-[11px] text-paragraph">E-Wallet · OTP</span>
                        </span>
                    </label>
                    <label v-if="ewalletEnabled" class="cursor-pointer">
                        <input class="peer sr-only" type="radio" value="easypaisa" v-model="form.swich_method">
                        <span class="flex flex-col h-full rounded-xl border-2 border-[#E8E4DC] px-3 py-3 peer-checked:border-primary peer-checked:bg-[#FFF4F1] transition">
                            <span class="text-sm font-extrabold text-heading">EasyPaisa</span>
                            <span class="mt-0.5 text-[11px] text-paragraph">E-Wallet · OTP</span>
                        </span>
                    </label>
                    <label v-if="billerEnabled" class="cursor-pointer">
                        <input class="peer sr-only" type="radio" value="biller" v-model="form.swich_method">
                        <span class="flex flex-col h-full rounded-xl border-2 border-[#E8E4DC] px-3 py-3 peer-checked:border-primary peer-checked:bg-[#FFF4F1] transition">
                            <span class="text-sm font-extrabold text-heading">1Bill / PSID</span>
                            <span class="mt-0.5 text-[11px] text-paragraph">Pay via voucher</span>
                        </span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block mb-2 text-sm font-bold text-heading">JazzCash / EasyPaisa wallet number</label>
                <input
                    v-model="form.swich_mobile"
                    type="tel"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="03072753841"
                    class="w-full h-12 rounded-xl px-4 border border-[#D9DBE9] bg-white text-heading"
                />
                <p class="mt-1.5 text-[11px] text-paragraph">Wallet number likhein (example: 03072753841)</p>
            </div>

            <div>
                <label class="block mb-2 text-sm font-bold text-heading">CNIC (optional)</label>
                <input
                    v-model="form.swich_cnic"
                    type="tel"
                    inputmode="numeric"
                    maxlength="13"
                    placeholder="13 digits, no dashes"
                    class="w-full h-12 rounded-xl px-4 border border-[#D9DBE9] bg-white text-heading"
                />
            </div>

            <div>
                <label class="block mb-2 text-sm font-bold text-heading">Email</label>
                <input
                    v-model="form.swich_email"
                    type="email"
                    class="w-full h-12 rounded-xl px-4 border border-[#D9DBE9] bg-white text-heading"
                />
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-4" v-else>
            <div class="flex items-center justify-between text-sm" v-if="orderSerial">
                <span class="text-paragraph">Order</span>
                <span class="font-bold text-heading">{{ orderSerial }}</span>
            </div>

            <div v-if="phase === 'paid'" class="rounded-2xl bg-green-50 border border-green-200 p-5 text-center">
                <p class="text-2xl font-black text-green-700">Thank you</p>
                <p class="mt-1 text-sm font-bold text-heading">Payment received</p>
            </div>

            <div v-else-if="phase === 'cancelled' || phase === 'failed'" class="rounded-2xl bg-red-50 border border-red-200 p-5 text-center space-y-3">
                <p class="text-xl font-black text-red-600">Payment cancelled</p>
                <p class="text-sm text-paragraph">{{ statusMessage || 'Request reject / fail ho gayi. Dobara try karein.' }}</p>
                <button type="button" class="inline-flex items-center justify-center w-full h-11 rounded-xl bg-primary text-white font-bold" @click="resetToForm">
                    Try again
                </button>
            </div>

            <template v-else>
                <div v-if="consumerNumber" class="rounded-2xl bg-[#FFF4F1] border border-primary/20 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-paragraph mb-2 text-center">1Bill PSID</p>
                    <p class="text-xl font-black tracking-wide text-heading select-all break-all text-center">{{ consumerNumber }}</p>
                    <ol class="mt-4 space-y-1.5 text-sm text-heading list-decimal list-inside leading-6">
                        <li><strong>JazzCash</strong> app kholen</li>
                        <li><strong>More</strong> → <strong>Corporate Payments</strong></li>
                        <li><strong>1Bill</strong> mein yeh PSID paste karke Pay karein</li>
                    </ol>
                </div>

                <div v-else class="rounded-2xl bg-[#F7F7FC] border border-[#E8E4DC] p-5 space-y-3 text-center">
                    <span v-if="phase === 'sending'" class="inline-block h-10 w-10 rounded-full border-4 border-primary/20 border-t-primary animate-spin"></span>
                    <p class="text-sm font-bold text-heading">{{ walletTitle }}</p>
                    <p class="text-sm text-paragraph">{{ walletHelp }}</p>
                </div>

                <p class="text-sm font-bold text-center text-primary">{{ statusMessage }}</p>
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
                swich_cnic: "",
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
            if (!String(this.form.swich_email || "").trim()) {
                alertService.error("Please enter your email for payment.");
                return false;
            }
            return true;
        },
        getPayload() {
            return {
                swich_method: this.form.swich_method,
                swich_mobile: String(this.form.swich_mobile || "").trim(),
                swich_cnic: String(this.form.swich_cnic || "").trim(),
                swich_email: String(this.form.swich_email || "").trim(),
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
