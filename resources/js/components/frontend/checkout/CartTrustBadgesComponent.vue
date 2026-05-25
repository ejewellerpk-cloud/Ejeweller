<template>
    <div class="cart-trust-badges rounded-xl bg-[#F4F8F5] border border-[#E2EDE6] px-2 py-2 sm:px-3 sm:py-2.5 mt-3">
        <h3 class="text-[11px] sm:text-xs font-bold text-heading mb-2 leading-snug">
            {{ $t('label.shop_safely_and_sustainably') }}
        </h3>

        <div class="grid grid-cols-4 gap-1 sm:gap-1.5">
            <div v-for="item in trustItems" :key="item.key"
                class="flex flex-col items-center text-center gap-0.5">
                <div class="w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center text-[#1B6B3A]">
                    <span v-if="item.key === 'payment'" class="relative inline-flex items-center justify-center w-4 h-4 sm:w-5 sm:h-5">
                        <i class="fa-solid fa-shield text-sm sm:text-base leading-none"></i>
                        <span class="absolute inset-0 flex items-center justify-center text-[8px] sm:text-[9px] font-extrabold leading-none pt-0.5">$</span>
                    </span>
                    <i v-else :class="item.icon" class="text-sm sm:text-base"></i>
                </div>
                <p class="text-[7px] sm:text-[9px] font-medium text-[#3D5C48] leading-tight">
                    {{ $t(item.labelKey) }}
                </p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "CartTrustBadgesComponent",
    data() {
        return {
            trustItems: [
                { key: "delivery", icon: "fa-solid fa-truck-fast", labelKey: "label.secure_delivery_guarantee" },
                { key: "payment", icon: "fa-solid fa-shield", labelKey: "label.secure_your_payment" },
                { key: "privacy", icon: "fa-solid fa-lock", labelKey: "label.security_and_privacy" },
                { key: "support", icon: "fa-solid fa-headset", labelKey: "label.customer_support" },
            ],
        };
    },
};
</script>
