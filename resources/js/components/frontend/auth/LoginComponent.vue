<template>
    <LoadingComponent :props="loading" />
    <div class="w-full max-w-3xl mx-auto rounded-2xl flex overflow-hidden gap-y-6 bg-white shadow-card mb-24 !sm:mb-0">
        <img :src="APP_URL + '/images/required/auth.jpg'" alt="banners"
            class="w-full hidden sm:block sm:max-w-xs md:max-w-sm flex-shrink-0">
        <form class="w-full p-6" @submit.prevent="handleSubmit">
            <div class="text-center mb-8">
                <h3 class="capitalize text-2xl mb-2 font-bold text-primary">{{ $t('label.sign_in') }}</h3>
                <p class="font-medium">{{ $t('message.continue_shopping') }}</p>
                <div v-if="errors.validation"
                    class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 mb-5 rounded relative" role="alert">
                    <span class="block sm:inline">{{ errors.validation }}</span>
                    <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" @click="close">
                        <i class="lab lab-close-circle-line margin-top-5-px"></i>
                    </span>
                </div>
                <!-- Login Method Tabs -->
                <div v-if="setting.otp_login_status == activityEnum.ENABLE" class="flex justify-center gap-6 mt-4 border-b border-gray-100 pb-2">
                    <button type="button" @click="setLoginMethod('password')" :class="loginMethod === 'password' ? 'text-primary border-b-2 border-primary font-bold' : 'text-slate-400 font-medium'" class="pb-2 text-sm capitalize transition-all duration-300">
                        {{ $t('label.login_with_password') || 'Login with Password' }}
                    </button>
                    <button type="button" @click="setLoginMethod('otp')" :class="loginMethod === 'otp' ? 'text-primary border-b-2 border-primary font-bold' : 'text-slate-400 font-medium'" class="pb-2 text-sm capitalize transition-all duration-300">
                        {{ $t('label.login_with_otp') || 'Login with OTP' }}
                    </button>
                </div>
            </div>

            <div :class="!toggleValue ? 'mb-6' : ''">
                <div class="flex items-center justify-between">
                    <label :for="!toggleValue ? 'formEmail' : 'phone'"
                        class="text-sm font-medium capitalize mb-1 field-title required">
                        {{ inputLabel }}
                    </label>
                    <button type="button" class="text-sm font-medium capitalize mb-1 underline text-primary"
                        @click="handleField()">
                        {{ inputButton }}
                    </button>
                </div>
                <div v-if="toggleValue" :class="errors.phone ? 'invalid' : ''"
                    class="flex items-center gap-1.5 px-4 h-12 rounded-lg border border-[#D9DBE9] hover:border-primary/30 focus-within:border-primary/30 transition-all duration-500">
                    <div class="w-fit flex-shrink-0 dropdown-group">
                        <button type="button" class="flex items-center gap-1 dropdown-btn">
                            {{ flag }}
                            <span class="whitespace-nowrap flex-shrink-0 text-xs">{{
                                form.country_code
                                }}</span>
                            <i class="fa-solid fa-caret-down text-xs"></i>
                        </button>
                        <ul
                            class="p-1.5 w-24 rounded-lg shadow-xl absolute top-8 -left-4 z-10 border border-gray-200 bg-white scale-y-0 origin-top dropdown-list !h-52 !overflow-x-hidden !overflow-y-auto thin-scrolling">
                            <li v-for="countryCode in countryCodes" @click="countryCodeChange(countryCode)"
                                class="flex items-center gap-2 p-1.5 rounded-md cursor-pointer hover:bg-gray-100">
                                {{ countryCode.flag_emoji }}
                                <span class="whitespace-nowrap text-xs">{{ countryCode.calling_code }}</span>
                            </li>
                        </ul>

                    </div>
                    <input v-model="form.phone" v-on:keypress="phoneNumber($event)" v-bind:class="errors.phone
                        ? 'invalid' : ''" type="text" id="phone" class="pl-2 text-sm w-full h-full" />
                </div>
                <input v-if="!toggleValue" v-model="form.email" :class="errors.email ? 'invalid' : ''" id="formEmail"
                    type="text"
                    class="w-full h-12 px-4 rounded-lg text-base border border-[#D9DBE9] hover:border-primary/30 focus-within:border-primary/30 transition-all duration-500" />
                <small class="db-field-alert" v-if="errors.email_or_phone">{{ errors.email_or_phone }}</small>
                <span v-else>
                    <small class="db-field-alert" v-if="errors.phone && toggleValue">{{ errors.phone[0] }}</small>
                    <small class="db-field-alert" v-if="errors.email && !toggleValue">{{ errors.email[0] }}</small>
                </span>
            </div>

            <!-- Password Input (Only for password login) -->
            <div v-if="loginMethod === 'password'" class="mb-3">
                <label for="formPassword" class="text-sm font-medium capitalize mb-1 field-title required">
                    {{ $t('label.password') }}
                </label>
                <div class="relative w-full">
                    <input v-model="form.password" :class="errors.password ? 'invalid' : ''" id="formPassword"
                        :type="showPassword ? 'text' : 'password'"
                        class="w-full h-12 pl-4 pr-12 rounded-lg text-base border border-[#D9DBE9] hover:border-primary/30 focus-within:border-primary/30 transition-all duration-500" />
                    <button type="button" @click="showPassword = !showPassword" class="absolute top-1/2 right-4 -translate-y-1/2 text-slate-400 hover:text-primary transition-all duration-300">
                        <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                    </button>
                </div>
                <small class="db-field-alert" v-if="errors.password">{{ errors.password[0] }}</small>
            </div>

            <!-- OTP Input (Only for OTP login, and after OTP is sent) -->
            <div v-if="loginMethod === 'otp' && otpSent" class="mb-3">
                <label class="text-sm font-medium capitalize mb-1 field-title required">
                    {{ $t('label.enter_otp') || 'Enter OTP' }}
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

                <small class="db-field-alert" v-if="errors.token">{{ errors.token ? (Array.isArray(errors.token) ? errors.token[0] : errors.token) : '' }}</small>
                <button type="button" @click="sendOtp" class="text-xs text-primary underline mt-2 block">
                    {{ $t('button.resend_code') || 'Resend Code' }}
                </button>
            </div>

            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="formRemember" class="custom-checkbox">
                    <label for="formRemember" class="text-sm -mb-0.5 capitalize cursor-pointer whitespace-nowrap">{{
                        $t('label.remember_me')
                        }}</label>
                </div>
                <router-link v-if="loginMethod === 'password'" :to="{ name: 'auth.forgotPassword' }" class="field-label text-primary">
                    {{ $t('label.forgot_password') }}
                </router-link>
            </div>

            <button type="submit"
                class="font-bold text-center w-full h-12 leading-12 rounded-full bg-primary text-white capitalize mb-6">
                {{ loginButtonText }}
            </button>
            <div v-if="socialLoginProviders.length > 0" class="flex items-center gap-3 mb-4">
                <span class="w-full h-[1px] bg-gradient-to-r from-[#FFFFFF] to-[#D9DBE9]"></span>
                <span class="text-sm">Or</span>
                <span class="w-full h-[1px] bg-gradient-to-l from-[#FFFFFF] to-[#D9DBE9]"></span>
            </div>
            <div v-if="socialLoginProviders.length > 0" class="flex justify-center flex-wrap gap-[10px] mb-6">
                <div v-for="socialLoginProvider in socialLoginProviders" :key="socialLoginProvider.id" @click.prevent="socialLogin(socialLoginProvider.slug)" class="flex items-center justify-center gap-1.5 bg-[#F7F7FC] px-3 h-10 rounded-lg cursor-pointer">
                    <img class="h-6 w-6 rounded-full" :src="socialLoginProvider.image" :alt="socialLoginProvider.name"  />
                    <span class="font-medium text-text">{{ $t('label.' + socialLoginProvider.slug) }}</span>
                </div>
            </div>
            <div class="flex items-center justify-center gap-1.5">
                <span class="font-medium text-text">{{ $t('message.dont_have_account') }}</span>
                <router-link class="capitalize font-bold text-primary" :to="{ name: 'auth.signup' }">
                    {{ $t('label.sign_up') }}
                </router-link>
            </div>

            <div v-if="demo === 'true' || demo === 'TRUE' || demo === 'True' || demo === '1' || demo === 1"
                class="mt-6">
                <h2 class="mb-6 text-center text-lg font-medium text-heading">{{ $t('message.for_quick_demo') }}</h2>
                <nav class="grid grid-cols-2 gap-3">
                    <button type="button" @click.prevent="setupCredit('admin')"
                        class="click-to-prop w-full h-10 leading-10 rounded-lg text-center text-sm capitalize text-white bg-orange-500"
                        id="adminClick">
                        {{ $t('label.admin') }}
                    </button>
                    <button type="button" @click.prevent="setupCredit('customer')"
                        class="click-to-prop w-full h-10 leading-10 rounded-lg text-center text-sm capitalize text-white bg-emerald-500"
                        id="customerClick">
                        {{ $t('label.customer') }}
                    </button>
                    <button type="button" @click.prevent="setupCredit('manager')"
                        class="click-to-prop w-full h-10 leading-10 rounded-lg text-center text-sm capitalize text-white bg-sky-600"
                        id="branchManagerClick">
                        {{ $t('label.manager') }}
                    </button>
                    <button type="button" @click.prevent="setupCredit('posOperator')"
                        class="click-to-prop w-full h-10 leading-10 rounded-lg text-center text-sm capitalize text-white bg-purple-500"
                        id="posOperatorClick">
                        {{ $t('label.pos_operator') }}
                    </button>
                </nav>
            </div>
        </form>
    </div>
</template>

<script>
import router from "../../../router";
import LoadingComponent from "../components/LoadingComponent";
import alertService from "../../../services/alertService";
import statusEnum from "../../../enums/modules/statusEnum";
import appService from "../../../services/appService";
import ENV from "../../../config/env";
import activityEnum from "../../../enums/modules/activityEnum";

export default {
    name: "LoginComponent",
    components: { LoadingComponent },
    data() {
        return {
            loading: {
                isActive: false,
            },
            form: {
                email: "",
                phone: "",
                country_code: "",
                password: "",
                token: ""
            },
            socialLoginProviders: [],
            flag: "",
            errors: {},
            permissions: {},
            firstMenu: null,
            demo: ENV.DEMO,
            APP_URL: ENV.API_URL,
            toggleValue: false,
            inputLabel: this.$t('label.email'),
            inputButton: this.$t('label.use_phone_instead'),
            showPassword: false,
            loginMethod: "password",
            otpSent: false,
            activityEnum: activityEnum,
            otpDigits: []
        }
    },
    computed: {
        carts: function () {
            return this.$store.getters['frontendCart/lists'];
        },
        countryCodes: function () {
            return this.$store.getters['frontendCountryCode/lists'];
        },
        setting: function () {
            return this.$store.getters['frontendSetting/lists'];
        },
        loginButtonText: function () {
            if (this.loginMethod === 'password') {
                return this.$t('label.sign_in');
            } else {
                return this.otpSent ? (this.$t('button.verify_login') || 'Verify & Login') : (this.$t('button.send_otp') || 'Send OTP');
            }
        },
        otpLength: function () {
            const limit = this.setting?.otp_digit_limit;
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
                this.form.token = token;
            }
        }
    },
    mounted() {
        this.loading.isActive = true;
        this.$store.dispatch('frontendCountryCode/lists');
        this.$store.dispatch('frontendSocialLogin/lists', { status: statusEnum.ACTIVE }).then(res => {
            this.socialLoginProviders = res.data.data;
        });
        this.$store.dispatch('frontendSetting/lists').then(res => {
            this.$store.dispatch('frontendCountryCode/show', res.data.data.company_country_code).then(res => {
                this.form.country_code = res.data.data.calling_code;
                this.flag = res.data.data.flag_emoji;

                this.loading.isActive = false;
            }).catch((err) => {
                this.loading.isActive = false;
            });
        }).catch((err) => {
            this.loading.isActive = false;
        });
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
        phoneNumber(e) {
            return appService.phoneNumber(e);
        },
        handleSubmit: function () {
            if (this.loginMethod === 'password') {
                this.login();
            } else {
                if (!this.otpSent) {
                    this.sendOtp();
                } else {
                    this.loginWithOtp();
                }
            }
        },
        setLoginMethod: function (method) {
            this.loginMethod = method;
            this.errors = {};
            this.form.password = "";
            this.form.token = "";
            this.otpSent = false;
        },
        sendOtp: function () {
            try {
                this.loading.isActive = true;
                this.errors = {};
                this.$store.dispatch('otpLoginSend', this.form).then((res) => {
                    this.loading.isActive = false;
                    this.otpSent = true;
                    alertService.success(res.data.message);
                }).catch((err) => {
                    this.loading.isActive = false;
                    if (err.response && err.response.data) {
                        this.errors = err.response.data.errors || {};
                        if (err.response.data.message && !this.errors.validation) {
                            this.errors.validation = err.response.data.message;
                        }
                    } else {
                        this.errors = { validation: err.message || "An unexpected error occurred." };
                    }
                });
            } catch (err) {
                this.loading.isActive = false;
            }
        },
        loginWithOtp: function () {
            try {
                this.loading.isActive = true;
                this.errors = {};
                this.$store.dispatch('otpLoginVerify', this.form).then((res) => {
                    this.loading.isActive = false;
                    alertService.success(res.data.message);
                    this.checkPendingWishlist();
                    this.$store.dispatch("frontendWishlist/lists").then().catch();
                    if (this.carts.length > 0) {
                        router.push({ name: "frontend.checkout" });
                    } else {
                        router.push({ name: "frontend.home" });
                    }
                    setTimeout(() => {
                        appService.recursiveRouter(router.options.routes, this.$store.getters.authPermission);
                    }, 1000);
                }).catch((err) => {
                    this.loading.isActive = false;
                    if (err.response && err.response.data) {
                        this.errors = err.response.data.errors || {};
                        if (err.response.data.message && !this.errors.validation) {
                            this.errors.validation = err.response.data.message;
                        }
                    } else {
                        this.errors = { validation: err.message || "An unexpected error occurred." };
                    }
                });
            } catch (err) {
                this.loading.isActive = false;
            }
        },
        login: function () {
            try {
                this.loading.isActive = true;
                this.$store.dispatch('login', this.form).then((res) => {
                    this.loading.isActive = false;
                    alertService.success(res.data.message);
                    this.checkPendingWishlist();
                    this.$store.dispatch("frontendWishlist/lists").then().catch();
                    if (this.carts.length > 0) {
                        router.push({ name: "frontend.checkout" });
                    } else {
                        router.push({ name: "frontend.home" });
                    }
                    setTimeout(() => {
                        appService.recursiveRouter(router.options.routes, this.$store.getters.authPermission);
                    }, 1000);
                }).catch((err) => {
                    this.loading.isActive = false;
                    if (err.response && err.response.data) {
                        this.errors = err.response.data.errors || {};
                        if (err.response.data.message && !this.errors.validation) {
                            this.errors.validation = err.response.data.message;
                        }
                    } else {
                        this.errors = { validation: err.message || "An unexpected error occurred." };
                    }
                })
            } catch (err) {
                this.loading.isActive = false;
            }
        },
        checkPendingWishlist: function () {
            const localWish = JSON.parse(localStorage.getItem('local_wishlist') || '[]');
            if (localWish.length > 0) {
                const promises = localWish.map(prodId => {
                    return this.$store.dispatch("frontendWishlist/toggle", {
                        product_id: parseInt(prodId),
                        toggle: true
                    }).catch(() => {});
                });
                Promise.all(promises).then(() => {
                    localStorage.removeItem('local_wishlist');
                });
            }

            const pendingWishlistProductId = localStorage.getItem('pending_wishlist_product_id');
            if (pendingWishlistProductId) {
                this.$store.dispatch("frontendWishlist/toggle", {
                    product_id: parseInt(pendingWishlistProductId),
                    toggle: true
                }).then(() => {
                    localStorage.removeItem('pending_wishlist_product_id');
                }).catch(() => {
                    localStorage.removeItem('pending_wishlist_product_id');
                });
            }
        },
        handleField: function () {
            this.toggleValue = !this.toggleValue

            if (this.toggleValue) {
                this.form.email = "";
                this.inputLabel = this.$t('label.phone');
                this.inputButton = this.$t('label.use_email_instead');
            } else {
                this.form.phone = "";
                this.inputLabel = this.$t('label.email');
                this.inputButton = this.$t('label.use_phone_instead');
            }
        },
        countryCodeChange: function (e) {
            this.flag = e.flag_emoji;
            this.form.country_code = e.calling_code;
        },
        close: function () {
            this.errors = {}
        },
        socialLogin: function (provider) {
            this.loading.isActive = true;
            this.$store.dispatch("socialLogin", provider).then((res) => {
                window.location.href = res.data.url;
            }).catch((err) => {
                this.loading.isActive = false;
                alertService.error(err.response.data.message);
            });
        },
        setupCredit: function (e) {
            if (e === 'admin') {
                this.form.email = 'admin@example.com';
                this.form.password = '123456';
            } else if (e === 'customer') {
                this.form.email = 'customer@example.com';
                this.form.password = '123456';
            } else if (e === 'manager') {
                this.form.email = 'manager@example.com';
                this.form.password = '123456';
            } else if (e === 'posOperator') {
                this.form.email = 'posoperator@example.com';
                this.form.password = '123456';
            }
        }
    }
}
</script>