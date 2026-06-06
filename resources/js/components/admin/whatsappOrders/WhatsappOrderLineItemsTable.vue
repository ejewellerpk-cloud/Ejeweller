<template>
    <div class="db-card mb-6">
        <div class="db-card-header">
            <h3 class="db-card-title">{{ $t('label.products') }}</h3>
            <span v-if="carts.length" class="text-sm text-gray-500">
                {{ totalQuantity }} {{ $t('label.products') }}
            </span>
        </div>
        <div class="db-card-body">
            <div v-if="carts.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                <img class="mb-4 w-40" :src="setting.image_cart" alt="empty" />
                <p class="text-sm text-gray-500">{{ $t('message.no_data_found') }}</p>
            </div>

            <div v-else class="db-table-responsive rounded-md border">
                <table class="db-table">
                    <thead class="db-table-head border-t-0">
                        <tr class="db-table-head-tr">
                            <th class="db-table-head-th min-w-[220px]">{{ $t('label.product') }}</th>
                            <th class="db-table-head-th">{{ $t('label.price') }}</th>
                            <th class="db-table-head-th">{{ $t('label.quantity') }}</th>
                            <th class="db-table-head-th">{{ $t('label.sub_total') }}</th>
                            <th class="db-table-head-th">{{ $t('label.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="db-table-body">
                        <tr v-for="(cart, index) in carts" :key="index" class="db-table-body-tr">
                            <td class="db-table-body-td">
                                <div class="flex items-center gap-3">
                                    <img :src="cart.image" :alt="cart.name"
                                        class="h-12 w-12 flex-shrink-0 rounded-md object-cover" />
                                    <div class="min-w-0">
                                        <p class="truncate font-medium capitalize">{{ cart.name }}</p>
                                        <p v-if="cart.variation_id > 0" class="text-xs text-gray-500 capitalize">
                                            {{ cart.variation_names }}
                                        </p>
                                        <p class="text-xs text-gray-400">SKU: {{ cart.sku }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="db-table-body-td whitespace-nowrap">
                                <span class="font-medium">{{ linePrice(cart.price) }}</span>
                                <del v-if="cart.discount > 0" class="ml-1 text-xs text-[#FF6262]">
                                    {{ linePrice(cart.old_price) }}
                                </del>
                            </td>
                            <td class="db-table-body-td">
                                <div class="inline-flex items-center gap-1 rounded-full bg-[#F7F7FC] p-1">
                                    <button type="button" @click="$emit('decrement', index, cart)"
                                        :class="cart.quantity === 1 ? 'cursor-not-allowed opacity-50' : ''"
                                        class="lab-fill-circle-minus text-lg leading-none transition hover:text-primary"></button>
                                    <input type="number" v-model="cart.quantity" min="1"
                                        class="h-5 w-12 border-0 bg-transparent text-center text-sm font-medium focus:outline-none"
                                        @keypress="$emit('onlyNumber', $event)"
                                        @keyup="$emit('quantityUp', index, cart, $event)" />
                                    <button type="button" @click="$emit('increment', index, cart)"
                                        :class="cart.quantity >= cart.stock ? 'cursor-not-allowed opacity-50' : ''"
                                        class="lab-fill-circle-plus text-lg leading-none transition hover:text-primary"></button>
                                </div>
                                <p class="mt-1 text-[11px] text-gray-400">{{ $t('label.stock') }}: {{ cart.stock }}</p>
                            </td>
                            <td class="db-table-body-td whitespace-nowrap font-medium">
                                {{ linePrice(cart.price * cart.quantity) }}
                            </td>
                            <td class="db-table-body-td">
                                <button type="button"
                                    class="inline-flex items-center gap-1 rounded-full bg-[#FFF4F4] px-2.5 py-1 text-xs font-medium text-[#E93C3C] transition hover:bg-[#E93C3C] hover:text-white"
                                    @click="$emit('remove', index)">
                                    <i class="lab-line-trash"></i>
                                    <span class="hidden sm:inline">{{ $t('button.remove') }}</span>
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
import appService from "../../../services/appService";

export default {
    name: "WhatsappOrderLineItemsTable",
    props: {
        carts: {
            type: Array,
            default: () => [],
        },
        setting: {
            type: Object,
            required: true,
        },
    },
    emits: ["increment", "decrement", "quantityUp", "remove", "onlyNumber"],
    computed: {
        totalQuantity() {
            return this.carts.reduce((sum, cart) => sum + Number(cart.quantity || 0), 0);
        },
    },
    methods: {
        linePrice(amount) {
            return appService.currencyFormat(
                amount,
                this.setting.site_digit_after_decimal_point,
                this.setting.site_default_currency_symbol,
                this.setting.site_currency_position
            );
        },
    },
};
</script>
