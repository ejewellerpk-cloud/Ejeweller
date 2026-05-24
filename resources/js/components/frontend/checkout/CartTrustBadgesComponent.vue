<template>
    <div class="cart-trust-badges relative overflow-hidden rounded-2xl bg-[#F4F8F5] border border-[#E2EDE6] px-3 py-4 sm:px-4 sm:py-5 mt-4">
        <div class="pointer-events-none absolute -right-6 -bottom-4 opacity-[0.07] text-[#1B6B3A]">
            <i class="fa-solid fa-shield-check text-[120px] sm:text-[140px]"></i>
        </div>

        <h3 class="relative z-10 text-sm sm:text-base font-bold text-heading mb-4 leading-snug">
            {{ $t('label.shop_safely_and_sustainably') }}
        </h3>

        <div class="relative z-10 grid grid-cols-4 gap-2 sm:gap-3">
            <div v-for="item in trustItems" :key="item.key"
                class="flex flex-col items-center text-center gap-1.5 sm:gap-2">
                <div class="w-9 h-9 sm:w-10 sm:h-10 flex items-center justify-center text-[#1B6B3A]">
                    <span v-if="item.key === 'payment'" class="relative inline-flex items-center justify-center w-6 h-7 sm:w-7 sm:h-8">
                        <i class="fa-solid fa-shield text-xl sm:text-2xl leading-none"></i>
                        <span class="absolute inset-0 flex items-center justify-center text-[10px] sm:text-xs font-extrabold leading-none pt-0.5">$</span>
                    </span>
                    <i v-else :class="item.icon" class="text-xl sm:text-2xl"></i>
                </div>
                <p class="text-[9px] sm:text-[11px] font-medium text-[#3D5C48] leading-tight max-w-[72px] sm:max-w-none">
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
