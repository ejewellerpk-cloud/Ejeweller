<template>
    <router-link
        :to="{ name: 'frontend.product.details', params: { slug: product.slug } }"
        class="related-product-card group block p-1 sm:p-1.5 bg-white rounded-2xl border border-gray-200/80 shadow-[0_4px_16px_rgba(0,0,0,0.05)] transition-all duration-300 hover:border-primary/20"
    >
        <div class="relative overflow-hidden rounded-xl aspect-[4/5] bg-[#fafafa]">
            <span
                v-if="product.is_offer && product.discount_percentage > 0"
                class="absolute top-2 left-2 z-10 bg-primary text-white text-[10px] font-extrabold px-2 py-0.5 rounded-full"
            >
                {{ Math.round(product.discount_percentage) }}% OFF
            </span>

            <img
                v-if="product.cover && !product.cover.includes('default/product')"
                :src="product.cover"
                :alt="product.name"
                width="320"
                height="400"
                loading="lazy"
                decoding="async"
                class="w-full h-full object-cover block transition-transform duration-500 group-hover:scale-[1.03]"
                @error="onImageError"
            >
            <div v-else class="w-full h-full flex items-center justify-center bg-gray-50/50">
                <img
                    :src="setting.theme_logo"
                    alt="logo"
                    loading="lazy"
                    class="w-1/2 h-1/2 object-contain opacity-40"
                >
            </div>
        </div>

        <div class="pt-2 px-0.5 pb-0.5">
            <h3 class="text-xs sm:text-sm font-bold text-heading capitalize line-clamp-2 leading-snug group-hover:text-primary transition-colors">
                {{ product.name }}
            </h3>
            <div class="flex flex-wrap items-center gap-1.5 mt-1">
                <span class="text-sm font-black text-primary">
                    {{ displayPrice }}
                </span>
                <del v-if="product.is_offer && product.old_currency_price" class="text-[11px] text-gray-400">
                    {{ product.old_currency_price }}
                </del>
            </div>
        </div>
    </router-link>
</template>

<script>
export default {
    name: 'RelatedProductCard',
    props: {
        product: {
            type: Object,
            required: true,
        },
    },
    computed: {
        setting() {
            return this.$store.getters['frontendSetting/lists'] || {};
        },
        displayPrice() {
            if (this.product.is_offer && this.product.discounted_price) {
                return this.product.discounted_price;
            }
            return this.product.currency_price;
        },
    },
    methods: {
        onImageError(event) {
            const logo = this.setting.theme_logo;
            if (!logo || event.target.src === logo) {
                return;
            }
            event.target.src = logo;
            event.target.classList.remove('object-cover');
            event.target.classList.add('object-contain', 'bg-white', 'p-4');
        },
    },
};
</script>

<style scoped>
.related-product-card {
    contain: layout style;
}
</style>
