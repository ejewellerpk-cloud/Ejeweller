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
        class="coupon-modal-overlay fixed inset-0 z-[200] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/50 transition-all duration-300 opacity-0 invisible">
        <div
            class="coupon-modal-panel w-full sm:max-w-md bg-white transition-all duration-300 rounded-t-2xl sm:rounded-2xl shadow-2xl">
            <div class="flex items-center justify-between gap-3 py-4 px-4 sm:px-5 border-b border-gray-100 shrink-0">
                <div class="min-w-0">
                    <h3 class="text-base sm:text-lg font-bold capitalize text-heading">{{ $t('label.coupon_code') }}</h3>
                    <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5">{{ $t('message.get_discount_with_your_order') }}</p>
                </div>
                <button @click.prevent="closeCouponModal" type="button"
                    class="shrink-0 w-9 h-9 rounded-full border border-gray-100 flex items-center justify-center text-[#E93C3C] hover:bg-red-50 transition-colors"
                    :aria-label="$t('button.close')">
                    <i class="lab-line-circle-cross text-lg"></i>
                </button>
            </div>

            <div class="px-4 sm:px-5 py-4 pb-5 sm:pb-6">
                <form @submit.prevent="couponChecking" class="w-full">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 block">
                        {{ $t('label.coupon_code') }}
                    </label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input
                            :class="error ? 'invalid' : ''"
                            type="text"
                            v-model="code"
                            autocomplete="off"
                            :placeholder="$t('label.coupon_code')"
                            class="h-11 w-full min-w-0 flex-1 px-3 rounded-lg border border-[#D9DBE9] text-sm focus:outline-none focus:border-primary/50" />
                        <button
                            type="submit"
                            class="h-11 w-full sm:w-auto sm:min-w-[100px] px-5 rounded-lg capitalize font-semibold text-white bg-primary hover:bg-primary/95 transition-colors shrink-0">
                            {{ $t('button.apply') }}
                        </button>
                    </div>
                    <small class="block w-full pt-1.5 text-sm text-red-500" v-if="error">{{ error }}</small>
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

<style scoped>
@media (max-width: 639px) {
    .coupon-modal-overlay.modal-active {
        align-items: flex-end;
    }

    .coupon-modal-overlay.modal-active .coupon-modal-panel {
        margin-bottom: 0;
    }
}
</style>
