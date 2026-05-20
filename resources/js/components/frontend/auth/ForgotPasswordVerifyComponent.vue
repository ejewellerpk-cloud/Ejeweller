<template>
    <LoadingComponent :props="loading" />
    <div class="w-full max-w-3xl mx-auto rounded-2xl flex overflow-hidden gap-y-6 bg-white shadow-card mb-24 sm:mb-0">
        <img :src="APP_URL + '/images/required/auth.jpg'" alt="banners"
            class="w-full hidden sm:block sm:max-w-xs md:max-w-sm flex-shrink-0" />
        <form class="w-full p-6" @submit.prevent="save">
            <div class="text-center relative mb-8">
                <router-link :to="{ name: 'auth.forgotPassword' }"
                    class="absolute top-1/2 ltr:left-0 rtl:right-0 -translate-y-1/2">
                    <i class="lab-line-long-arrow-left text-2xl !font-semibold text-primary"></i>
                </router-link>
                <h3 class="capitalize text-2xl mb-2 font-bold text-primary">{{ $t('label.verification') }}</h3>
                <div v-if="errors.validation"
                    class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 mb-5 rounded relative" role="alert">
                    <span class="block sm:inline">{{ errors.validation }}</span>
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" @click="close">
                        <i class="lab lab-close-circle-line margin-top-5-px"></i>
                    </span>
                </div>
            </div>
            <div class="mb-6">
                <label class="text-sm font-medium capitalize mb-1 field-title required">{{ $t('label.enter_code') }}
                </label>
                
                <!-- Premium OTP Input Boxes -->
                <div class="flex items-center justify-center gap-3 my-6">
                    <input
                        v-for="index in otpLength"
                        :key="index"
                        :ref="'otp_input_' + (index - 1)"
                        type="text"
                        maxlength="1"
                        v-model="otpDigits[index - 1]"
                        @input="handleOtpInput($event, index - 1)"
                        @keydown.delete="handleOtpBackspace($event, index - 1)"
                        @paste="handleOtpPaste($event)"
                        class="w-12 h-12 sm:w-14 sm:h-14 text-center text-xl font-extrabold border border-[#D9DBE9] rounded-xl focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none transition-all duration-300 bg-[#F7F7FC] focus:bg-white text-heading"
                    />
                </div>

                <small class="db-field-alert" v-if="errors">{{ errors }}</small>
                <small class="block mt-3 text-center text-sm font-medium">{{ $t('label.not_receive_code') }}
                    <button v-if="props.form.phone" @click.prevent="resendCodeToPhone" type="button"
                        class="font-bold text-primary">
                        {{ $t('button.resend_code') }}
                    </button>
                    <button v-else @click.prevent="resendCodeToEmail" type="button" class="font-bold text-primary">
                        {{ $t('button.resend_code') }}
                    </button>
                </small>
            </div>
            <button type="submit"
                class="font-bold text-center w-full h-12 leading-12 rounded-full bg-primary text-white mb-6">
                {{ $t('button.verify') }}</button>
            <router-link class="block text-center font-bold text-primary" :to="{ name: 'auth.login' }">
                {{ $t('label.back_to_sign_in') }}
            </router-link>
        </form>
    </div>
</template>

<script>

import LoadingComponent from "../components/LoadingComponent";
import alertService from "../../../services/alertService";
import ENV from "../../../config/env";

export default {
    name: "ForgotPasswordVerifyComponent",
    components: { LoadingComponent },
    data() {
        return {
            loading: {
                isActive: false,
            },
            props: {
                form: {
                    email: "",
                    phone: "",
                    token: "",
                    country_code: "",
                },
            },
            APP_URL: ENV.API_URL,
            errors: "",
            message: null,
            otpDigits: [],
        };
    },
    computed: {
        otpLength: function () {
            const limit = this.$store.getters['frontendSetting/lists']?.otp_digit_limit;
            return limit ? parseInt(limit) : 4;
        }
    },
    watch: {
        otpLength: {
            immediate: true,
            handler(newVal) {
                this.otpDigits = Array(newVal).fill("");
            }
        },
        otpDigits: {
            deep: true,
            handler(newVal) {
                const token = newVal.join('');
                this.props.form.token = token;
            }
        }
    },
    mounted() {
        this.$store.dispatch('frontendSetting/lists');
        this.phoneOrEmailChecking();
    },
    methods: {
        handleOtpInput(event, index) {
            const val = event.target.value;
            if (!/^[0-9]$/.test(val)) {
                this.otpDigits[index] = "";
                return;
            }
            this.otpDigits[index] = val;
            if (val && index < this.otpLength - 1) {
                this.$refs['otp_input_' + (index + 1)][0].focus();
            }
        },
        handleOtpBackspace(event, index) {
            if (!this.otpDigits[index] && index > 0) {
                this.otpDigits[index - 1] = "";
                this.$refs['otp_input_' + (index - 1)][0].focus();
            } else {
                this.otpDigits[index] = "";
            }
        },
        handleOtpPaste(event) {
            event.preventDefault();
            const pasteData = event.clipboardData.getData('text').trim();
            if (/^[0-9]+$/.test(pasteData)) {
                const digits = pasteData.split('').slice(0, this.otpLength);
                for (let i = 0; i < this.otpLength; i++) {
                    this.otpDigits[i] = digits[i] || "";
                }
                const focusIndex = Math.min(digits.length, this.otpLength - 1);
                this.$nextTick(() => {
                    this.$refs['otp_input_' + focusIndex][0].focus();
                });
            }
        },
        phoneOrEmailChecking: function () {
            this.loading.isActive = true;
            const otpPhone = this.$store.getters['phone'];
            const otpEmail = this.$store.getters['email'];
            if (Object.keys(otpPhone).length > 0 && otpPhone.otp.phone !== "") {
                this.props.form.phone = otpPhone.otp.phone;
                this.props.form.country_code = otpPhone.otp.country_code;
                this.props.form.email = "";
                this.loading.isActive = false;
            } else if (Object.keys(otpEmail).length > 0 && otpPhone.otp.email !== "") {
                this.props.form.email = otpPhone.otp.email;
                this.props.form.phone = "";
                this.props.form.country_code = "";
                this.loading.isActive = false;
            }
            else {
                this.$router.push({ name: 'auth.login' });
            }
            this.loading.isActive = false;
        },
        resendCodeToPhone: function () {
            try {
                this.loading.isActive = true;
                this.$store.dispatch("otpPhone", this.props.form).then((res) => {
                    this.loading.isActive = false;
                    this.errors = "";
                    alertService.success(res.data.message, 'bottom-center');
                }).catch((err) => {
                    this.loading.isActive = false;
                    this.errors = err.response?.data?.message || err.message || "An unexpected error occurred.";
                });
            } catch (err) {
                this.loading.isActive = false;
                alertService.error(err);
            }
        },
        resendCodeToEmail: function () {
            try {
                this.loading.isActive = true;
                this.$store.dispatch("otpEmail", this.props.form).then((res) => {
                    this.loading.isActive = false;
                    this.errors = "";
                    alertService.success(res.data.message, 'bottom-center');
                }).catch((err) => {
                    this.loading.isActive = false;
                    this.errors = err.response?.data?.message || err.message || "An unexpected error occurred.";
                });
            } catch (err) {
                this.loading.isActive = false;
                alertService.error(err);
            }
        },
        save: function () {
            try {
                this.loading.isActive = true;
                if (this.props.form.country_code !== "" && this.props.form.phone !== "") {
                    this.$store.dispatch("forgotPasswordVerifyPhone", this.props.form).then((res) => {
                        this.loading.isActive = false;
                        alertService.success(res.data.message, 'bottom-center');
                        this.props.form = {
                            email: "",
                            phone: "",
                            token: "",
                            country_code: "",
                        };
                        this.errors = '';
                        this.$router.push({
                            name: "auth.resetPassword",
                        });
                    }).catch((err) => {
                        this.loading.isActive = false;
                        this.errors = err.response?.data?.message || err.message || "An unexpected error occurred.";
                    });
                } else {
                    this.$store.dispatch("forgotPasswordVerifyEmail", this.props.form).then((res) => {
                        this.loading.isActive = false;
                        alertService.success(res.data.message, 'bottom-center');
                        this.props.form = {
                            email: "",
                            phone: "",
                            token: "",
                            country_code: "",
                        };
                        this.errors = '';
                        this.$router.push({
                            name: "auth.resetPassword",
                        });
                    }).catch((err) => {
                        this.loading.isActive = false;
                        this.errors = err.response?.data?.message || err.message || "An unexpected error occurred.";
                    });
                }

            } catch (err) {
                this.loading.isActive = false;
                alertService.error(err);
            }
        },
    },
}
</script>
