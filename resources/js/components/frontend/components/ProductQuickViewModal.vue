<template>
    <Teleport to="body">
        <Transition name="qv-fade">
            <div
                v-if="open && listProduct"
                class="quick-view-root"
                role="presentation"
                @keydown.esc="close"
            >
                <div class="quick-view-backdrop" @click="close" aria-hidden="true"></div>

                <div
                    class="quick-view-panel"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="displayName"
                    tabindex="-1"
                    ref="panel"
                >
                    <button
                        type="button"
                        class="quick-view-close"
                        :aria-label="$t('button.close') || 'Close'"
                        @click="close"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                    <div v-if="loading" class="quick-view-loading">
                        <div class="quick-view-loading__media animate-pulse bg-gray-100 rounded-2xl"></div>
                        <div class="quick-view-loading__info space-y-3">
                            <div class="h-6 w-3/4 bg-gray-100 rounded animate-pulse"></div>
                            <div class="h-8 w-1/2 bg-gray-100 rounded animate-pulse"></div>
                            <div class="h-20 w-full bg-gray-100 rounded animate-pulse"></div>
                        </div>
                    </div>

                    <div v-else class="quick-view-body">
                        <div class="quick-view-gallery">
                            <Swiper
                                v-if="galleryImages.length"
                                dir="ltr"
                                :modules="swiperModules"
                                :slides-per-view="1"
                                :space-between="0"
                                :pagination="{ clickable: true }"
                                class="quick-view-swiper"
                            >
                                <SwiperSlide v-for="(image, index) in galleryImages" :key="index">
                                    <div class="quick-view-slide">
                                        <img
                                            :src="image"
                                            :alt="displayName"
                                            :loading="index === 0 ? 'eager' : 'lazy'"
                                            class="quick-view-slide__img"
                                            @error="onImageError"
                                        />
                                    </div>
                                </SwiperSlide>
                            </Swiper>
                            <div v-else class="quick-view-slide quick-view-slide--empty">
                                <img :src="setting.theme_logo" alt="" class="w-1/3 object-contain opacity-40" />
                            </div>
                        </div>

                        <div class="quick-view-info">
                            <h2 class="quick-view-title">{{ displayName }}</h2>

                            <div class="quick-view-rating" v-if="reviewCount > 0">
                                <div class="flex items-center gap-0.5" :aria-label="ratingLabel">
                                    <i
                                        v-for="star in 5"
                                        :key="star"
                                        class="fa-star text-xs"
                                        :class="star <= starCount ? 'fa-solid text-primary' : 'fa-regular text-gray-300'"
                                    ></i>
                                </div>
                                <span class="font-bold text-heading">{{ ratingText }}</span>
                                <span class="text-gray-500">({{ reviewCount }})</span>
                            </div>

                            <div class="quick-view-price">
                                <span class="quick-view-price__sale">{{ displaySalePrice }}</span>
                                <span v-if="prices.onSale" class="quick-view-price__old">{{ displayOldPrice }}</span>
                                <span v-if="prices.onSale && discountPercent > 0" class="quick-view-price__badge">
                                    {{ discountPercent }}% OFF
                                </span>
                            </div>

                            <div class="quick-view-meta">
                                <span v-if="temp.stock > 0 && temp.stock <= 5" class="quick-view-meta__urgent">
                                    <i class="fa-solid fa-fire"></i>
                                    {{ $t('label.only_x_left', { count: temp.stock }) }}
                                </span>
                                <span v-else-if="temp.stock > 0" class="quick-view-meta__ok">
                                    <i class="fa-solid fa-circle-check"></i>
                                    {{ $t('label.in_stock') || 'In stock' }}
                                </span>
                                <span v-else class="quick-view-meta__out">
                                    {{ $t('label.stock_out') }}
                                </span>
                                <span class="quick-view-meta__delivery">
                                    <i class="fa-solid fa-truck-fast"></i>
                                    {{ estimatedDelivery }}
                                </span>
                            </div>

                            <p v-if="descriptionPreview" class="quick-view-description">
                                {{ descriptionPreview }}
                            </p>

                            <VariationComponent
                                v-if="showVariationComponent && detail?.slug"
                                :product-slug="detail.slug"
                                :variation-tree-data="allVariationTree"
                                :method="onVariationSelected"
                                :variations="initialVariations"
                                :fallback-image="detail.image"
                            />

                            <div class="quick-view-qty">
                                <span class="quick-view-qty__label">{{ $t('label.quantity') }}</span>
                                <div class="quick-view-qty__control">
                                    <button type="button" @click="quantityDecrement" :disabled="temp.quantity <= 1">
                                        <i class="lab-fill-circle-minus"></i>
                                    </button>
                                    <input
                                        type="number"
                                        v-model.number="temp.quantity"
                                        min="1"
                                        @change="quantityUp"
                                    />
                                    <button type="button" @click="quantityIncrement">
                                        <i class="lab-fill-circle-plus"></i>
                                    </button>
                                </div>
                                <span v-if="temp.quantity > 1" class="quick-view-qty__total">
                                    {{ formattedTotalPrice }}
                                </span>
                            </div>

                            <div v-if="reviewSnippets.length" class="quick-view-reviews">
                                <p class="quick-view-reviews__title">{{ $t('label.reviews') }}</p>
                                <div
                                    v-for="(review, index) in reviewSnippets"
                                    :key="index"
                                    class="quick-view-reviews__item"
                                >
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-semibold text-sm capitalize">{{ review.name }}</span>
                                        <div class="flex items-center gap-0.5">
                                            <i
                                                v-for="s in 5"
                                                :key="s"
                                                class="fa-solid fa-star text-[9px]"
                                                :class="s <= review.star ? 'text-primary' : 'text-gray-300'"
                                            ></i>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600 line-clamp-2">{{ review.review }}</p>
                                </div>
                            </div>

                            <div class="quick-view-actions">
                                <button
                                    type="button"
                                    class="quick-view-btn quick-view-btn--wish"
                                    :aria-label="$t('label.wishlist')"
                                    @click="toggleWishlist"
                                >
                                    <i :class="isWishlisted ? 'lab-fill-heart text-primary' : 'lab-line-heart'"></i>
                                </button>
                                <button
                                    type="button"
                                    class="quick-view-btn quick-view-btn--cart"
                                    :disabled="temp.stock <= 0 || addingToCart"
                                    @click="addToCart"
                                >
                                    <i class="lab-line-bag"></i>
                                    <span>{{ $t('button.add_to_cart') }}</span>
                                </button>
                                <button
                                    type="button"
                                    class="quick-view-btn quick-view-btn--buy"
                                    :disabled="temp.stock <= 0 || addingToCart"
                                    @click="buyNow"
                                >
                                    <i class="fa-solid fa-bolt"></i>
                                    <span>{{ $t('button.buy_now') }}</span>
                                </button>
                            </div>

                            <button type="button" class="quick-view-details-link" @click="goToDetails">
                                {{ $t('label.view_full_details') || 'View full details' }}
                                <i class="fa-solid fa-arrow-right text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script>
import { Pagination } from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/vue";
import "swiper/css";
import "swiper/css/pagination";
import router from "../../../router";
import appService from "../../../services/appService";
import alertService from "../../../services/alertService";
import VariationComponent from "./VariationComponent.vue";
import {
    discountPercentage,
    getDetailPrices,
    parseAmount,
    withCartLinePricing,
} from "../../../utils/productOffer";
import {
    formatListProductRating,
    getListReviewCount,
    getStarFillCount,
} from "../../../utils/productRating";
import { trackAddToCart, trackWishlistToggle } from "../../../services/analyticsEcommerceBridge";

export default {
    name: "ProductQuickViewModal",
    components: {
        Swiper,
        SwiperSlide,
        VariationComponent,
    },
    props: {
        open: {
            type: Boolean,
            default: false,
        },
        listProduct: {
            type: Object,
            default: null,
        },
    },
    emits: ["close", "added-to-cart"],
    data() {
        return {
            loading: false,
            loadToken: 0,
            detail: null,
            showVariationComponent: false,
            selectedVariation: null,
            addingToCart: false,
            localWishlist: JSON.parse(localStorage.getItem("local_wishlist") || "[]"),
            initProduct: {
                isVariation: false,
                variationId: null,
                sku: "",
                stock: 0,
                quantity: 1,
                discount: 0,
                price: 0,
                oldPrice: 0,
                totalPrice: 0,
                maximum_purchase_quantity: 1,
            },
            temp: {
                name: "",
                image: "",
                isVariation: false,
                variationId: null,
                productId: null,
                sku: "",
                stock: 0,
                taxes: [],
                shipping: {},
                quantity: 1,
                discount: 0,
                price: 0,
                oldPrice: 0,
                totalPrice: 0,
                maximum_purchase_quantity: 1,
            },
        };
    },
    computed: {
        setting() {
            return this.$store.getters["frontendSetting/lists"] || {};
        },
        swiperModules() {
            return [Pagination];
        },
        displayName() {
            return this.detail?.name || this.listProduct?.name || "";
        },
        galleryImages() {
            const fromDetail = this.detail?.images;
            if (Array.isArray(fromDetail) && fromDetail.length) {
                return fromDetail;
            }
            const previews = this.listProduct?.previews;
            if (Array.isArray(previews) && previews.length) {
                return previews;
            }
            if (this.listProduct?.cover) {
                return [this.listProduct.cover];
            }
            if (this.detail?.image) {
                return [this.detail.image];
            }
            return [];
        },
        ratingProduct() {
            return this.detail || this.listProduct || {};
        },
        ratingText() {
            return formatListProductRating(this.ratingProduct);
        },
        starCount() {
            return getStarFillCount(this.ratingProduct);
        },
        reviewCount() {
            return getListReviewCount(this.ratingProduct);
        },
        ratingLabel() {
            return `${this.ratingText} out of 5`;
        },
        prices() {
            const source = this.selectedVariation?.sku
                ? {
                    price: parseAmount(this.selectedVariation.price),
                    old_price: parseAmount(this.selectedVariation.old_price),
                    is_offer: false,
                }
                : this.detail || this.listProduct;
            return getDetailPrices(source);
        },
        displaySalePrice() {
            if (this.selectedVariation?.sku) {
                return this.selectedVariation.price;
            }
            return this.prices.onSale
                ? (this.detail?.discounted_price || this.listProduct?.discounted_price)
                : (this.detail?.currency_price || this.listProduct?.currency_price);
        },
        displayOldPrice() {
            if (this.selectedVariation?.sku) {
                return this.selectedVariation.old_price;
            }
            return this.detail?.old_currency_price || this.listProduct?.currency_price;
        },
        discountPercent() {
            return discountPercentage(this.detail || this.listProduct);
        },
        formattedTotalPrice() {
            return appService.currencyFormat(
                this.temp.totalPrice,
                this.setting.site_digit_after_decimal_point,
                this.setting.site_default_currency_symbol,
                this.setting.site_currency_position
            );
        },
        descriptionPreview() {
            const raw = this.detail?.details;
            if (!raw) {
                return "";
            }
            const text = this.stripHtml(raw).replace(/\s+/g, " ").trim();
            if (!text) {
                return "";
            }
            return text.length > 160 ? `${text.slice(0, 157)}...` : text;
        },
        reviewSnippets() {
            const reviews = this.detail?.reviews || [];
            return Array.isArray(reviews) ? reviews.slice(0, 2) : [];
        },
        allVariationTree() {
            return this.$store.getters["frontendProductVariation/allVariation"] || [];
        },
        initialVariations() {
            return this.$store.getters["frontendProductVariation/initialVariation"] || [];
        },
        isWishlisted() {
            const product = this.listProduct;
            if (!product) {
                return false;
            }
            if (this.$store.getters.authStatus) {
                return !!(this.detail?.wishlist ?? product.wishlist);
            }
            return this.localWishlist.includes(product.id);
        },
        estimatedDelivery() {
            const today = new Date();
            const min = new Date(today);
            min.setDate(today.getDate() + 2);
            const max = new Date(today);
            max.setDate(today.getDate() + 4);
            const options = { month: "short", day: "numeric" };
            return `${min.toLocaleDateString("en-US", options)} - ${max.toLocaleDateString("en-US", options)}`;
        },
    },
    watch: {
        open: {
            immediate: true,
            handler(isOpen) {
                if (isOpen && this.listProduct) {
                    this.onOpen();
                } else if (!isOpen) {
                    this.onCloseCleanup();
                }
            },
        },
        listProduct(newProduct, oldProduct) {
            if (this.open && newProduct && newProduct?.id !== oldProduct?.id) {
                this.onOpen();
            }
        },
    },
    beforeUnmount() {
        this.onCloseCleanup();
    },
    methods: {
        stripHtml(html) {
            if (typeof document === "undefined") {
                return String(html).replace(/<[^>]*>/g, "");
            }
            const div = document.createElement("div");
            div.innerHTML = html;
            return div.textContent || div.innerText || "";
        },
        onImageError(event) {
            event.target.src = this.setting.theme_logo;
            event.target.classList.add("object-contain", "p-6", "opacity-50");
        },
        async onOpen() {
            const token = ++this.loadToken;
            this.loading = true;
            this.detail = null;
            this.selectedVariation = null;
            this.showVariationComponent = false;
            document.body.classList.add("quick-view-open");
            document.body.style.overflow = "hidden";

            this.$nextTick(() => this.$refs.panel?.focus?.());

            try {
                const product = this.listProduct;
                const tasks = [
                    this.$store.dispatch("frontendProduct/show", { slug: product.slug }),
                ];

                if (Number(product.variation_count) > 0) {
                    this.$store.commit("frontendProductVariation/initialVariation", []);
                    this.$store.commit("frontendProductVariation/allVariation", []);
                    tasks.push(
                        this.$store.dispatch("frontendProductVariation/allVariation", product.slug),
                        this.$store.dispatch("frontendProductVariation/initialVariation", product.id)
                    );
                }

                const results = await Promise.all(tasks);
                if (token !== this.loadToken) {
                    return;
                }

                const showRes = results[0];
                this.detail = showRes.data.data;
                this.applyDetail(this.detail);

                if (Number(product.variation_count) > 0) {
                    const tree = this.$store.getters["frontendProductVariation/allVariation"] || [];
                    const initial = this.$store.getters["frontendProductVariation/initialVariation"] || [];
                    this.showVariationComponent = tree.length > 0 || initial.length > 0;
                }
            } catch (e) {
                if (token === this.loadToken) {
                    alertService.error(this.$t("message.something_wrong") || "Could not load product.");
                    this.close();
                }
            } finally {
                if (token === this.loadToken) {
                    this.loading = false;
                }
            }
        },
        applyDetail(data) {
            this.initProduct = {
                isVariation: false,
                variationId: null,
                sku: data.sku,
                stock: data.stock,
                quantity: 1,
                discount: 0,
                price: parseAmount(data.price),
                oldPrice: parseAmount(data.old_price),
                totalPrice: parseAmount(data.price),
                maximum_purchase_quantity: data.maximum_purchase_quantity,
            };
            this.temp = {
                name: data.name,
                image: data.image || data.images?.[0] || this.listProduct?.cover,
                isVariation: false,
                variationId: null,
                productId: data.id,
                sku: data.sku,
                stock: data.stock,
                taxes: data.taxes,
                shipping: data.shipping,
                quantity: 1,
                discount: 0,
                price: parseAmount(data.price),
                oldPrice: parseAmount(data.old_price),
                totalPrice: parseAmount(data.price),
                maximum_purchase_quantity: data.maximum_purchase_quantity,
            };
        },
        onVariationSelected(variation) {
            this.selectedVariation = null;
            Object.assign(this.temp, {
                isVariation: this.initProduct.isVariation,
                variationId: this.initProduct.variationId,
                sku: this.initProduct.sku,
                stock: this.initProduct.stock,
                quantity: this.initProduct.quantity,
                discount: this.initProduct.discount,
                price: this.initProduct.price,
                oldPrice: this.initProduct.oldPrice,
                totalPrice: this.initProduct.price,
                maximum_purchase_quantity: this.initProduct.maximum_purchase_quantity,
            });

            if (variation?.sku) {
                this.selectedVariation = variation;
                this.temp.isVariation = true;
                this.temp.variationId = variation.id;
                this.temp.sku = variation.sku;
                this.temp.stock = variation.stock;
                this.temp.quantity = 1;
                this.temp.price = parseAmount(variation.price);
                this.temp.oldPrice = parseAmount(variation.old_price);
                this.temp.totalPrice = parseAmount(variation.price);
                this.temp.maximum_purchase_quantity = variation.maximum_purchase_quantity;
            }
        },
        quantityUp() {
            if (!this.temp.quantity || this.temp.quantity < 1) {
                this.temp.quantity = 1;
            }
            if (this.temp.quantity > this.temp.stock) {
                this.temp.quantity = this.temp.stock;
            }
            if (this.temp.quantity > this.temp.maximum_purchase_quantity) {
                alertService.error(this.$t("message.purchase_limit_exceeded"));
                this.temp.quantity = this.temp.maximum_purchase_quantity;
            }
            this.totalPriceSetup();
        },
        quantityIncrement() {
            this.temp.quantity++;
            if (this.temp.quantity > this.temp.stock) {
                this.temp.quantity--;
            }
            if (this.temp.quantity > this.temp.maximum_purchase_quantity) {
                alertService.error(this.$t("message.purchase_limit_exceeded"));
                this.temp.quantity--;
            }
            this.totalPriceSetup();
        },
        quantityDecrement() {
            this.temp.quantity--;
            if (this.temp.quantity < 1) {
                this.temp.quantity = 1;
            }
            this.totalPriceSetup();
        },
        totalPriceSetup() {
            this.temp.totalPrice = this.temp.price * this.temp.quantity;
        },
        validateBeforePurchase() {
            if (this.showVariationComponent && (!this.selectedVariation || !this.selectedVariation.sku)) {
                alertService.error(
                    this.$t("message.select_all_options")
                    || this.$t("message.please_select_a_variation")
                    || "Please select all options first."
                );
                return false;
            }
            if ((this.temp.stock || 0) <= 0) {
                alertService.error(this.$t("message.out_of_stock") || "This product is out of stock!");
                return false;
            }
            return true;
        },
        cartPricingSource() {
            if (this.selectedVariation?.sku) {
                return this.selectedVariation;
            }
            return {
                price: this.temp.price,
                old_price: this.temp.oldPrice,
                is_offer: this.detail?.is_offer,
                discount: this.detail?.discount,
                discount_percentage: this.detail?.discount_percentage,
            };
        },
        buildCartPayload(extra = {}) {
            return withCartLinePricing(
                {
                    name: this.temp.name,
                    product_id: this.temp.productId,
                    image: this.temp.image,
                    variation_names: "",
                    variation_id: this.temp.variationId ?? null,
                    sku: this.temp.sku,
                    stock: this.temp.stock,
                    taxes: this.temp.taxes,
                    shipping: this.temp.shipping,
                    quantity: this.temp.quantity,
                    maximum_purchase_quantity: this.temp.maximum_purchase_quantity,
                    in_baskets: this.detail?.in_baskets || this.listProduct?.in_baskets || 0,
                    bought_last_24_hours: this.detail?.bought_last_24_hours || this.listProduct?.bought_last_24_hours || 0,
                    actual_sales: this.detail?.actual_sales || this.listProduct?.actual_sales || 0,
                    ...extra,
                },
                this.cartPricingSource()
            );
        },
        async addToCart() {
            if (!this.validateBeforePurchase() || this.addingToCart) {
                return;
            }
            this.addingToCart = true;
            try {
                let payload = this.buildCartPayload();
                if (this.selectedVariation?.sku) {
                    const res = await this.$store.dispatch(
                        "frontendProductVariation/ancestorsToString",
                        this.selectedVariation.id
                    );
                    payload = { ...payload, variation_names: res.data.data };
                }
                await this.$store.dispatch("frontendCart/lists", payload);
                trackAddToCart(
                    { id: this.temp.productId, product_id: this.temp.productId, sku: this.temp.sku, name: this.temp.name },
                    this.temp.quantity
                );
                this.$emit("added-to-cart");
                this.close();
            } catch (err) {
                if (err?.message === "stockOut") {
                    alertService.error(this.$t("message.out_of_stock") || "This product is out of stock!");
                } else {
                    alertService.error(this.$t("message.maximum_quantity") || "Maximum purchase quantity reached!");
                }
            } finally {
                this.addingToCart = false;
            }
        },
        async buyNow() {
            if (!this.validateBeforePurchase() || this.addingToCart) {
                return;
            }
            this.addingToCart = true;
            try {
                let payload = this.buildCartPayload({ skipCartDrawer: true });
                if (this.selectedVariation?.sku) {
                    const res = await this.$store.dispatch(
                        "frontendProductVariation/ancestorsToString",
                        this.selectedVariation.id
                    );
                    payload = { ...payload, variation_names: res.data.data };
                }
                await this.$store.dispatch("frontendCart/lists", payload);
                this.close();
                router.push({ name: "frontend.checkout.checkout" });
            } catch (err) {
                if (err?.message === "stockOut") {
                    alertService.error(this.$t("message.out_of_stock") || "This product is out of stock!");
                } else {
                    alertService.error(this.$t("message.maximum_quantity") || "Maximum purchase quantity reached!");
                }
            } finally {
                this.addingToCart = false;
            }
        },
        toggleWishlist() {
            const product = this.listProduct;
            if (!product) {
                return;
            }
            const next = !this.isWishlisted;
            if (this.$store.getters.authStatus) {
                this.$store.dispatch("frontendWishlist/toggle", {
                    product_id: product.id,
                    toggle: next,
                }).then(() => {
                    if (this.detail) {
                        this.detail.wishlist = next;
                    }
                    product.wishlist = next;
                }).catch((err) => {
                    if (err.response?.status === 401) {
                        router.push({ name: "auth.login" });
                    }
                });
            } else {
                let localWish = [...this.localWishlist];
                if (localWish.includes(product.id)) {
                    localWish = localWish.filter((id) => id !== product.id);
                } else {
                    localWish.push(product.id);
                }
                localStorage.setItem("local_wishlist", JSON.stringify(localWish));
                this.localWishlist = localWish;
                trackWishlistToggle(
                    { id: product.id, product_id: product.id, sku: product.sku },
                    localWish.includes(product.id)
                );
            }
        },
        goToDetails() {
            const slug = this.detail?.slug || this.listProduct?.slug;
            if (!slug) {
                return;
            }
            this.close();
            router.push({ name: "frontend.product.details", params: { slug } });
        },
        close() {
            this.$emit("close");
        },
        onCloseCleanup() {
            this.loadToken++;
            document.body.classList.remove("quick-view-open");
            document.body.style.overflow = "";
        },
    },
};
</script>

<style scoped>
.quick-view-root {
    position: fixed;
    inset: 0;
    z-index: 10050;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding: 0;
}

.quick-view-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(2px);
}

.quick-view-panel {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 960px;
    max-height: 92vh;
    overflow: auto;
    background: #fff;
    border-radius: 1.25rem 1.25rem 0 0;
    box-shadow: 0 -8px 40px rgba(0, 0, 0, 0.18);
    outline: none;
}

.quick-view-close {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    z-index: 5;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 9999px;
    background: #fff;
    border: 1px solid #e5e7eb;
    color: #374151;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.quick-view-loading,
.quick-view-body {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    padding: 1rem 1rem 1.25rem;
}

.quick-view-loading__media {
    aspect-ratio: 1 / 1;
}

.quick-view-gallery {
    min-width: 0;
}

.quick-view-swiper {
    border-radius: 1rem;
    overflow: hidden;
    background: #f9fafb;
}

.quick-view-slide {
    aspect-ratio: 1 / 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f9fafb;
}

.quick-view-slide--empty {
    min-height: 240px;
}

.quick-view-slide__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.quick-view-info {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.quick-view-title {
    font-size: 1.125rem;
    line-height: 1.35;
    font-weight: 800;
    color: #111827;
    padding-right: 2.5rem;
}

.quick-view-rating {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.85rem;
}

.quick-view-price {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 0.5rem;
}

.quick-view-price__sale {
    font-size: 1.5rem;
    font-weight: 900;
    color: rgb(var(--primary) / 1);
}

.quick-view-price__old {
    font-size: 0.95rem;
    color: #9ca3af;
    text-decoration: line-through;
    font-weight: 600;
}

.quick-view-price__badge {
    font-size: 0.7rem;
    font-weight: 800;
    color: #fff;
    background: rgb(var(--primary) / 1);
    border-radius: 9999px;
    padding: 0.15rem 0.5rem;
}

.quick-view-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.quick-view-meta__urgent {
    color: #dc2626;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.quick-view-meta__ok {
    color: #16a34a;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.quick-view-meta__out {
    color: #dc2626;
}

.quick-view-meta__delivery {
    color: #4b5563;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.quick-view-description {
    font-size: 0.875rem;
    line-height: 1.5;
    color: #4b5563;
}

.quick-view-qty {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
}

.quick-view-qty__label {
    font-size: 0.875rem;
    font-weight: 700;
    color: #111827;
}

.quick-view-qty__control {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.2rem;
    border-radius: 9999px;
    background: #f7f7fc;
}

.quick-view-qty__control button {
    width: 1.75rem;
    height: 1.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
}

.quick-view-qty__control input {
    width: 2.5rem;
    text-align: center;
    font-size: 0.875rem;
    font-weight: 700;
    background: transparent;
    border: 0;
}

.quick-view-qty__total {
    font-size: 0.875rem;
    font-weight: 700;
    color: #16a34a;
}

.quick-view-reviews {
    border-top: 1px solid #f3f4f6;
    padding-top: 0.75rem;
}

.quick-view-reviews__title {
    font-size: 0.8rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.quick-view-reviews__item + .quick-view-reviews__item {
    margin-top: 0.65rem;
}

.quick-view-actions {
    display: grid;
    grid-template-columns: auto 1fr 1fr;
    gap: 0.5rem;
}

.quick-view-btn {
    min-height: 2.75rem;
    border-radius: 9999px;
    font-size: 0.8rem;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    transition: transform 0.15s ease, opacity 0.15s ease;
}

.quick-view-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.quick-view-btn:not(:disabled):active {
    transform: scale(0.98);
}

.quick-view-btn--wish {
    width: 2.75rem;
    border: 1px solid #e5e7eb;
    background: #fff;
    font-size: 1.1rem;
}

.quick-view-btn--cart {
    background: rgb(var(--primary) / 1);
    color: #fff;
    box-shadow: 0 4px 14px rgb(var(--primary) / 0.28);
}

.quick-view-btn--buy {
    background: #dc2626;
    color: #fff;
    box-shadow: 0 4px 14px rgba(220, 38, 38, 0.25);
}

.quick-view-details-link {
    width: 100%;
    text-align: center;
    font-size: 0.85rem;
    font-weight: 700;
    color: rgb(var(--primary) / 1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
}

.quick-view-swiper :deep(.swiper-pagination-bullet-active) {
    background: rgb(var(--primary) / 1);
}

@media (min-width: 768px) {
    .quick-view-root {
        align-items: center;
        padding: 1.5rem;
    }

    .quick-view-panel {
        border-radius: 1.25rem;
        max-height: 88vh;
    }

    .quick-view-loading,
    .quick-view-body {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 1.25rem;
        padding: 1.25rem;
    }

    .quick-view-title {
        font-size: 1.35rem;
    }
}

.qv-fade-enter-active,
.qv-fade-leave-active {
    transition: opacity 0.2s ease;
}

.qv-fade-enter-from,
.qv-fade-leave-to {
    opacity: 0;
}
</style>
