<template>
    <div class="row">
        <div class="col-12 lg:col-8">
            <ul v-if="carts.length > 0" class="p-4 mb-11 rounded-2xl shadow-card">
                <li v-for="(cart, index) in carts"
                    :key="index"
                    class="flex items-start gap-3 pb-4 mb-4 border-b last:mb-0 last:pb-0 last:border-none border-gray-100">

                    <div class="relative w-28 aspect-square flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                        <!-- LAZY LOAD INDICATOR -->
                        <div v-show="!loadedImages[index]" class="absolute inset-0 flex flex-col items-center justify-center opacity-40">
                            <i class="fa-solid fa-spinner fa-spin text-xl text-primary mb-1"></i>
                        </div>
                        <img :src="cart.image" alt="products" 
                             loading="lazy"
                             @load="onImageLoad(index)"
                             @error="onImageError($event, index)"
                             :class="loadedImages[index] ? 'opacity-100' : 'opacity-0'"
                             class="w-full h-full object-cover transition-opacity duration-300 relative z-10" />
                    </div>

                    <div class="relative w-full overflow-hidden">
                        <h4 class="font-semibold capitalize whitespace-nowrap overflow-hidden text-ellipsis mb-1">
                            {{ cart.name }}
                        </h4>

                        <div v-if="cart.variation_id > 0" class="flex flex-wrap mb-2">
                            <span class="text-xs capitalize inline-flex items-center">
                                {{ cart.variation_names }}
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-3 mb-3 items-center">
                            <span class="font-semibold font-sans text-primary">
                                {{ currencyFormat(
                                    cart.price,
                                    setting.site_digit_after_decimal_point,
                                    setting.site_default_currency_symbol,
                                    setting.site_currency_position
                                ) }}
                            </span>

                            <!-- OLD PRICE -->
                            <del
                                v-if="cart.old_price > cart.price"
                                class="font-medium font-sans text-slate-400 text-sm line-through">

                                {{ currencyFormat(
                                    cart.old_price,
                                    setting.site_digit_after_decimal_point,
                                    setting.site_default_currency_symbol,
                                    setting.site_currency_position
                                ) }}
                            </del>

                            <!-- DISCOUNT BADGE -->
                            <span
                                v-if="cart.old_price > cart.price"
                                class="bg-primary text-white text-[10px] font-extrabold px-2 py-0.5 rounded-full shadow-[0_2px_6px_rgba(255,92,0,0.15)] flex items-center gap-1">

                                <i class="fa-solid fa-tags text-[9px]"></i>

                                {{ Math.round(((cart.old_price - cart.price) / cart.old_price) * 100) }}% OFF
                            </span>
                        </div>

                        <div class="flex items-start justify-between gap-3">

                            <!-- QUANTITY -->
                            <div class="flex items-center gap-1 w-20 p-1 rounded-full bg-[#F7F7FC]">

                                <button
                                    @click.prevent="quantityDecrement(index, cart)"
                                    type="button"
                                    :class="cart.quantity === 1 ? 'cursor-not-allowed' : ''"
                                    class="lab-fill-circle-minus text-lg leading-none transition-all duration-300 hover:text-primary">
                                </button>

                                <input
                                    v-on:keypress="onlyNumber($event)"
                                    v-on:keyup="quantityUp(index, cart, $event)"
                                    type="number"
                                    :value="cart.quantity"
                                    class="text-center w-full h-5 text-sm font-medium">

                                <button
                                    :class="cart.quantity >= cart.stock ? 'cursor-not-allowed' : ''"
                                    @click.prevent="quantityIncrement(index, cart)"
                                    type="button"
                                    class="lab-fill-circle-plus text-lg leading-none transition-all duration-300 hover:text-primary">
                                </button>
                            </div>

                            <!-- REMOVE BUTTON -->
                            <button
                                @click.prevent="removeProduct(index)"
                                class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#FFF4F4] text-[#E93C3C] transition-all duration-300 hover:bg-[#E93C3C] hover:text-white">

                                <i class="lab-line-trash text-sm"></i>

                                <span class="text-xs font-medium capitalize hidden sm:block">
                                    {{ $t('button.remove') }}
                                </span>
                            </button>

                        </div>

                        <p v-if="shouldShowCartSocialProof(cart)"
                            class="text-red-500 font-bold text-[11px] mt-1 flex items-center gap-1 leading-tight">
                            <i class="fa-solid fa-fire text-red-500 text-[10px]"></i>
                            <span>{{ cartSocialProofText(cart) }}</span>
                        </p>
                    </div>
                </li>
            </ul>

        </div>

        <div class="col-12 lg:col-4">
            <CouponComponent />
            <SummeryComponent />

            <router-link
                v-if="carts.length > 0"
                :to="{ name : 'frontend.checkout.checkout' }"
                class="field-button mt-6 font-semibold tracking-wide normal-case w-full text-center">

                {{ $t('button.process_to_checkout') }}
            </router-link>

            <CartTrustBadgesComponent v-if="carts.length > 0" />
        </div>
    </div>
</template>

<script>
import appService from "../../../../services/appService";
import alertService from "../../../../services/alertService";
import CouponComponent from "../CouponComponent.vue";
import SummeryComponent from "../SummeryComponent.vue";
import CartTrustBadgesComponent from "../CartTrustBadgesComponent.vue";
import { shouldShowSocialProof, socialProofTextForItem } from "../../../../utils/socialProof";

export default {
    name: "CartListComponent",

    components: {
        SummeryComponent,
        CouponComponent,
        CartTrustBadgesComponent
    },

    data() {
        return {
            loadedImages: {}
        };
    },

    computed: {
        setting() {
            return this.$store.getters['frontendSetting/lists'];
        },

        carts() {
            return this.$store.getters['frontendCart/lists'];
        }
    },

    methods: {
        onImageLoad(index) {
            this.loadedImages[index] = true;
        },
        onImageError(e, index) {
            this.loadedImages[index] = true;
            e.target.src = this.setting.theme_logo;
            e.target.classList.remove('object-cover');
            e.target.classList.add('object-contain', 'bg-white', 'p-2');
        },

        onlyNumber(e) {
            return appService.onlyNumber(e);
        },

        currencyFormat(amount, decimal, currency, position) {
            return appService.currencyFormat(
                amount,
                decimal,
                currency,
                position
            );
        },

        quantityUp(id, product, e) {

            let quantity = parseInt(e.target.value) || 1;

            const stock =
                (product.stock > 0)
                    ? parseInt(product.stock)
                    : Infinity;

            const maxVal = parseInt(product.maximum_purchase_quantity);

            const max =
                (maxVal > 0)
                    ? maxVal
                    : Infinity;

            const ceiling = Math.min(stock, max);

            if (quantity < 1) {
                quantity = 1;
            }

            if (quantity > ceiling && ceiling !== Infinity) {

                alertService.error(
                    this.$t('message.purchase_limit_exceeded')
                );

                quantity = ceiling;
            }

            this.$store.dispatch(
                'frontendCart/quantity',
                {
                    id: id,
                    status: quantity
                }
            ).then().catch();
        },

        quantityIncrement(id, product) {

            const qty = parseInt(product.quantity) || 1;

            const stock =
                product.stock != null
                    ? parseInt(product.stock)
                    : Infinity;

            if (qty >= stock) {

                alertService.error(
                    this.$t('message.out_of_stock')
                );

                return;
            }

            this.$store.dispatch(
                'frontendCart/quantity',
                {
                    id: id,
                    status: "increment"
                }
            ).then().catch(() => {

                alertService.error(
                    this.$t('message.something_wrong')
                );
            });
        },

        quantityDecrement(id, product) {

            const qty = parseInt(product.quantity) || 1;

            if (qty <= 1) {

                alertService.error(
                    this.$t('message.minimum_quantity') || "Minimum quantity is 1!"
                );

                return;
            }

            this.$store.dispatch(
                'frontendCart/quantity',
                {
                    id: id,
                    status: "decrement"
                }
            ).then().catch(() => {

                alertService.error(
                    this.$t('message.something_wrong') || "Something went wrong!"
                );
            });
        },

        removeProduct(id) {

            this.$store.dispatch(
                'frontendCart/remove',
                {
                    id: id
                }
            ).then().catch();
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
