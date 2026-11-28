<template>
    <LoadingComponent :props="loading" />

    <div class="col-12">
        <div class="rg-page">
            <div class="rg-page__header">
                <div class="min-w-0">
                    <h1 class="rg-page__title">{{ $t('label.reviews_generator') }}</h1>
                    <p class="rg-page__subtitle">{{ $t('message.reviews_generator_hint') }}</p>
                </div>
                <router-link :to="{ name: 'admin.review.list' }" class="rg-btn rg-btn--muted">
                    <i class="lab lab-line-undo lab-font-size-16"></i>
                    <span>{{ $t('button.back') }}</span>
                </router-link>
            </div>

            <form class="rg-card" @submit.prevent="generate">
                <div class="rg-section">
                    <label class="rg-label required" for="rg_product_id">{{ $t('label.product') }}</label>
                    <vue-select
                        id="rg_product_id"
                        class="db-field-control f-b-custom-select"
                        v-model="form.product_id"
                        :options="products"
                        label-by="name"
                        value-by="id"
                        :closeOnSelect="true"
                        :searchable="true"
                        :clearOnClose="true"
                        placeholder="--"
                        search-placeholder="--"
                        :class="errors.product_id ? 'invalid' : ''"
                    />
                    <small class="db-field-alert" v-if="errors.product_id">{{ errors.product_id[0] }}</small>

                    <div v-if="selectedProduct" class="rg-product">
                        <img
                            class="rg-product__thumb"
                            :src="selectedProduct.cover || selectedProduct.thumb"
                            :alt="selectedProduct.name"
                        />
                        <div class="min-w-0">
                            <p class="rg-product__name">{{ selectedProduct.name }}</p>
                            <p class="rg-product__meta" v-if="selectedProduct.sku">
                                SKU: {{ selectedProduct.sku }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rg-section">
                    <label class="rg-label required" for="rg_quantity">{{ $t('label.quantity') }}</label>
                    <input
                        id="rg_quantity"
                        v-model.number="form.quantity"
                        type="number"
                        min="1"
                        max="50"
                        class="db-field-control rg-qty"
                        :class="errors.quantity ? 'invalid' : ''"
                    />
                    <small class="rg-help">{{ $t('message.reviews_generator_quantity_hint') }}</small>
                    <small class="db-field-alert" v-if="errors.quantity">{{ errors.quantity[0] }}</small>
                </div>

                <div class="rg-section">
                    <label class="rg-label">{{ $t('label.upload_images') }}</label>
                    <p class="rg-help mb-3">{{ $t('message.reviews_generator_images_hint') }}</p>
                    <div class="rg-images">
                        <div class="relative" v-for="(_, index) in maxImages" :key="index">
                            <button
                                v-if="imageUrl[index]"
                                @click="removeImage(index)"
                                type="button"
                                class="lab-fill-close text-xs w-5 h-5 leading-5 rounded-full shadow absolute -top-1 -right-1 text-danger bg-white z-10"
                            ></button>
                            <img
                                v-if="imageUrl[index]"
                                :src="imageUrl[index]"
                                alt="review"
                                class="rg-images__preview"
                            />
                            <label
                                v-else
                                class="rg-images__slot"
                            >
                                <input
                                    @change="handleImageUpload($event, index)"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp,image/jpg"
                                    class="absolute inset-0 opacity-0 cursor-pointer"
                                />
                                <i class="lab-fill-image text-xl text-text"></i>
                                <span>{{ $t('label.add_image') }}</span>
                            </label>
                        </div>
                    </div>
                    <small class="db-field-alert" v-if="errors['images.0']">{{ errors['images.0'][0] }}</small>
                </div>

                <div class="rg-actions">
                    <button type="submit" class="rg-btn rg-btn--primary" :disabled="loading.isActive">
                        <i class="lab lab-line-star"></i>
                        <span>{{ $t('button.generate_reviews') }}</span>
                    </button>
                    <router-link :to="{ name: 'admin.review.list' }" class="rg-btn rg-btn--muted">
                        <i class="lab lab-fill-close-circle"></i>
                        <span>{{ $t('button.cancel') }}</span>
                    </router-link>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import LoadingComponent from "../components/LoadingComponent";
import alertService from "../../../services/alertService";

export default {
    name: "ReviewGeneratorComponent",
    components: { LoadingComponent },
    data() {
        return {
            loading: {
                isActive: false,
            },
            maxImages: 8,
            imageUrl: Array(8).fill(null),
            images: {},
            form: {
                product_id: null,
                quantity: 10,
            },
            errors: {},
            productSearch: {
                paginate: 0,
                page: 1,
                order_column: "id",
            },
        };
    },
    mounted() {
        this.loading.isActive = true;
        this.$store
            .dispatch("product/getSimpleProduct", this.productSearch)
            .finally(() => {
                this.loading.isActive = false;
            });
    },
    computed: {
        products() {
            return this.$store.getters["product/simpleList"] || [];
        },
        selectedProduct() {
            if (!this.form.product_id) {
                return null;
            }
            return this.products.find((p) => Number(p.id) === Number(this.form.product_id)) || null;
        },
    },
    methods: {
        handleImageUpload(event, index) {
            const input = event.target;
            if (!input.files || !input.files[0]) {
                return;
            }
            const file = input.files[0];
            this.imageUrl[index] = URL.createObjectURL(file);
            this.images[index] = file;
        },
        removeImage(index) {
            if (this.imageUrl[index]) {
                URL.revokeObjectURL(this.imageUrl[index]);
            }
            this.imageUrl[index] = null;
            this.images[index] = null;
        },
        generate() {
            this.errors = {};

            if (!this.form.product_id) {
                this.errors.product_id = [this.$t("message.reviews_generator_product_required")];
                return;
            }

            const qty = Number(this.form.quantity);
            if (!qty || qty < 1 || qty > 50) {
                this.errors.quantity = [this.$t("message.reviews_generator_quantity_hint")];
                return;
            }

            const fd = new FormData();
            fd.append("product_id", this.form.product_id);
            fd.append("quantity", qty);

            Object.keys(this.images).forEach((key) => {
                if (this.images[key]) {
                    fd.append("images[]", this.images[key]);
                }
            });

            this.loading.isActive = true;
            this.$store
                .dispatch("review/generate", { form: fd })
                .then((res) => {
                    const count =
                        res.data?.meta?.generated ??
                        (Array.isArray(res.data?.data) ? res.data.data.length : qty);
                    alertService.success(
                        this.$t("message.reviews_generator_success", { count })
                    );
                    this.$router.push({
                        name: "admin.review.list",
                        query: { product_id: String(this.form.product_id) },
                    });
                })
                .catch((err) => {
                    this.errors = err.response?.data?.errors || {};
                    if (err.response?.data?.message && !Object.keys(this.errors).length) {
                        alertService.error(err.response.data.message);
                    }
                })
                .finally(() => {
                    this.loading.isActive = false;
                });
        },
    },
    beforeUnmount() {
        this.imageUrl.forEach((url) => {
            if (url) {
                URL.revokeObjectURL(url);
            }
        });
    },
};
</script>

<style scoped>
.rg-page {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.rg-page__header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.rg-page__title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a2e;
    letter-spacing: -0.02em;
}

.rg-page__subtitle {
    margin: 0.35rem 0 0;
    font-size: 0.875rem;
    color: #6b7280;
    max-width: 36rem;
    line-height: 1.45;
}

.rg-card {
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 1rem;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
}

.rg-section {
    max-width: 40rem;
}

.rg-label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
}

.rg-label.required::after {
    content: " *";
    color: #ef4444;
}

.rg-help {
    display: block;
    margin-top: 0.4rem;
    font-size: 0.75rem;
    color: #9ca3af;
    line-height: 1.4;
}

.rg-qty {
    max-width: 8rem;
}

.rg-product {
    margin-top: 1rem;
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 0.75rem;
    background: #f8f9fc;
    border: 1px solid #eef0f5;
    border-radius: 0.75rem;
}

.rg-product__thumb {
    width: 4.5rem;
    height: 4.5rem;
    object-fit: cover;
    border-radius: 0.625rem;
    border: 1px solid #e5e7eb;
    background: #fff;
    flex-shrink: 0;
}

.rg-product__name {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #111827;
    line-height: 1.35;
}

.rg-product__meta {
    margin: 0.25rem 0 0;
    font-size: 0.75rem;
    color: #6b7280;
}

.rg-images {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.rg-images__preview {
    width: 5.5rem;
    height: 5.5rem;
    object-fit: cover;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
}

.rg-images__slot {
    position: relative;
    width: 5.5rem;
    height: 5.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    cursor: pointer;
    background: #eff0f6;
    border: 1px dashed #c7cbd6;
    border-radius: 0.75rem;
    font-size: 0.625rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: capitalize;
}

.rg-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding-top: 0.25rem;
}

.rg-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.55rem 1rem;
    border-radius: 0.625rem;
    font-size: 0.875rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    text-decoration: none;
    line-height: 1.2;
}

.rg-btn--primary {
    background: #4f46e5;
    color: #fff;
}

.rg-btn--primary:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.rg-btn--muted {
    background: #4b5563;
    color: #fff;
}
</style>
