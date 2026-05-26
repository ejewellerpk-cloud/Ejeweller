<template>
    <div class="bg-white rounded-2xl shadow-card">
        <div class="p-4 border-b border-[#EFF0F6]">
            <h3 class="text-lg font-semibold capitalize">{{ $t('label.order_summery') }}</h3>
        </div>

        <ul v-if="cartItems.length > 0" class="flex flex-col gap-3 p-4 border-b border-[#EFF0F6] max-h-52 overflow-y-auto thin-scrolling">
            <li v-for="(item, index) in cartItems" :key="index" class="flex items-start gap-2.5">
                <img :src="item.image" :alt="item.name" class="w-12 h-12 rounded-lg object-cover shrink-0 bg-gray-50 border border-gray-100" />
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold capitalize leading-tight line-clamp-2">{{ item.name }}</p>
                    <p v-if="item.variation_id > 0" class="text-[11px] text-gray-500 truncate">{{ item.variation_names }}</p>
                    <p class="text-[11px] text-gray-500 mt-0.5">{{ $t('label.quantity') }}: {{ item.quantity }}</p>
                </div>
                <span class="text-sm font-semibold text-primary shrink-0 leading-tight">
                    {{ currencyFormat(lineTotal(item), setting.site_digit_after_decimal_point,
                        setting.site_default_currency_symbol, setting.site_currency_position) }}
                </span>
            </li>
        </ul>

        <ul class="flex flex-col gap-3 p-4 border-b border-[#EFF0F6]">
            <li class="flex items-center justify-between">
                <span class="capitalize">{{ $t('label.subtotal') }}</span>
                <span class="font-medium">{{ currencyFormat(subtotal, setting.site_digit_after_decimal_point,
                    setting.site_default_currency_symbol, setting.site_currency_position) }}</span>
            </li>
            <li class="flex items-center justify-between">
                <span class="capitalize">{{ $t('label.tax') }}</span>
                <span class="font-medium">{{ currencyFormat(totalTax, setting.site_digit_after_decimal_point,
                    setting.site_default_currency_symbol, setting.site_currency_position) }}</span>
            </li>
            <li class="flex items-center justify-between">
                <span class="capitalize">{{ $t('label.shipping_charge') }}</span>
                <span class="font-medium">{{ currencyFormat(shippingCharge, setting.site_digit_after_decimal_point,
                    setting.site_default_currency_symbol, setting.site_currency_position) }}</span>
            </li>
            <li class="flex items-center justify-between">
                <span class="capitalize">{{ $t('label.discount') }}</span>
                <span class="font-medium">{{ currencyFormat(discount, setting.site_digit_after_decimal_point,
                    setting.site_default_currency_symbol, setting.site_currency_position) }}</span>
            </li>
        </ul>
        <div class="p-4">
            <dl class="flex items-center justify-between">
                <dt class="font-semibold capitalize text-base text-gray-800">{{ $t('label.total') }}</dt>
                <dd class="font-bold text-lg text-primary">{{ currencyFormat(total, setting.site_digit_after_decimal_point,
                    setting.site_default_currency_symbol, setting.site_currency_position) }}</dd>
            </dl>

            <!-- Total Savings Alert Banner (Premium Highlighted Box) -->
            <div v-if="totalSavings > 0" class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 flex items-center justify-between shadow-sm mt-3 animate-pulse">
                <div class="flex items-center gap-1.5 text-emerald-800 font-bold text-xs">
                    <i class="fa-solid fa-gift text-emerald-600 text-sm"></i>
                    <span>Total Savings on this order:</span>
                </div>
                <span class="text-sm font-black text-emerald-700 font-sans">
                    {{ currencyFormat(totalSavings, setting.site_digit_after_decimal_point, setting.site_default_currency_symbol, setting.site_currency_position) }}
                </span>
            </div>
        </div>
    </div>
</template>

<script>
import appService from "../../../services/appService";

export default {
    name: "SummeryComponent",
    computed: {
        setting: function () {
            return this.$store.getters['frontendSetting/lists'];
        },
        subtotal: function () {
            return this.$store.getters['frontendCart/subtotal'];
        },
        discount: function () {
            return this.$store.getters['frontendCart/discount'];
        },
        totalTax: function () {
            return this.$store.getters['frontendCart/totalTax'];
        },
        shippingCharge: function () {
            return this.$store.getters['frontendCart/shippingCharge'];
        },
        total: function () {
            return this.$store.getters['frontendCart/total'];
        },
        totalSavings: function () {
            return this.$store.getters['frontendCart/totalSavings'];
        },
        cartItems: function () {
            return this.$store.getters['frontendCart/lists'] || [];
        }
    },
    methods: {
        currencyFormat(amount, decimal, currency, position) {
            return appService.currencyFormat(amount, decimal, currency, position);
        },
        lineTotal: function (item) {
            if (item.total_price != null && item.total_price !== '') {
                return item.total_price;
            }
            const qty = parseInt(item.quantity, 10) || 1;
            return (parseFloat(item.price) || 0) * qty;
        }
    }
}
</script>