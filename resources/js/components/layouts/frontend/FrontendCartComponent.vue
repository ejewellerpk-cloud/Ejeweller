<template>
    <aside id="cart-canvas" @click="closeBackdrop" class="fixed inset-0 z-50 bg-black/50 duration-500 transition-all invisible opacity-0">
        <div
            class="w-[85%] sm:w-full sm:max-w-md h-dvh overflow-x-hidden overflow-y-hidden flex flex-col bg-white duration-500 transition-all ms-auto translate-x-full">
            <div class="py-5 flex items-center justify-between px-4 border-b border-slate-100">
                <h3 class="text-[22px] font-bold capitalize">{{ $t('label.shopping_cart') }}</h3>
                <button type="button" class="lab-line-circle-cross text-lg text-danger"
                    @click.prevent="closeCanvas('cart-canvas')"></button>
            </div>

            <div v-if="carts.length === 0" class="h-[calc(100vh_-_218px)] flex flex-col items-center justify-center">
                <img class="w-52" :src="setting.image_cart" alt="empty">
                <p class="text-sm">{{ $t('message.empty_cart') }}</p>
            </div>

            <div v-if="carts.length > 0" class="flex-grow overflow-y-auto">
                <ul class="px-4 pt-4 pb-4">
                    <li v-for="(cart, index) in carts"
                        class="flex items-start gap-3 pb-4 mb-4 border-b last:mb-0 last:pb-0 last:border-none border-gray-100">
                        <img :src="cart.image" alt="products" class="w-28 rounded-lg flex-shrink-0" />

                        <div class="relative w-full overflow-hidden">
                            <h4 class="font-semibold capitalize whitespace-nowrap overflow-hidden text-ellipsis mb-1">
                                {{ cart.name }}
                            </h4>
                            <div v-if="cart.variation_id > 0" class="flex flex-wrap mb-2">
                                <span class="text-xs capitalize inline-flex items-center">{{ cart.variation_names }}</span>
                            </div>
                            <div class="flex flex-wrap gap-2 items-center mb-3">
                                <span class="font-semibold font-sans text-primary">{{ currencyFormat(cart.price,
                                    setting.site_digit_after_decimal_point,
                                    setting.site_default_currency_symbol, setting.site_currency_position) }}</span>
                                <del v-if="cart.old_price > cart.price" class="font-medium font-sans text-slate-400 text-xs line-through">
                                    {{ currencyFormat(cart.old_price, setting.site_digit_after_decimal_point,
                                        setting.site_default_currency_symbol, setting.site_currency_position) }}
                                </del>
                                <span v-if="cart.old_price > cart.price" class="bg-primary text-white text-[9px] font-extrabold px-1.5 py-0.5 rounded-full shadow-[0_2px_6px_rgba(255,92,0,0.15)] flex items-center gap-0.5">
                                    <i class="fa-solid fa-tags text-[8px]"></i>
                                    {{ Math.round(((cart.old_price - cart.price) / cart.old_price) * 100) }}% OFF
                                </span>
                            </div>

                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-1 w-20 p-1 rounded-full bg-[#F7F7FC]">
                                    <button @click.prevent="quantityDecrement(index, cart)" type="button"
                                        :class="cart.quantity === 1 ? 'cursor-not-allowed' : ''"
                                        class="lab-fill-circle-minus text-lg leading-none transition-all duration-300 hover:text-primary"></button>
                                    <input v-on:keypress="onlyNumber($event)" v-on:keyup="quantityUp(index, cart, $event)"
                                        type="number" :value="cart.quantity"
                                        class="text-center w-full h-5 text-sm font-medium">
                                    <button
                                        :class="cart.quantity >= cart.stock ? 'cursor-not-allowed' : cart.quantity >= cart.maximum_purchase_quantity ? 'cursor-not-allowed' : ''"
                                        @click.prevent="quantityIncrement(index, cart)" type="button"
                                        class="lab-fill-circle-plus text-lg leading-none transition-all duration-300 hover:text-primary"></button>
                                </div>
                                <button @click.prevent="removeProduct(index)"
                                    class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#FFF4F4] text-[#E93C3C] transition-all duration-300 hover:bg-[#E93C3C] hover:text-white">
                                    <i class="lab-line-trash text-sm"></i>
                                    <span class="text-xs font-medium capitalize hidden sm:block">{{ $t('button.remove')
                                        }}</span>
                                </button>
                            </div>

                            <p v-if="shouldShowCartSocialProof(cart)"
                                class="text-red-500 font-bold text-[9px] sm:text-[10px] mt-1.5 flex flex-nowrap items-center gap-1 leading-none whitespace-nowrap overflow-hidden text-ellipsis max-w-full">
                                <i class="fa-solid fa-fire text-red-500 text-[9px] flex-shrink-0"></i>
                                <span class="truncate">{{ cartSocialProofText(cart) }}</span>
                            </p>
                        </div>
                    </li>
                </ul>

                <!-- You May Also Like -->
                <div v-if="popularProducts.length > 0" class="px-4 pb-4 border-t border-gray-100 pt-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2.5">You May Also Like</h4>
                    <div class="flex gap-2.5 overflow-x-auto pb-2 cart-related-scroll">
                        <div v-for="product in popularProducts" :key="product.id"
                             @click.prevent="goToProduct(product.slug)"
                             class="flex-shrink-0 w-[100px] cursor-pointer group">
                            <div class="w-[100px] h-[100px] rounded-lg overflow-hidden bg-gray-50 mb-1.5 relative">
                                
                                <!-- Fallback if no valid cover exists -->
                                <div v-if="!product.cover || product.cover.includes('default/product')" class="absolute inset-0 flex items-center justify-center bg-gray-50/50 z-10">
                                    <img :src="setting.theme_logo" alt="logo" loading="lazy" class="w-1/2 h-1/2 object-contain opacity-40 group-hover:scale-105 group-hover:opacity-70 transition-all duration-300" />
                                </div>
                                
                                <template v-else>
                                    <!-- Dot Loading Indicator -->
                                    <div class="absolute inset-0 flex items-center justify-center z-0" v-if="!loadedImages[product.id]">
                                        <div class="flex gap-1">
                                            <div class="w-1.5 h-1.5 bg-primary/40 rounded-full animate-bounce"></div>
                                            <div class="w-1.5 h-1.5 bg-primary/40 rounded-full animate-bounce" style="animation-delay: 0.15s"></div>
                                            <div class="w-1.5 h-1.5 bg-primary/40 rounded-full animate-bounce" style="animation-delay: 0.3s"></div>
                                        </div>
                                    </div>
                                    <img :src="product.cover" :alt="product.name" loading="lazy"
                                         @load="onImageLoad(product.id)"
                                         @error="onImageError($event, product.id)"
                                         :class="loadedImages[product.id] ? 'opacity-100' : 'opacity-0'"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-all duration-300 relative z-10" />
                                </template>
                            </div>
                            <h5 class="text-[11px] font-medium text-gray-700 leading-tight line-clamp-2 group-hover:text-primary transition-colors duration-200">{{ product.name }}</h5>
                            <div class="flex items-center gap-1 mt-0.5">
                                <span class="text-[11px] font-bold text-primary font-sans">{{ currencyFormat(product.price, setting.site_digit_after_decimal_point, setting.site_default_currency_symbol, setting.site_currency_position) }}</span>
                                <del v-if="product.is_offer" class="text-[9px] text-gray-400 font-sans">{{ currencyFormat(product.old_price, setting.site_digit_after_decimal_point, setting.site_default_currency_symbol, setting.site_currency_position) }}</del>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="carts.length > 0" class="p-4 border-t border-gray-100 flex-shrink-0">
                <!-- Total Savings Box (Premium Highlighted Banner) -->
                <div v-if="totalSavings > 0" class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 mb-4 flex items-center justify-between shadow-sm animate-pulse">
                    <div class="flex items-center gap-1.5 text-emerald-800 font-bold text-xs">
                        <i class="fa-solid fa-gift text-emerald-600 text-sm"></i>
                        <span>Congratulations! You are saving:</span>
                    </div>
                    <span class="text-sm font-extrabold text-emerald-700 font-sans">
                        {{ currencyFormat(totalSavings, setting.site_digit_after_decimal_point, setting.site_default_currency_symbol, setting.site_currency_position) }}
                    </span>
                </div>

                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold capitalize">{{ $t('label.subtotal') }}</h3>
                    <h4 class="font-semibold capitalize font-sans">{{ currencyFormat(subtotal,
                        setting.site_digit_after_decimal_point,
                        setting.site_default_currency_symbol, setting.site_currency_position) }} </h4>
                </div>
                <router-link :to="{ name: 'frontend.checkout' }" v-on:click="closeCanvas('cart-canvas')"
                    class="text-center w-full mb-3 h-12 leading-12 px-12 font-semibold tracking-wide rounded-full whitespace-nowrap text-white bg-primary">
                    {{ $t('button.process_to_checkout') }}
                </router-link>
                <CartTrustBadgesComponent />
                <p class="pb-12 sm:pb-0 capitalize text-xs text-center font-medium mt-3">{{ $t('message.checkout_guide') }}
                </p>
            </div>
        </div>
    </aside>
</template>

<script>
import targetService from "../../../services/targetService";
import appService from "../../../services/appService";
import alertService from "../../../services/alertService";
import {useCanvas} from "../../../composables/canvas";
import statusEnum from "../../../enums/modules/statusEnum";
import router from "../../../router";
import CartTrustBadgesComponent from "../../frontend/checkout/CartTrustBadgesComponent.vue";
import { shouldShowSocialProof, socialProofTextForItem } from "../../../utils/socialProof";

export default {
    name: "FrontendCartComponent",
    components: {
        CartTrustBadgesComponent,
    },
    data() {
        return {
            loadedImages: {}
        }
    },
    setup() {
        const {closeCanvas, closeBackdrop} = useCanvas();

        return {
            closeCanvas,
            closeBackdrop
        }
    },
    computed: {
        setting: function () {
            return this.$store.getters['frontendSetting/lists'];
        },
        carts: function () {
            return this.$store.getters['frontendCart/lists'];
        },
        subtotal: function () {
            return this.$store.getters['frontendCart/subtotal'];
        },
        cartCoupon: function () {
            return this.$store.getters['frontendCart/coupon'];
        },
        totalSavings: function () {
            return this.$store.getters['frontendCart/totalSavings'];
        },
        popularProducts: function () {
            return this.$store.getters['frontendProduct/popularProducts'];
        }
    },
    mounted() {
        this.$store.dispatch('frontendProduct/popularProducts', {
            paginate: 1,
            per_page: 10,
            order_column: 'id',
            order_type: 'desc',
            status: statusEnum.ACTIVE
        }).catch();
        this.$store.dispatch('frontendCart/refreshSocialProof').catch();
    },
    methods: {
        onImageLoad(key) {
            this.loadedImages[key] = true;
        },
        onImageError(event, key) {
            this.loadedImages[key] = true;
            event.target.src = this.setting.theme_logo;
            event.target.classList.remove('object-cover');
            event.target.classList.add('object-contain', 'p-3', 'opacity-40');
        },
        hideTarget: function (id, cClass) {
            targetService.hideTarget(id, cClass);
        },
        onlyNumber: function (e) {
            return appService.onlyNumber(e);
        },
        currencyFormat(amount, decimal, currency, position) {
            return appService.currencyFormat(amount, decimal, currency, position);
        },
        quantityUp: function (id, product, e) {
            let quantity = parseInt(e.target.value) || 1;
            const stock = (product.stock > 0) ? parseInt(product.stock) : Infinity;
            const maxVal = parseInt(product.maximum_purchase_quantity);
            const max = (maxVal > 0) ? maxVal : Infinity;
            const ceiling = Math.min(stock, max);

            if (quantity < 1) quantity = 1;
            if (quantity > ceiling && ceiling !== Infinity) {
                alertService.error(this.$t('message.purchase_limit_exceeded'));
                quantity = ceiling;
            }
            this.$store.dispatch('frontendCart/quantity', { id: id, status: quantity }).then().catch();
        },
        quantityIncrement: function (id, product) {
            const qty = parseInt(product.quantity) || 1;
            const stock = product.stock != null ? parseInt(product.stock) : Infinity;
            const maxVal = parseInt(product.maximum_purchase_quantity);
            const max = (maxVal > 0) ? maxVal : Infinity; // 0 or null = no limit
            if (qty >= stock) {
                alertService.error(this.$t('message.out_of_stock'));
                return;
            }
            if (qty >= max) {
                alertService.error(this.$t('message.purchase_limit_exceeded'));
                return;
            }
            this.$store.dispatch('frontendCart/quantity', { id: id, status: "increment" }).then().catch((err) => {
                alertService.error(this.$t('message.something_wrong'));
            });
        },
        quantityDecrement: function (id, product) {
            const qty = parseInt(product.quantity) || 1;
            if (qty <= 1) {
                alertService.error(this.$t('message.minimum_quantity') || "Minimum quantity is 1!");
                return;
            }
            this.$store.dispatch('frontendCart/quantity', { id: id, status: "decrement" }).then().catch((err) => {
                alertService.error(this.$t('message.something_wrong') || "Something went wrong!");
            });
        },
        removeProduct: function (id) {
            this.$store.dispatch('frontendCart/remove', { id: id }).then().catch();
            if (Object.keys(this.cartCoupon).length !== 0) {
                this.$store.dispatch("frontendCart/destroyCoupon").then().catch();
                alertService.warning(this.$t('message.coupon_remove'));
            }
        },
        goToProduct: function (slug) {
            this.closeCanvas('cart-canvas');
            router.push({ name: 'frontend.product.details', params: { slug: slug } });
        },
        shouldShowCartSocialProof(cart) {
            return shouldShowSocialProof(cart);
        },
        cartSocialProofText(cart) {
            return socialProofTextForItem(cart);
        },
    }
}
</script>

<style scoped>
.cart-related-scroll::-webkit-scrollbar {
    height: 3px;
}
.cart-related-scroll::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
.cart-related-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>