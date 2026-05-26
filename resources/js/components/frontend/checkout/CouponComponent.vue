<template>
    <LoadingComponent v-if="loading.isActive" :props="loading" skeleton="page" />
    <div v-if="Object.keys(cartCoupon).length !== 0"
        class="mb-6 rounded-2xl border border-success flex items-center gap-3 p-4">
        <div class="relative flex-shrink-0">
            <i class="lab-fill-shape lab-font-size-2xl opacity-[0.3] text-success"></i>
            <i
                class="lab-line-percent lab-font-size-8 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-success"></i>
        </div>
        <div class="flex-auto overflow-hidden min-w-0">
            <h4
                class="font-semibold leading-5 mb-1 whitespace-nowrap overflow-hidden text-ellipsis capitalize text-success">
                {{ $t('message.coupon_applied') }}</h4>
            <h5 class="text-xs font-normal whitespace-nowrap overflow-hidden text-ellipsis text-gray-600">
                {{ $t('message.you_saved', { amount: cartCoupon.currency_discount }) }}
            </h5>
        </div>
        <button type="button" @click.prevent="destroyCoupon" class="lab-line-trash lab-font-size-xl text-danger shrink-0 p-1"
            :aria-label="$t('button.delete')"></button>
    </div>

    <div v-else @click.prevent="openCouponModal"
        class="mb-6 rounded-2xl border border-focus flex items-center gap-3 p-4 cursor-pointer hover:border-primary/40 transition-colors">
        <div class="relative flex-shrink-0">
            <i class="lab lab-fill-shape lab-font-size-2xl opacity-[0.3] text-focus"></i>
            <i
                class="lab lab-line-percent lab-font-size-8 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-focus"></i>
        </div>
        <div class="flex-auto overflow-hidden min-w-0">
            <h4
                class="font-semibold leading-5 mb-1 whitespace-nowrap overflow-hidden text-ellipsis capitalize text-focus">
                {{ $t('message.apply_coupon') }}</h4>
            <h5 class="text-xs font-normal text-gray-600 line-clamp-2">
                {{ $t('message.get_discount_with_your_order') }}
            </h5>
        </div>
        <i class="lab lab-line-chevron-right rtl:rotate-180 lab-font-size-2xl text-focus shrink-0"></i>
    </div>

    <div id="coupon-modal"
        class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50 transition-all duration-300 opacity-0 invisible"
        @click.self="closeCouponModal">
        <div
            class="w-full max-w-[400px] mx-auto bg-white rounded-2xl shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between gap-3 py-4 px-5 border-b border-slate-100">
                <div class="min-w-0">
                    <h3 class="text-lg font-bold capitalize text-heading">{{ $t('label.coupon_code') }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $t('message.get_discount_with_your_order') }}</p>
                </div>
                <button @click.prevent="closeCouponModal" type="button"
                    class="shrink-0 w-9 h-9 rounded-full border border-gray-100 flex items-center justify-center text-[#E93C3C] hover:bg-red-50 transition-colors"
                    :aria-label="$t('button.close')">
                    <i class="lab-line-circle-cross text-lg"></i>
                </button>
            </div>

            <div class="p-5">
                <form @submit.prevent="couponChecking" class="w-full">
                    <label for="coupon-code-input" class="text-sm font-medium capitalize mb-1.5 field-title block">
                        {{ $t('label.coupon_code') }}
                    </label>
                    <input
                        id="coupon-code-input"
                        :class="error ? 'invalid' : ''"
                        type="text"
                        v-model="code"
                        autocomplete="off"
                        :placeholder="$t('label.coupon_code')"
                        class="w-full h-12 px-4 rounded-lg text-base text-heading placeholder:text-gray-400 border border-[#D9DBE9] bg-white hover:border-primary/30 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/15 transition-all duration-300" />
                    <small class="db-field-alert block mt-1.5" v-if="error">{{ error }}</small>
                    <button
                        type="submit"
                        class="mt-4 w-full h-12 rounded-lg capitalize font-semibold text-white bg-primary hover:bg-primary/95 active:scale-[0.99] transition-all duration-300">
                        {{ $t('button.apply') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import targetService from "../../../services/targetService";
import alertService from "../../../services/alertService";
import LoadingComponent from "../components/LoadingComponent.vue";

export default {
    name: "CouponComponent",
    components: { LoadingComponent },
    data() {
        return {
            loading: {
                isActive: false
            },
            code: null,
            error: ""
        }
    },
    computed: {
        subtotal: function () {
            return this.$store.getters['frontendCart/subtotal'];
        },
        cartCoupon: function () {
            return this.$store.getters['frontendCart/coupon'];
        }
    },
    methods: {
        openCouponModal: function () {
            this.showTarget('coupon-modal', 'modal-active');
            this.$nextTick(() => {
                document.getElementById('coupon-code-input')?.focus();
            });
        },
        closeCouponModal: function () {
            this.hideTarget('coupon-modal', 'modal-active');
        },
        showTarget: function (id, cClass) {
            targetService.showTarget(id, cClass);
        },
        hideTarget: function (id, cClass) {
            this.code = null;
            this.error = "";
            targetService.hideTarget(id, cClass);
        },
        couponChecking: function () {
            if (!this.code || !String(this.code).trim()) {
                this.error = this.$t('message.coupon_not_exist') || 'Please enter a coupon code.';
                return;
            }
            this.loading.isActive = true;
            this.$store.dispatch('frontendCoupon/checking', {
                total: this.subtotal,
                code: String(this.code).trim()
            }).then(res => {
                this.error = "";
                this.$store.dispatch("frontendCart/coupon", res.data.data);
                this.loading.isActive = false;
                this.closeCouponModal();
                alertService.success(this.$t('message.coupon_add'));
            }).catch((err) => {
                this.loading.isActive = false;
                this.error = err.response?.data?.message || this.$t('message.coupon_not_exist');
            });
        },
        destroyCoupon: function () {
            this.loading.isActive = true;
            this.$store.dispatch("frontendCart/destroyCoupon").then(() => {
                this.code = null;
                this.loading.isActive = false;
                alertService.success(this.$t('message.coupon_delete'));
            }).catch((err) => {
                this.loading.isActive = false;
                alertService.error(err);
            });
        }
    }
}
</script>
